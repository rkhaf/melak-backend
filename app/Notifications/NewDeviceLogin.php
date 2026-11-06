<?php

namespace App\Notifications;

use App\Support\Unsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * FR-01.2.4 — email keamanan saat login dari perangkat baru.
 */
class NewDeviceLogin extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ?string $ip = null,
        public readonly string $device = '',
        public readonly \DateTimeInterface $time = new \DateTime,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Melak — Login baru terdeteksi')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Kami mendeteksi login ke akun Anda dari perangkat baru.')
            ->line('Waktu: '.$this->time->format('d/m/Y H:i:s T'))
            ->line('IP: '.($this->ip ?? '-'))
            ->line('Perangkat: '.mb_substr($this->device, 0, 180))
            ->line('Jika bukan Anda, segera ganti kata sandi Anda.')
            ->action('Buka Aplikasi', config('app.url'))
            ->line(Unsubscribe::line($notifiable, 'security'))
            ->line('Email ini dikirim otomatis, mohon tidak membalas.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ip' => $this->ip,
            'device' => mb_substr($this->device, 0, 180),
            'time' => $this->time->format('c'),
        ];
    }
}
