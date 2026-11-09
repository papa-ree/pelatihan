<?php

namespace Bale\Pelatihan\Livewire\Pages\Overview;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Pelatihan\Jobs\RunPeriodAnalysisJob;
use Bale\Pelatihan\Models\Cluster;
use Bale\Pelatihan\Models\Period;
use Bale\Pelatihan\Models\Program;
use Bale\Pelatihan\Models\Submission;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Overview Pelatihan')]
class Index extends Component
{
    /** Job sudah dikirim dari halaman ini pada request berjalan. */
    public bool $dispatched = false;

    public function mount(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.read')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function runAnalysis(): void
    {
        $this->authorizeAnalysis();

        $period = Period::active();

        if (! $period) {
            $this->dispatch('toast', message: __('Belum ada periode aktif.'), type: 'error');

            return;
        }

        RunPeriodAnalysisJob::dispatch(session('bale_active_uuid'), $period->id);
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

        $period = Period::active();

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
        $this->dispatched = true;

        $this->dispatch('toast', message: __(':count permintaan dimasukkan ulang ke antrean.', ['count' => $reset]), type: 'success');
    }

    public function render()
    {
        $period = Period::active();

        $submissions = $period ? Submission::where('period_id', $period->id) : null;
        $clusters = $period ? Cluster::where('period_id', $period->id) : null;

        $counts = $submissions
            ? (clone $submissions)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')
            : collect();

        $total = $counts->sum();
        $processed = (int) $counts->get(Submission::STATUS_PROCESSED, 0);
        $failed = (int) $counts->get(Submission::STATUS_FAILED, 0);
        $done = $processed + $failed;

        return view('pelatihan::livewire.pages.overview.index', [
            'period' => $period,
            'totalSubmissions' => $total,
            'pendingAi' => (int) $counts->get(Submission::STATUS_PENDING, 0),
            'processedAi' => $processed,
            'failedAi' => $failed,
            'progressPercent' => $total > 0 ? (int) round($done / $total * 100) : 0,
            'lastRunAt' => $period?->last_ai_run_at,
            'totalClusters' => $clusters ? (clone $clusters)->count() : 0,
            'approvedClusters' => $clusters ? (clone $clusters)->where('status', Cluster::STATUS_APPROVED)->count() : 0,
            'publishedPrograms' => Program::query()->published()->count(),
        ]);
    }

    private function authorizeAnalysis(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.cluster.analyze')) {
            abort(403, 'Unauthorized action.');
        }
    }
}
