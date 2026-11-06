<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

/**
 * Link unsubscribe bertanda tangan untuk tiap email notifikasi
 * (FR-06.2 — "unsubscribe link di tiap email").
 *
 * Route web `GET /unsubscribe` (routes/web.php) memverifikasi tanda
 * tangan lalu menonaktifkan via_email untuk tipe tersebut.
 */
class Unsubscribe
{
    public static function url(User $user, string $type): string
    {
        return URL::signedRoute('unsubscribe', [
            'user' => $user->id,
            'type' => $type,
        ]);
    }

    /**
     * Baris penutup berisi link unsubscribe (render sebagai HTML mentah
     * di template markdown mail Laravel).
     */
    public static function line(User $user, string $type): HtmlString
    {
        $url = e(self::url($user, $type));

        return new HtmlString(
            'Tidak ingin menerima email ini lagi? <a href="'.$url.'">Berhenti berlanggana</a>.'
        );
    }
}
