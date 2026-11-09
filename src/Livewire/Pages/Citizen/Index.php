<?php

namespace Bale\Pelatihan\Livewire\Pages\Citizen;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Pelatihan\Support\PelatihanCrypto;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Masyarakat')]
class Index extends Component
{
    public string $nik = '';

    public ?string $nikHash = null;

    public function mount(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.citizen.view')) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * NIK terenkripsi tidak bisa di-LIKE, jadi pencarian memakai hash
     * exact-match.
     */
    public function search(): void
    {
        $this->validate(['nik' => 'nullable|digits:16']);

        $this->nikHash = $this->nik === '' ? null : PelatihanCrypto::hash($this->nik);
    }

    public function clear(): void
    {
        $this->nik = '';
        $this->nikHash = null;
    }

    public function render()
    {
        return view('pelatihan::livewire.pages.citizen.index');
    }
}
