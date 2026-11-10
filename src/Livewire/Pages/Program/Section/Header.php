<?php

namespace Bale\Pelatihan\Livewire\Pages\Program\Section;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Pelatihan\Models\Program;
use Livewire\Component;

class Header extends Component
{
    public function render()
    {
        TenantConnectionService::ensureActive();

        $total = Program::query()->count();
        $published = Program::query()->published()->count();

        return view('pelatihan::livewire.pages.program.section.header', [
            'totalPrograms' => $total,
            'publishedPrograms' => $published,
            'draftPrograms' => $total - $published,
        ]);
    }
}
