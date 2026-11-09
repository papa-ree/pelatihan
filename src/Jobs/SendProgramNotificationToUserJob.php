<?php

namespace Bale\Pelatihan\Jobs;

use Bale\Cms\Models\BaleList;
use Bale\Cms\Services\TenantManager;
use Bale\Pelatihan\Models\CitizenUser;
use Bale\Pelatihan\Models\NotificationLog;
use Bale\Pelatihan\Models\Program;
use Bale\Pelatihan\Models\Setting;
use Bale\Pelatihan\Notifications\PelatihanProgramPublished;
use Bale\Pelatihan\Support\NotificationTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Kirim notifikasi satu program ke satu peserta.
 *
 * Satu peserta = satu job supaya retry dan timeout tidak menyeret peserta
 * lain. `tries` > 1 hanya mengulang kanal yang belum `sent` (idempotensi),
 * sehingga WhatsApp yang sudah terkirim tidak dikirim dua kali.
 */
class SendProgramNotificationToUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function __construct(
        public string $baleUuid,
        public string $programId,
        public string $userUuid,
    ) {}

    public function handle(): void
    {
        $tenant = BaleList::find($this->baleUuid);

        if (! $tenant) {
            return;
        }

        TenantManager::initializeFromBaleUuid($tenant->id);

        $program = Program::query()->with('cluster')->find($this->programId);

        if (! $program || $program->status !== Program::STATUS_PUBLISHED) {
            return;
        }

        // users.id auto-increment; kunci yang dipakai notifikasi adalah `uuid`.
        $user = CitizenUser::query()->with('profile')->where('uuid', $this->userUuid)->first();

        if (! $user) {
            return;
        }

        $setting = Setting::current();

        // Jangan buat log untuk kanal yang tujuannya memang tidak ada atau
        // sudah dimatikan admin.
        $channels = array_values(array_filter([
            filled($user->email) ? 'email' : null,
            $setting->whatsapp_enabled && filled($user->profile?->whatsapp) ? 'whatsapp' : null,
        ]));

        if ($channels === []) {
            return;
        }

        $logs = NotificationLog::query()
            ->where('program_id', $program->id)
            ->where('user_uuid', $user->uuid)
            ->get()
            ->keyBy('channel');

        $toSend = array_values(array_filter(
            $channels,
            fn (string $channel) => ($logs[$channel]->status ?? null) !== 'sent',
        ));

        if ($toSend === []) {
            return;
        }

        foreach ($toSend as $channel) {
            NotificationLog::query()->updateOrCreate(
                [
                    'program_id' => $program->id,
                    'user_uuid' => $user->uuid,
                    'channel' => $channel,
                ],
                ['status' => 'queued', 'error' => null, 'sent_at' => null],
            );
        }

        try {
            $user->notify(new PelatihanProgramPublished(
                $program->id,
                $user->uuid,
                NotificationTemplate::render($setting->email_subject, $program, $user),
                NotificationTemplate::render($setting->email_body, $program, $user),
                NotificationTemplate::render($setting->wa_body, $program, $user),
                (bool) $setting->whatsapp_enabled,
            ));
        } finally {
            // Baris yang masih `queued` berarti kanalnya tidak pernah melapor
            // hasil (mis. exception sebelum kanal dijalankan).
            NotificationLog::query()
                ->where('program_id', $program->id)
                ->where('user_uuid', $user->uuid)
                ->where('status', 'queued')
                ->update([
                    'status' => 'failed',
                    'error' => __('Kanal tidak melaporkan hasil pengiriman.'),
                ]);
        }
    }
}
