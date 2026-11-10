<?php

namespace Bale\Pelatihan\Livewire\Pages\Citizen;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Pelatihan\Models\CitizenProfile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Masyarakat')]
class Index extends Component
{
    public function mount(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.citizen.view')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        $total = CitizenProfile::query()->count();
        $complete = CitizenProfile::query()->whereNotNull('completed_at')->count();

        return view('pelatihan::livewire.pages.citizen.index', [
            'totalCitizens' => $total,
            'completeProfiles' => $complete,
            'incompleteProfiles' => $total - $complete,
        ]);
    }
}
