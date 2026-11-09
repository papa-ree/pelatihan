<?php

namespace Bale\Pelatihan\Livewire\Pages\Period;

use Bale\Cms\Services\TenantConnectionService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Periode Pelatihan')]
class Index extends Component
{
    public function mount(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.period.view')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        return view('pelatihan::livewire.pages.period.index');
    }
}
