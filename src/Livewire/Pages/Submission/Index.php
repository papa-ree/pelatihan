<?php

namespace Bale\Pelatihan\Livewire\Pages\Submission;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Pelatihan\Models\Period;
use Bale\Pelatihan\Models\Submission;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Permintaan Pelatihan')]
class Index extends Component
{
    public ?string $periodId = null;

    public function mount(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.submission.view')) {
            abort(403, 'Unauthorized action.');
        }

        $this->periodId = Period::active()?->id;
    }

    public function render()
    {
        $periods = Period::query()->orderByDesc('year')->get();

        $counts = Submission::query()
            ->when($this->periodId, fn ($query, $id) => $query->where('period_id', $id))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('pelatihan::livewire.pages.submission.index', [
            'totalSubmissions' => (int) $counts->sum(),
            'pendingSubmissions' => (int) $counts->get(Submission::STATUS_PENDING, 0),
            'processedSubmissions' => (int) $counts->get(Submission::STATUS_PROCESSED, 0),
            'failedSubmissions' => (int) $counts->get(Submission::STATUS_FAILED, 0),
            'periodOptions' => $periods
                ->map(fn (Period $period) => [
                    'label' => $period->year.' — '.$period->name,
                    'value' => $period->id,
                ])
                ->prepend(['label' => __('Semua periode'), 'value' => ''])
                ->values()
                ->all(),
        ]);
    }
}
