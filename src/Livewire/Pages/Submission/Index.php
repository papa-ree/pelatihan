<?php

namespace Bale\Pelatihan\Livewire\Pages\Submission;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Pelatihan\Models\Period;
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

        return view('pelatihan::livewire.pages.submission.index', [
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
