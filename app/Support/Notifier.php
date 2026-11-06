<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Pengiriman notifikasi in-app + email sesuai preferensi pengguna
 * (FR-06.6, API.md §8.4 / §8.5).
 *
 * Channel in-app ditulis langsung ke tabel `notifications` (dipoll klien
 * lewat GET /api/notifications — tanpa WebSocket, API.md §9.2).
 */
class Notifier
{
    public static function send(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = [],
        ?Closure $mail = null,
    ): void {
        $setting = NotificationSetting::firstOrCreate(
            ['user_id' => $user->id, 'type' => $type],
            ['via_push' => true, 'via_email' => true, 'via_inapp' => true]
        );

        if ($setting->via_inapp) {
            Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data !== [] ? $data : null,
                'read_at' => null,
            ]);
        }

        if ($setting->via_email && $mail !== null) {
            try {
                $mail($user);
            } catch (\Throwable $e) {
                // Kegagalan SMTP (mis. host mail tidak ada di production) tidak boleh
                // menggagalkan notifikasi in-app apalagi request yang memicunya.
                Log::warning('Notifikasi email gagal dikirim', [
                    'type' => $type,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
