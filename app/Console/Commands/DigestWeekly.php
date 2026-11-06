<?php

namespace App\Console\Commands;

use App\Models\FishScan;
use App\Models\OutbreakAlert;
use App\Models\User;
use App\Models\UserPond;
use App\Notifications\WeeklyDigest;
use App\Support\Notifier;
use Illuminate\Console\Command;

/**
 * FR-06.2 — ringkasan mingguan aktivitas pengguna, dijadwalkan tiap Senin
 * 07.00 WIB (routes/console.php). Sebut `php artisan digest:weekly`.
 *
 * Hanya pengguna aktif dengan aktivitas 7 hari terakhir (scan atau alert
 * wabah aktif di provinsinya) yang dikirimi ringkasan. Kanal email mengikuti
 * NotificationSetting tipe `weekly` (via Notifier).
 */
class DigestWeekly extends Command
{
    protected $signature = 'digest:weekly';

    protected $description = 'Kirim ringkasan mingguan (scan, kolam, wabah) ke pengguna aktif';

    public function handle(): int
    {
        $since = now()->subDays(7);
        $period = $since->format('d M Y').' - '.now()->format('d M Y');
        $sent = 0;

        User::where('is_active', true)->lazy(200)->each(function (User $user) use ($since, $period, &$sent) {
            $scans = FishScan::where('user_id', $user->id)
                ->where('created_at', '>=', $since)
                ->count();

            $outbreaks = $user->province
                ? OutbreakAlert::where('province', $user->province)->where('status', 'active')->count()
                : 0;

            if ($scans === 0 && $outbreaks === 0) {
                return;
            }

            $ponds = UserPond::where('user_id', $user->id)->count();

            Notifier::send(
                $user,
                'weekly',
                'Ringkasan mingguan '.$period,
                sprintf(
                    'Minggu ini Anda membuat %d scan; %d kolam terdaftar; %d alert wabah aktif di wilayah Anda.',
                    $scans,
                    $ponds,
                    $outbreaks,
                ),
                ['period' => $period, 'scans' => $scans, 'ponds' => $ponds, 'outbreaks' => $outbreaks],
                fn () => $user->notify(new WeeklyDigest($period, $scans, $ponds, $outbreaks)),
            );

            $sent++;
        });

        $this->info("Ringkasan mingguan diproses untuk {$sent} pengguna.");

        return self::SUCCESS;
    }
}
