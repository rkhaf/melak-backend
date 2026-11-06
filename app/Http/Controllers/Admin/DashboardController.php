<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Disease;
use App\Models\PlantScan;
use App\Models\ForumPost;
use App\Models\ForumReport;
use App\Models\MlModel;
use App\Models\ScanResult;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();

        $hasScan = Schema::hasTable('plant_scans');
        $hasForum = Schema::hasTable('forum_posts');
        $hasDisease = Schema::hasTable('diseases');
        $hasModels = Schema::hasTable('ml_models');

        $scansToday = $hasScan ? PlantScan::where('created_at', '>=', $today)->count() : 0;
        $scansYesterday = $hasScan ? PlantScan::where('created_at', '>=', $today->copy()->subDay())
            ->where('created_at', '<', $today)->count() : 0;

        $stats = [
            'total_users' => User::count(),
            'users_today' => User::where('created_at', '>=', $today)->count(),
            'scans_today' => $scansToday,
            'scans_today_pct' => $this->percentChange($scansYesterday, $scansToday),
            'scans_total' => $hasScan ? PlantScan::count() : 0,
            'pending_posts' => $hasForum ? ForumPost::where('status', 'pending')->count() : 0,
            'reported_posts' => $hasForum ? ForumPost::whereHas('reports', fn ($q) => $q->where('status', 'pending'))->count() : 0,
            'total_posts' => $hasForum ? ForumPost::count() : 0,
            'active_diseases' => $hasDisease ? Disease::where('is_published', true)->count() : 0,
            'active_model' => $hasModels ? MlModel::where('is_active', true)->value('version') : '—',
        ];

        $latestPosts = $hasForum
            ? ForumPost::with(['user:id,name', 'category:id,name,slug'])
                ->latest()->take(8)->get(['id', 'title', 'status', 'votes_count', 'comments_count', 'created_at'])
            : collect();

        $topDiseases = [];
        if ($hasScan && $hasDisease) {
            $topDiseases = ScanResult::query()
                ->join('plant_scans', 'plant_scans.id', '=', 'scan_results.scan_id')
                ->join('diseases', 'diseases.id', '=', 'scan_results.disease_id')
                ->where('plant_scans.created_at', '>=', now()->subDays(6)->startOfDay())
                ->selectRaw('diseases.id, diseases.name, diseases.slug, COUNT(*) as total')
                ->groupBy('diseases.id', 'diseases.name', 'diseases.slug')
                ->orderByDesc('total')
                ->limit(5)
                ->get();
        }

        $trend = $hasScan
            ? PlantScan::selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                ->groupBy('date')->orderBy('date')
                ->pluck('total', 'date')
            : collect();

        $chartDates = [];
        $chartTotals = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $chartDates[] = now()->subDays($i)->format('d M');
            $chartTotals[] = $trend[$d] ?? 0;
        }

        return view('admin.dashboard', compact(
            'stats',
            'latestPosts',
            'topDiseases',
            'chartDates',
            'chartTotals'
        ));
    }

    /**
     * FR-09.1.1 — export statistik dashboard ke CSV.
     */
    public function export()
    {
        $today = now()->startOfDay();

        $stats = [
            'Total pengguna' => User::count(),
            'Pengguna baru hari ini' => User::where('created_at', '>=', $today)->count(),
            'Scan hari ini' => PlantScan::where('created_at', '>=', $today)->count(),
            'Total scan' => PlantScan::count(),
            'Total post forum' => ForumPost::count(),
            'Post menunggu moderasi' => ForumPost::where('status', 'pending')->count(),
            'Laporan menunggu review' => ForumReport::where('status', 'pending')->count(),
            'Penyakit terbit' => Disease::where('is_published', true)->count(),
            'Versi model aktif' => MlModel::where('is_active', true)->value('version') ?? '—',
        ];

        $topDiseases = ScanResult::query()
            ->join('plant_scans', 'plant_scans.id', '=', 'scan_results.scan_id')
            ->join('diseases', 'diseases.id', '=', 'scan_results.disease_id')
            ->where('plant_scans.created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('diseases.name as name, COUNT(*) as total')
            ->groupBy('diseases.name')
            ->orderByDesc('total')
            ->get();

        return response()->streamDownload(function () use ($stats, $topDiseases) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Statistik Platform Melak', now()->format('d/m/Y H:i')]);
            fputcsv($out, []);
            fputcsv($out, ['Metrik', 'Nilai']);
            foreach ($stats as $label => $value) {
                fputcsv($out, [$label, $value]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Penyakit Terdeteksi 7 Hari Terakhir', 'Jumlah']);
            foreach ($topDiseases as $row) {
                fputcsv($out, [$row->name, $row->total]);
            }

            fclose($out);
        }, 'statistik-melak-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function percentChange(int $previous, int $current): int
    {
        if ($previous <= 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }
}
