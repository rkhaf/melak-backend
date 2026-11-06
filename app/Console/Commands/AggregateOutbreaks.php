<?php

namespace App\Console\Commands;

use App\Models\OutbreakAlert;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * FR-06.4 — agregasi kasus 7 hari per provinsi → OutbreakAlert.
 *
 * Dijadwalkan harian (routes/console.php). Sebut `php artisan outbreaks:aggregate`.
 */
class AggregateOutbreaks extends Command
{
    protected $signature = 'outbreaks:aggregate {--threshold= : Ambang kasus unik (default config ngingu.outbreak.threshold)}';

    protected $description = 'Agregasi sebaran penyakit 7 hari terakhir per provinsi menjadi alert wabah';

    public function handle(): int
    {
        $threshold = (int) ($this->option('threshold') ?: config('ngingu.outbreak.threshold', 5));
        $windowDays = (int) config('ngingu.outbreak.window_days', 7);

        $rows = DB::table('fish_scans')
            ->join('users', 'users.id', '=', 'fish_scans.user_id')
            ->join('scan_results', 'scan_results.scan_id', '=', 'fish_scans.id')
            ->join('diseases', 'diseases.id', '=', 'scan_results.disease_id')
            ->where('fish_scans.status', 'done')
            ->where('fish_scans.created_at', '>=', now()->subDays($windowDays))
            ->whereNotNull('users.province')
            ->whereNotNull('scan_results.disease_id')
            ->groupBy('users.province', 'scan_results.disease_id', 'diseases.name')
            ->select('users.province as province', 'scan_results.disease_id as disease_id', 'diseases.name as disease_name')
            ->selectRaw('COUNT(DISTINCT fish_scans.user_id) as cases')
            ->get();

        $activeKeys = [];

        foreach ($rows as $row) {
            if ((int) $row->cases < $threshold) {
                continue;
            }

            $activeKeys[] = $row->disease_id.'|'.$row->province;

            $message = sprintf(
                '%d kasus %s terlaporkan di %s dalam %d hari terakhir.',
                (int) $row->cases,
                $row->disease_name,
                $row->province,
                $windowDays,
            );

            $alert = OutbreakAlert::firstOrNew([
                'disease_id' => $row->disease_id,
                'province' => $row->province,
            ]);

            $isNew = $alert->exists === false;

            $alert->fill([
                'cases_7d' => (int) $row->cases,
                'threshold' => $threshold,
                'message' => $message,
                'status' => 'active',
            ])->save();

            if ($isNew) {
                $this->notifyProvince($row->province, (int) $row->disease_id, (string) $row->disease_name, (int) $row->cases, $row->province);
            }

            $this->info($message);
        }

        OutbreakAlert::where('status', 'active')
            ->get()
            ->reject(fn (OutbreakAlert $alert) => in_array($alert->disease_id.'|'.$alert->province, $activeKeys, true))
            ->each(fn (OutbreakAlert $alert) => $alert->update(['status' => 'expired']));

        return self::SUCCESS;
    }

    private function notifyProvince(string $province, int $diseaseId, string $diseaseName, int $cases, string $region): void
    {
        User::where('province', $province)
            ->where('is_active', true)
            ->lazy(200)
            ->each(function (User $user) use ($diseaseId, $diseaseName, $cases, $region) {
                Notifier::send(
                    $user,
                    'outbreak',
                    'Alert wabah di '.$region,
                    sprintf('%d kasus %s terlaporkan di %s dalam 7 hari terakhir.', $cases, $diseaseName, $region),
                    ['disease_id' => $diseaseId, 'province' => $region, 'cases_7d' => $cases],
                );
            });
    }
}
