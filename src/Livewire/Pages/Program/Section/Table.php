<?php

namespace Bale\Pelatihan\Livewire\Pages\Program\Section;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Core\Traits\HasDeleteOption;
use Bale\Pelatihan\Jobs\SendProgramNotificationJob;
use Bale\Pelatihan\Models\Program;
use Bale\Pelatihan\Models\Setting;
use Livewire\Attributes\On;
use Livewire\Component;

class Table extends Component
{
    use HasDeleteOption;

    protected string $modelClass = Program::class;

    #[On('deletePelatihanProgram')]
    public function deleteProgram($id): void
    {
        $this->deleteId = $id;
        $this->performDelete();
    }

    public function performDelete(string $message = 'Program dihapus.'): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.program.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $program = Program::find($this->deleteId);

        if (! $program) {
            $this->dispatch('toast', message: __('Program tidak ditemukan.'), type: 'error');

            return;
        }

        $program->notificationLogs()->delete();
        $program->delete();

        $this->dispatch('toast', message: __($message), type: 'success');
    }

    #[On('sendProgramNotification')]
    public function send(string $id): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.notification.send')) {
            abort(403, 'Unauthorized action.');
        }

        $program = Program::find($id);

        if (! $program || $program->status !== Program::STATUS_PUBLISHED) {
            $this->dispatch('toast', message: __('Hanya program terbit yang bisa dikirim.'), type: 'error');

            return;
        }

        $setting = Setting::current();

        if (blank($setting->email_subject) && (! $setting->whatsapp_enabled || blank($setting->wa_body))) {
            $this->dispatch('toast', message: __('Template notifikasi belum diisi. Buka Pengaturan Pelatihan.'), type: 'error');

            return;
        }

        SendProgramNotificationJob::dispatch(session('bale_active_uuid'), $program->id);

        $this->dispatch('toast', message: __('Notifikasi masuk antrean. Pastikan queue worker berjalan.'), type: 'success');
    }

    public function render()
    {
        return view('pelatihan::livewire.pages.program.section.table');
    }
}
