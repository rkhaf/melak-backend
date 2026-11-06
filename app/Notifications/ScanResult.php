<?php

namespace App\Notifications;

use App\Models\Disease;
use App\Models\FishScan;
use App\Support\Unsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * FR-06.1 — email notifikasi hasil scan selesai.
 *
 * In-app dibuat via App\Support\Notifier; kelas ini hanya kanal email.
 */
class ScanResult extends Notification
{
    use Queueable;

    public function __construct(
        public readonly FishScan $scan,
        public readonly ?Disease $disease = null,
        public readonly float $confidence = 0,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->disease?->name ?? 'Tidak diketahui';

        return (new MailMessage)
            ->subject('Melak — Hasil scan siap')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Prediksi: '.$name.' (confidence '.$this->confidence.'%).')
            ->line('Waktu: '.$this->scan->created_at?->format('d/m/Y H:i'))
            // Halaman web (bukan /api/... yang membalas JSON untuk pembaca email).
            ->action('Lihat Hasil', URL::signedRoute('scans.show', ['id' => $this->scan->id]))
            ->line(Unsubscribe::line($notifiable, 'scan_result'));
    }
}
