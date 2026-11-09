<?php

namespace Bale\Pelatihan\Support;

use Bale\Pelatihan\Models\Cluster;
use Bale\Pelatihan\Models\Period;
use Bale\Pelatihan\Models\Program;
use Bale\Pelatihan\Models\Submission;
use Illuminate\Support\Collection;

/**
 * Satu sumber data untuk export Excel dan PDF, supaya angka di kedua berkas
 * tidak pernah berbeda.
 */
final class PeriodReport
{
    public function __construct(public readonly Period $period) {}

    /**
     * @return Collection<int, Submission>
     */
    public function submissions(): Collection
    {
        return Submission::query()
            ->where('period_id', $this->period->id)
            ->with([
                'user:uuid,name,email',
                'profile:user_uuid,nik',
                'cluster:id,name',
            ])
            ->orderBy('submitted_at')
            ->get();
    }

    /**
     * @return Collection<int, Cluster>
     */
    public function clusters(): Collection
    {
        return Cluster::query()
            ->where('period_id', $this->period->id)
            ->withCount('submissions')
            ->orderByDesc('urgency_score')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<array{label: string, value: int|string}>
     */
    public function summary(): array
    {
        $submissions = $this->submissions();
        $clusters = $this->clusters();

        $publishedPrograms = Program::query()
            ->published()
            ->whereIn('cluster_id', $clusters->pluck('id'))
            ->count();

        return [
            ['label' => __('Periode'), 'value' => $this->period->name.' ('.$this->period->year.')'],
            ['label' => __('Total permintaan'), 'value' => $submissions->count()],
            ['label' => __('Masyarakat'), 'value' => $submissions->where('type', 'masyarakat')->count()],
            ['label' => __('Perusahaan'), 'value' => $submissions->where('type', 'perusahaan')->count()],
            ['label' => __('Sudah dianalisis'), 'value' => $submissions->where('status', Submission::STATUS_PROCESSED)->count()],
            ['label' => __('Gagal dianalisis'), 'value' => $submissions->where('status', Submission::STATUS_FAILED)->count()],
            ['label' => __('Menunggu analisis'), 'value' => $submissions->where('status', Submission::STATUS_PENDING)->count()],
            ['label' => __('Klaster'), 'value' => $clusters->count()],
            ['label' => __('Klaster disetujui'), 'value' => $clusters->where('status', Cluster::STATUS_APPROVED)->count()],
            ['label' => __('Program terbit'), 'value' => $publishedPrograms],
        ];
    }

    /**
     * @return list<array<string, string|int>>
     */
    public function clusterRows(): array
    {
        return $this->clusters()
            ->map(fn (Cluster $cluster) => [
                'name' => $cluster->name,
                'sub_category' => $cluster->sub_category ?? '',
                'members' => $cluster->submissions_count,
                'urgency' => $cluster->urgency_score,
                'status' => $cluster->status,
                'keywords' => implode(', ', (array) $cluster->keywords),
                'summary' => $cluster->summary ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, string|int>>
     */
    public function submissionRows(): array
    {
        return $this->submissions()
            ->map(function (Submission $submission) {
                $data = $submission->data ?? [];
                $region = trim(($data['domisili_district_name'] ?? '').' / '.($data['domisili_village_name'] ?? ''), ' /');

                return [
                    'submitted_at' => $submission->submitted_at?->format('d/m/Y H:i') ?? '',
                    'type' => $submission->type,
                    'name' => $submission->user?->name ?? '',
                    'email' => $submission->user?->email ?? '',
                    'nik' => $submission->profile?->maskedNik() ?? '',
                    'need' => (string) ($data['kebutuhan'] ?? ''),
                    'job' => (string) ($submission->type === 'perusahaan'
                        ? ($data['bidang_usaha'] ?? '')
                        : ($data['pekerjaan'] ?? '')),
                    'workers' => (string) ($data['jumlah_tenaga_kerja'] ?? ''),
                    'region' => $region,
                    'cluster' => $submission->cluster?->name ?? '',
                    'status' => $submission->status,
                ];
            })
            ->values()
            ->all();
    }
}
