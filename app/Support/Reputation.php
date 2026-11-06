<?php

namespace App\Support;

use App\Models\ReputationLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Sistem reputasi (FR-05.4.5): Pemula → Aktif → Ahli → Legenda.
 */
class Reputation
{
    private const LEVELS = [
        'Pemula' => 0,
        'Aktif' => 50,
        'Ahli' => 200,
        'Legenda' => 1000,
    ];

    public static function award(User $user, int $points, string $reason, ?string $refType = null, ?int $refId = null): void
    {
        $user->reputation_points = max(0, (int) $user->reputation_points + $points);
        $user->reputation_level = self::levelFor($user->reputation_points);
        $user->save();

        ReputationLog::create([
            'user_id' => $user->id,
            'points' => $points,
            'reason' => $reason,
            'ref_type' => $refType,
            'ref_id' => $refId,
        ]);

        Cache::forget('user:reputation:'.$user->id);
    }

    public static function levelFor(int $points): string
    {
        $level = 'Pemula';

        foreach (self::LEVELS as $name => $minimum) {
            if ($points >= $minimum) {
                $level = $name;
            }
        }

        return $level;
    }
}
