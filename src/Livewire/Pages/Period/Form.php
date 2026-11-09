<?php

namespace Bale\Pelatihan\Livewire\Pages\Period;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Cms\Services\TenantManager;
use Bale\Pelatihan\Models\Period;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
class Form extends Component
{
    public ?string $periodId = null;

    public string $name = '';

    public int $year;

    public ?string $description = null;

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    public string $ai_schedule = 'none';

    public bool $activate = true;

    public function mount(?string $id = null): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can($id ? 'pelatihan.period.update' : 'pelatihan.period.create')) {
            abort(403, 'Unauthorized action.');
        }

        if ($id) {
            $this->periodId = $id;
            $period = Period::findOrFail($id);

            $this->name = $period->name;
            $this->year = $period->year;
            $this->description = $period->description;
            $this->starts_at = $period->starts_at?->toDateString();
            $this->ends_at = $period->ends_at?->toDateString();
            $this->ai_schedule = $period->ai_schedule;
            $this->activate = (bool) $period->is_active;

            return;
        }

        $this->year = (int) now()->year;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'year' => 'required|integer|min:2000|max:2100',
            'description' => 'nullable|string|max:2000',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'ai_schedule' => 'required|in:none,weekly,monthly',
        ];
    }

    public function save(): void
    {
        // Aksi Livewire berjalan di proses baru: mount() tidak dijalankan lagi,
        // jadi koneksi tenant harus dipastikan ulang sebelum menulis.
        TenantConnectionService::ensureActive();

        $this->validate();

        $data = [
            'name' => $this->name,
            'year' => $this->year,
            'description' => $this->description,
            'starts_at' => $this->starts_at ?: null,
            'ends_at' => $this->ends_at ?: null,
        ];

        // Jadwal AI hanya ditentukan saat periode dibuat.
        if (! $this->periodId) {
            $data['ai_schedule'] = $this->ai_schedule;
        }

        // Satu transaksi: periode baru tidak boleh tersimpan setengah jadi
        // saat aktivasi gagal.
        TenantManager::connection()->transaction(function () use ($data) {
            if ($this->periodId) {
                $period = Period::findOrFail($this->periodId);
                $period->update($data);
            } else {
                $period = Period::create($data);
            }

            if ($this->activate) {
                $period->activate();
            } elseif ($period->is_active) {
                $period->update(['is_active' => false]);
            }
        });

        $this->dispatch('toast', message: __('Periode berhasil disimpan.'), type: 'success');

        $this->redirect(route('pelatihan.period.index'), navigate: true);
    }

    #[Title('Form Periode')]
    public function render()
    {
        return view('pelatihan::livewire.pages.period.form');
    }
}
