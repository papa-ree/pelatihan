<?php

namespace Bale\Pelatihan\Listeners;

use Bale\Pelatihan\Models\NotificationLog;
use Bale\Pelatihan\Notifications\PelatihanProgramPublished;
use Illuminate\Notifications\Events\NotificationFailed;

/**
 * Kanal melaporkan kegagalan lewat event, bukan exception (lihat WaraChannel).
 * Baris log kanal tersebut ditandai `failed`; baris `sent` tidak disentuh.
 */
class MarkProgramNotificationFailed
{
    public function handle(NotificationFailed $event): void
    {
        if (! $event->notification instanceof PelatihanProgramPublished) {
            return;
        }

        $channel = $this->channel($event->channel);

        if ($channel === null) {
            return;
        }

        $reason = (string) ($event->data['reason'] ?? $event->data['message'] ?? __('Kegagalan pengiriman.'));

        if (isset($event->data['retry_after'])) {
            $reason .= ' (cooldown '.$event->data['retry_after'].'s)';
        }

        NotificationLog::query()
            ->where('program_id', $event->notification->programId)
            ->where('user_uuid', $event->notification->userUuid)
            ->where('channel', $channel)
            ->where('status', 'queued')
            ->update([
                'status' => 'failed',
                'error' => mb_substr($reason, 0, 900),
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
