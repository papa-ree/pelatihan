<?php

namespace Bale\Pelatihan\Listeners;

use Bale\Pelatihan\Models\NotificationLog;
use Bale\Pelatihan\Notifications\PelatihanProgramPublished;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Menandai baris log yang benar-benar selesai diproses kanal.
 *
 * Hanya baris `queued` yang disentuh: WaraChannel memancarkan
 * `NotificationFailed` lebih dulu saat gagal, jadi baris itu sudah `failed`
 * dan tidak boleh ditimpa `sent`.
 */
class MarkProgramNotificationSent
{
    public function handle(NotificationSent $event): void
    {
        if (! $event->notification instanceof PelatihanProgramPublished) {
            return;
        }

        $channel = $this->channel($event->channel);

        if ($channel === null) {
            return;
        }

        NotificationLog::query()
            ->where('program_id', $event->notification->programId)
            ->where('user_uuid', $event->notification->userUuid)
            ->where('channel', $channel)
            ->where('status', 'queued')
            ->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error' => null,
            ]);
    }

    private function channel(string $channel): ?string
    {
        return match ($channel) {
            'wara', 'whatsapp' => 'whatsapp',
            'mail', 'email' => 'email',
            default => null,
        };
    }
}
