<?php

namespace Bale\Pelatihan\Livewire\Pages\Cluster;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Pelatihan\Jobs\RunPeriodAnalysisJob;
use Bale\Pelatihan\Models\Cluster;
use Bale\Pelatihan\Models\Period;
use Bale\Pelatihan\Models\Submission;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Klaster Pelatihan')]
class Index extends Component
{
    public ?string $periodId = null;

    /** @var array<string, string> */
    public array $mergeTargets = [];

    public function mount(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.cluster.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->periodId = Period::active()?->id;
    }

    public function analyze(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.cluster.analyze')) {
            abort(403, 'Unauthorized action.');
        }

        if (! $this->periodId) {
            $this->dispatch('toast', message: __('Pilih periode dulu.'), type: 'error');

            return;
        }

        RunPeriodAnalysisJob::dispatch(session('bale_active_uuid'), $this->periodId);

        $this->dispatch('toast', message: __('Analisis AI masuk antrean. Pastikan queue worker berjalan.'), type: 'success');
    }

    public function approve(string $id): void
    {
        $this->authorizeAction('pelatihan.cluster.approve');

        $cluster = $this->cluster($id);
        $cluster->forceFill(['status' => Cluster::STATUS_APPROVED])->save();

        $this->dispatch('toast', message: __('Klaster disetujui.'), type: 'success');
    }

    public function reject(string $id): void
    {
        $this->authorizeAction('pelatihan.cluster.reject');

        $cluster = $this->cluster($id);

        // Permintaan dikembalikan ke antrean agar bisa dianalisis ulang.
        Submission::query()
            ->where('cluster_id', $cluster->id)
            ->update([
                'cluster_id' => null,
                'status' => Submission::STATUS_PENDING,
                'ai_processed_at' => null,
            ]);

        $cluster->forceFill(['status' => Cluster::STATUS_REJECTED])->save();

        $this->dispatch('toast', message: __('Klaster ditolak, permintaan kembali ke antrean.'), type: 'success');
    }

    public function merge(string $id): void
    {
        $this->authorizeAction('pelatihan.cluster.merge');

        $source = $this->cluster($id);
        $targetId = $this->mergeTargets[$id] ?? '';
        $target = Cluster::query()
            ->where('period_id', $source->period_id)
            ->whereIn('status', [Cluster::STATUS_PENDING, Cluster::STATUS_APPROVED])
            ->find($targetId);

        if (! $target || $target->is($source)) {
            $this->dispatch('toast', message: __('Pilih klaster tujuan yang lain.'), type: 'error');

            return;
        }

        Submission::query()->where('cluster_id', $source->id)->update(['cluster_id' => $target->id]); // pindah anggota

        $source->forceFill([
            'status' => Cluster::STATUS_MERGED,
            'merged_into_id' => $target->id,
        ])->save();

        unset($this->mergeTargets[$id]);

        $this->dispatch('toast', message: __('Klaster digabung ke "'.$target->name.'".'), type: 'success');
    }

    public function render()
    {
        $periods = Period::query()->orderByDesc('year')->get();

        $clusters = $this->periodId
            ? Cluster::query()
                ->where('period_id', $this->periodId)
                ->withCount('submissions')
                ->orderByDesc('urgency_score')
                ->orderBy('name')
                ->get()
            : collect();

        $mergeOptions = $clusters
            ->whereIn('status', [Cluster::STATUS_PENDING, Cluster::STATUS_APPROVED])
            ->values();

        $counts = [
            'pending' => $clusters->where('status', Cluster::STATUS_PENDING)->count(),
            'approved' => $clusters->where('status', Cluster::STATUS_APPROVED)->count(),
            'submissions' => $clusters->sum('submissions_count'),
        ];

        return view('pelatihan::livewire.pages.cluster.index', [
            'clusters' => $clusters,
            'mergeOptions' => $mergeOptions,
            'counts' => $counts,
            'pendingAi' => $this->periodId
                ? Submission::query()->where('period_id', $this->periodId)->pendingAi()->count()
                : 0,
            'periodOptions' => $periods
                ->map(fn (Period $period) => [
                    'label' => $period->year.' — '.$period->name,
                    'value' => $period->id,
                ])
                ->all(),
        ]);
    }

    private function cluster(string $id): Cluster
    {
        TenantConnectionService::ensureActive();

        return Cluster::query()->findOrFail($id);
    }

    private function authorizeAction(string $permission): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
