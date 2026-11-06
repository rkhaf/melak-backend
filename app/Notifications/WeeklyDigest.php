<?php

namespace App\Notifications;

use App\Support\Unsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-06.2 — ringkasan mingguan aktivitas pengguna (cron `digest:weekly`).
 *
 * In-app dibuat via App\Support\Notifier; kelas ini hanya kanal email.
 */
class WeeklyDigest extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $period = '',
        public readonly int $scans = 0,
        public readonly int $gardens = 0,
        public readonly int $outbreaks = 0,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lines = [
            'Ringkasan 7 hari terakhir ('.$this->period.'):',
            $this->scans.' scan tanaman baru',
            $this->gardens.' kebun terdaftar',
        ];

        if ($this->outbreaks > 0) {
            $lines[] = $this->outbreaks.' alert wabah aktif di wilayah Anda';
        }

        return (new MailMessage)
            ->subject('Melak — Ringkasan mingguan '.$this->period)
            ->greeting('Halo, '.$notifiable->name.'!')
            ->lines($lines)
            ->action('Buka Aplikasi', config('app.url'))
            ->line(Unsubscribe::line($notifiable, 'weekly'));
    }
}
