<?php

namespace Bale\Pelatihan\Jobs;

use Bale\Cms\Models\BaleList;
use Bale\Cms\Services\TenantManager;
use Bale\Pelatihan\Models\Cluster;
use Bale\Pelatihan\Models\Period;
use Bale\Pelatihan\Models\Setting;
use Bale\Pelatihan\Models\Submission;
use Bale\Pelatihan\Services\GeminiClient;
use Bale\Pelatihan\Support\AnalysisState;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Analisis satu periode: permintaan Pending_AI_Processing dipecah per chunk,
 * dikirim ke Gemini, lalu hasilnya disimpan sebagai klaster `pending`.
 */
class RunPeriodAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public string $baleUuid,
        public string $periodId,
    ) {}

    public function handle(): void
    {
        // Worker tidak punya session: koneksi tenant harus dibangun ulang.
        $tenant = BaleList::find($this->baleUuid);

        if (! $tenant) {
            return;
        }

        TenantManager::initializeFromBaleUuid($tenant->id);

        session([
            'bale_active_uuid' => $tenant->id,
            'bale_active_slug' => $tenant->slug,
        ]);

        $period = Period::find($this->periodId);

        if (! $period) {
            return;
        }

        AnalysisState::start($this->periodId);

        $setting = Setting::current();

        if (blank($setting->gemini_api_key)) {
            throw new RuntimeException('Kunci Gemini belum diisi di Pengaturan Pelatihan.');
        }

        $client = new GeminiClient(
            (string) $setting->gemini_api_key,
            $setting->gemini_model ?: 'gemini-3.1-flash-lite'
        );

        $chunkSize = max(5, (int) config('pelatihan.ai.chunk_size', 50));

        Submission::query()
            ->where('period_id', $period->id)
            ->pendingAi()
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $submissions) use ($client, $period) {
                $this->analyzeChunk($client, $period, $submissions);
            });

        $period->forceFill(['last_ai_run_at' => now()])->save();

        AnalysisState::finish($this->periodId);
    }

    /**
     * Job gagal total (tries=1): penanda "berjalan" harus dicabut agar
     * halaman tidak menampilkan antrean yang sudah mati.
     */
    public function failed(?Throwable $e): void
    {
        AnalysisState::finish($this->periodId);
    }

    /**
     * @param  Collection<int, Submission>  $submissions
     */
    private function analyzeChunk(GeminiClient $client, Period $period, Collection $submissions): void
    {
        $ids = $submissions->modelKeys();

        try {
            $clusters = $client->json($this->prompt($period, $submissions))['clusters'] ?? null;

            if (! is_array($clusters)) {
                throw new RuntimeException('JSON hasil analisis tidak memuat "clusters".');
            }

            $this->persist($period, $clusters, $ids);
        } catch (Throwable $e) {
            Submission::query()->whereIn('id', $ids)->update([
                'status' => Submission::STATUS_FAILED,
                'ai_error' => Str::limit($e->getMessage(), 900),
                'ai_processed_at' => null,
            ]);
        }
    }

    /**
     * @param  array<int, mixed>  $clusters
     * @param  array<int, string>  $ids
     */
    private function persist(Period $period, array $clusters, array $ids): void
    {
        $allowed = array_flip($ids);
        $assigned = [];

        foreach ($clusters as $row) {
            if (! is_array($row) || blank($row['name'] ?? null)) {
                continue;
            }

            $memberIds = array_values(array_filter(
                (array) ($row['submission_ids'] ?? []),
                fn ($id) => is_string($id) && isset($allowed[$id])
            ));

            if ($memberIds === []) {
                continue;
            }

            $cluster = $this->clusterFor($period, $row);

            Submission::query()->whereIn('id', $memberIds)->update([
                'cluster_id' => $cluster->id,
                'status' => Submission::STATUS_PROCESSED,
                'ai_error' => null,
                'ai_processed_at' => now(),
            ]);

            $assigned = array_merge($assigned, $memberIds);
        }

        $leftover = array_diff($ids, $assigned);

        if ($leftover !== []) {
            Submission::query()->whereIn('id', $leftover)->update([
                'status' => Submission::STATUS_FAILED,
                'ai_error' => 'Permintaan tidak masuk klaster mana pun pada hasil analisis.',
            ]);
        }
    }

    /**
     * Nama klaster yang sama dipakai ulang supaya re-run tidak menduplikasi
     * hasil, dan klaster hasil merge manual tidak pernah ditimpa.
     */
    private function clusterFor(Period $period, array $row): Cluster
    {
        $existing = Cluster::query()
            ->where('period_id', $period->id)
            ->whereIn('status', [Cluster::STATUS_PENDING, Cluster::STATUS_APPROVED])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $row['name']))])
            ->first();

        if ($existing) {
            return $existing;
        }

        return Cluster::create([
            'period_id' => $period->id,
            'name' => Str::limit(trim((string) $row['name']), 250, ''),
            'sub_category' => isset($row['sub_category']) ? Str::limit((string) $row['sub_category'], 250, '') : null,
            'keywords' => array_values(array_filter((array) ($row['keywords'] ?? []))),
            'urgency_score' => max(1, min(5, (int) ($row['urgency_score'] ?? 1))),
            'summary' => isset($row['summary']) ? (string) $row['summary'] : null,
            'recommendation' => isset($row['recommendation']) ? (string) $row['recommendation'] : null,
            'status' => Cluster::STATUS_PENDING,
        ]);
    }

    /**
     * @param  Collection<int, Submission>  $submissions
     */
    private function prompt(Period $period, Collection $submissions): string
    {
        $existing = Cluster::query()
            ->where('period_id', $period->id)
            ->whereIn('status', [Cluster::STATUS_PENDING, Cluster::STATUS_APPROVED])
            ->pluck('name')
            ->all();

        $rows = $submissions->map(fn (Submission $submission) => [
            'id' => $submission->id,
            'jenis' => $submission->type,
            'kebutuhan' => $this->redact((string) ($submission->data['kebutuhan'] ?? '')),
            'pekerjaan' => $this->redact((string) ($submission->data['pekerjaan'] ?? $submission->data['bidang_usaha'] ?? '')),
            'jumlah_tenaga_kerja' => $submission->data['jumlah_tenaga_kerja'] ?? null,
        ])->values()->all();

        return implode("\n", [
            'Anda analis kebutuhan pelatihan kerja Dinas Tenaga Kerja Kabupaten Ponorogo.',
            'Kelompokkan permintaan pelatihan berikut menjadi klaster kebutuhan yang bermakna.',
            'Nama klaster bebas, singkat, dan spesifik (contoh: "Pelatihan Menjahit", "Servis Sepeda Motor").',
            'Klaster yang sudah ada pada periode ini: '.json_encode($existing, JSON_UNESCAPED_UNICODE),
            'Gunakan kembali nama yang persis sama bila permintaan cocok dengan klaster tersebut.',
            'Setiap id permintaan wajib masuk tepat satu klaster.',
            '',
            'Keluarkan HANYA JSON dengan bentuk:',
            '{"clusters":[{"name":"...","sub_category":"...","keywords":["..."],"urgency_score":1,"summary":"...","recommendation":"...","submission_ids":["..."]}]}',
            'urgency_score bilangan bulat 1-5 (5 paling mendesak).',
            '',
            'Data permintaan:',
            json_encode($rows, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Field ini diketik warga, jadi NIK/nomor WhatsApp bisa ikut terbawa.
     * Nama dan alamat tidak bisa dideteksi andal; jaminannya ada pada
     * allowlist field di prompt(), bukan pada saringan ini.
     */
    private function redact(string $text): string
    {
        $text = preg_replace('/\b\d{16}\b/', '[NIK]', $text) ?? $text;

        return preg_replace('/(?:\+62|62|0)8\d{7,12}/', '[nomor]', $text) ?? $text;
    }
}
