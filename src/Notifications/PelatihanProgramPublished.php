<?php

namespace Bale\Pelatihan\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi program pelatihan yang sudah terbit.
 *
 * Tidak mengimplementasikan ShouldQueue: pengantreannya milik
 * SendProgramNotificationJob, sehingga job yang sama bisa mencatat status
 * setiap kanal ke pelatihan_notification_logs.
 */
class PelatihanProgramPublished extends Notification
{
    public function __construct(
        public string $programId,
        public string $userUuid,
        public string $subject,
        public string $emailBody,
        public string $waBody,
        public bool $whatsappEnabled = false,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Hanya kanal yang tujuannya ada; mail tanpa alamat akan melempar
        // exception dan menghabiskan jatah retry peserta itu.
        return array_values(array_filter([
            // WhatsApp lebih dulu supaya kegagalan mail tidak membatalkan kanal WA.
            $this->whatsappEnabled && filled($notifiable->toWara()) ? 'wara' : null,
            filled($notifiable->email) ? 'mail' : null,
        ]));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->lines(preg_split('/\R/', $this->emailBody) ?: []);
    }

    public function toWara($notifiable = null): string
    {
        return $this->waBody;
    }
}
