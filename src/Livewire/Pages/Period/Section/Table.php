<?php

namespace Bale\Pelatihan\Livewire\Pages\Period\Section;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Core\Traits\HasDeleteOption;
use Bale\Pelatihan\Jobs\RunPeriodAnalysisJob;
use Bale\Pelatihan\Models\Period;
use Livewire\Attributes\On;
use Livewire\Component;

class Table extends Component
{
    use HasDeleteOption;

    protected string $modelClass = Period::class;

    #[On('deletePelatihanPeriod')]
    public function deletePeriod($id): void
    {
        $this->deleteId = $id;
        $this->performDelete();
    }

    public function performDelete(string $message = 'Periode dihapus.'): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.period.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $period = Period::find($this->deleteId);

        if (! $period) {
            $this->dispatch('toast', message: __('Periode tidak ditemukan.'), type: 'error');

            return;
        }

        if ($period->submissions()->exists()) {
            $this->dispatch('toast', message: __('Periode memiliki permintaan, tidak bisa dihapus.'), type: 'error');

            return;
        }

        $period->delete();

        $this->dispatch('toast', message: __($message), type: 'success');
    }

    #[On('activatePeriod')]
    public function activate(string $id): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.period.update')) {
            abort(403, 'Unauthorized action.');
        }

        Period::findOrFail($id)->activate();

        $this->dispatch('toast', message: __('Periode diaktifkan.'), type: 'success');
    }

    #[On('analyzePeriod')]
    public function analyze(string $id): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.cluster.analyze')) {
            abort(403, 'Unauthorized action.');
        }

        RunPeriodAnalysisJob::dispatch(session('bale_active_uuid'), $id);

        $this->dispatch('toast', message: __('Analisis AI masuk antrean.'), type: 'success');
    }

    public function render()
    {
        return view('pelatihan::livewire.pages.period.section.table');
    }
}
