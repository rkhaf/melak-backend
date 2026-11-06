<?php

namespace App\Notifications;

use App\Support\Unsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-09.3.2 — pemberitahuan suspend/ban akun ke pengguna.
 *
 * In-app dibuat via App\Support\Notifier; kelas ini hanya kanal email.
 */
class UserBanned extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $reason,
        public readonly ?string $until = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Melak — Akun Anda dinonaktifkan')
            ->line('Akun Anda dinonaktifkan oleh administrator.')
            ->line('Alasan: '.$this->reason);

        if ($this->until !== null) {
            $mail->line('Berlaku hingga: '.$this->until);
        } else {
            $mail->line('Penonaktifan berlaku sampai diaktifkan kembali oleh administrator.');
        }

        $mail->line(Unsubscribe::line($notifiable, 'security'));

        return $mail;
    }
}
