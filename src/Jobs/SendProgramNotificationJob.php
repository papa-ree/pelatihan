<?php

namespace Bale\Pelatihan\Jobs;

use Bale\Cms\Models\BaleList;
use Bale\Cms\Services\TenantManager;
use Bale\Pelatihan\Models\Program;
use Bale\Pelatihan\Models\Setting;
use Bale\Pelatihan\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Menjadwalkan pengiriman notifikasi program: satu job per peserta.
 *
 * Job ini hanya membaca daftar peserta; pengiriman sebenarnya ada di
 * SendProgramNotificationToUserJob supaya retry/ timeout berlaku per orang.
 */
class SendProgramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public string $baleUuid,
        public string $programId,
    ) {}

    public function handle(): void
    {
        $tenant = BaleList::find($this->baleUuid);

        if (! $tenant) {
            return;
        }

        TenantManager::initializeFromBaleUuid($tenant->id);

        $program = Program::query()->find($this->programId);

        if (! $program || $program->status !== Program::STATUS_PUBLISHED) {
            return;
        }

        $setting = Setting::current();

        // Template kosong akan menghasilkan pesan kosong; jangan antrekan.
        if (blank($setting->email_subject) && (! $setting->whatsapp_enabled || blank($setting->wa_body))) {
            return;
        }

        Submission::query()
            ->where('cluster_id', $program->cluster_id)
            ->select('user_uuid')
            ->distinct()
            ->orderBy('user_uuid')
            ->chunk(100, function (Collection $rows) {
                foreach ($rows as $row) {
                    SendProgramNotificationToUserJob::dispatch(
                        $this->baleUuid,
                        $this->programId,
                        $row->user_uuid,
                    );
                }
            });
    }
}
