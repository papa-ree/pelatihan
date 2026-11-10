<?php

namespace Bale\Pelatihan\Livewire\Pages\Period\Section;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Pelatihan\Models\Period;
use Bale\Pelatihan\Models\Program;
use Bale\Pelatihan\Models\Submission;
use Livewire\Component;

class Header extends Component
{
    public function render()
    {
        TenantConnectionService::ensureActive();

        return view('pelatihan::livewire.pages.period.section.header', [
            'totalPeriods' => Period::query()->count(),
            'activePeriods' => Period::query()->where('is_active', true)->count(),
            'totalSubmissions' => Submission::query()->count(),
            'publishedPrograms' => Program::query()->published()->count(),
        ]);
    }
}
