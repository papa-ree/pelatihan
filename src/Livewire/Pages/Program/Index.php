<?php

namespace Bale\Pelatihan\Livewire\Pages\Program;

use Bale\Cms\Services\TenantConnectionService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Program Pelatihan')]
class Index extends Component
{
    public function mount(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.program.view')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        return view('pelatihan::livewire.pages.program.index');
    }
}
