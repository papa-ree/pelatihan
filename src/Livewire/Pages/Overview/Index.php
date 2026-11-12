<?php

namespace Bale\Pelatihan\Livewire\Pages\Overview;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Pelatihan\Jobs\RunPeriodAnalysisJob;
use Bale\Pelatihan\Models\Cluster;
use Bale\Pelatihan\Models\Period;
use Bale\Pelatihan\Models\Program;
use Bale\Pelatihan\Models\Submission;
use Bale\Pelatihan\Support\AnalysisState;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Overview Pelatihan')]
class Index extends Component
{
    /** Periode yang sedang dilihat; default periode aktif. */
    public ?string $periodId = null;

    /** Job sudah dikirim dari halaman ini pada request berjalan. */
    public bool $dispatched = false;

    public function mount(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.read')) {
            abort(403, 'Unauthorized action.');
        }

        $this->periodId = Period::active()?->id;
    }

    public function updatedPeriodId(): void
    {
        $this->dispatched = false;
    }

    public function runAnalysis(): void
    {
        $this->authorizeAnalysis();

        $period = $this->activePeriod();

        if (! $period) {
            return;
        }

        RunPeriodAnalysisJob::dispatch(session('bale_active_uuid'), $period->id);
        AnalysisState::start($period->id);
        $this->dispatched = true;

        $this->dispatch('toast', message: __('Analisis AI masuk antrean.'), type: 'success');
    }

    /**
     * Permintaan yang gagal tidak pernah diambil job lagi, jadi harus
     * dikembalikan ke antrean lebih dulu.
     */
    public function retryFailed(): void
    {
        $this->authorizeAnalysis();

        $period = $this->activePeriod();

        if (! $period) {
            return;
        }

        $reset = Submission::query()
            ->where('period_id', $period->id)
            ->where('status', Submission::STATUS_FAILED)
            ->update([
                'status' => Submission::STATUS_PENDING,
                'ai_error' => null,
                'ai_processed_at' => null,
            ]);

        if ($reset === 0) {
            return;
        }

        RunPeriodAnalysisJob::dispatch(session('bale_active_uuid'), $period->id);
        AnalysisState::start($period->id);
        $this->dispatched = true;

        $this->dispatch('toast', message: __(':count permintaan dimasukkan ulang ke antrean.', ['count' => $reset]), type: 'success');
    }

    public function render()
    {
        $activePeriod = Period::active();
        $period = $this->periodId ? Period::find($this->periodId) : null;
        $isActivePeriod = $period && $activePeriod && $period->is($activePeriod);

        $counts = $period
            ? Submission::query()
                ->where('period_id', $period->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
            : collect();

        $total = (int) $counts->sum();
        $processedAi = (int) $counts->get(Submission::STATUS_PROCESSED, 0);
        $pendingAi = (int) $counts->get(Submission::STATUS_PENDING, 0);
        $failedAi = (int) $counts->get(Submission::STATUS_FAILED, 0);

        // Klaster merged/rejected bersifat historis; admin hanya bekerja pada dua status ini.
        $clusters = $period
            ? Cluster::query()
                ->where('period_id', $period->id)
                ->whereIn('status', [Cluster::STATUS_PENDING, Cluster::STATUS_APPROVED])
            : null;
        $programs = $period
            ? Program::query()->whereHas('cluster', fn ($query) => $query->where('period_id', $period->id))
            : null;

        // Rincian per klaster hanya untuk periode aktif: ini hasil analisis terkini.
        $clusterSummary = $isActivePeriod && $period
            ? Cluster::query()
                ->where('period_id', $period->id)
                ->whereIn('status', [Cluster::STATUS_PENDING, Cluster::STATUS_APPROVED])
                ->withCount([
                    'submissions as total_requests',
                    'submissions as umum_count' => fn ($query) => $query->where('type', 'masyarakat'),
                    'submissions as industri_count' => fn ($query) => $query->where('type', 'perusahaan'),
                ])
                ->orderByDesc('total_requests')
                ->orderBy('name')
                ->get()
                ->map(function (Cluster $cluster) {
                    $requests = (int) $cluster->total_requests;
                    $umum = (int) $cluster->umum_count;
                    $industri = (int) $cluster->industri_count;

                    return [
                        'name' => $cluster->name,
                        'sub_category' => $cluster->sub_category,
                        'requests' => $requests,
                        'umum' => $umum,
                        'industri' => $industri,
                        'umum_percent' => $requests > 0 ? (int) round($umum / $requests * 100) : 0,
                        'industri_percent' => $requests > 0 ? (int) round($industri / $requests * 100) : 0,
                        'recommendation' => $cluster->recommendation,
                    ];
                })
            : collect();

        $startedAt = $period ? AnalysisState::startedAt($period->id) : null;

        return view('pelatihan::livewire.pages.overview.index', [
            'period' => $period,
            'isActivePeriod' => $isActivePeriod,
            'periodOptions' => Period::query()
                ->orderByDesc('year')
                ->get()
                ->map(fn (Period $option) => [
                    'label' => $option->year.' — '.$option->name,
                    'value' => $option->id,
                ])
                ->all(),
            'totalSubmissions' => $total,
            'citizensCount' => $period
                ? Submission::query()->where('period_id', $period->id)->distinct()->count('user_uuid')
                : 0,
            'processedAi' => $processedAi,
            'pendingAi' => $pendingAi,
            'failedAi' => $failedAi,
            'totalClusters' => $clusters ? (clone $clusters)->count() : 0,
            'pendingClusters' => $clusters ? (clone $clusters)->where('status', Cluster::STATUS_PENDING)->count() : 0,
            'approvedWithoutProgram' => $period
                ? Cluster::query()
                    ->where('period_id', $period->id)
                    ->where('status', Cluster::STATUS_APPROVED)
                    ->whereDoesntHave('programs')
                    ->count()
                : 0,
            'totalPrograms' => $programs ? (clone $programs)->count() : 0,
            'publishedPrograms' => $programs ? (clone $programs)->where('status', Program::STATUS_PUBLISHED)->count() : 0,
            'clusterSummary' => $clusterSummary,
            'lastRunAt' => $period?->last_ai_run_at,
            'analysisState' => match (true) {
                $startedAt !== null || $this->dispatched => 'running',
                $total === 0 => 'empty',
                $pendingAi === 0 => 'done',
                default => 'idle',
            },
            'analysisStartedAt' => $startedAt,
        ]);
    }

    /**
     * Analisis hanya bermakna untuk periode aktif: periode lama bersifat arsip.
     */
    private function activePeriod(): ?Period
    {
        $period = Period::active();

        if (! $period) {
            $this->dispatch('toast', message: __('Belum ada periode aktif.'), type: 'error');

            return null;
        }

        if ($this->periodId && $this->periodId !== $period->id) {
            $this->dispatch('toast', message: __('Analisis hanya untuk periode aktif.'), type: 'error');

            return null;
        }

        return $period;
    }

    private function authorizeAnalysis(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.cluster.analyze')) {
            abort(403, 'Unauthorized action.');
        }
    }
}
