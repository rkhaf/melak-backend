<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FishScan;
use App\Models\ForumFollow;
use App\Models\ForumPost;
use App\Models\ScanResult;
use App\Models\User;
use App\Models\UserPond;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dashboard personal (API.md §7.1, FR-07).
 */
class DashboardController extends Controller
{
    use ApiResponse, ForumPresenter, ScanPresenter;

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'period' => 'sometimes|in:7d,30d,90d',
        ]);

        $days = match ($data['period'] ?? '7d') {
            '30d' => 30,
            '90d' => 90,
            default => 7,
        };

        return $this->ok([
            'stat_cards' => $this->statCards($user),
            'recent_scans' => $this->recentScans($user),
            'trending_posts' => $this->trendingPosts(),
            'disease_trend' => $this->diseaseTrend($user, $days),
            'top_disease_this_week' => $this->topDiseaseThisWeek($user),
            'activity_feed' => $this->activityFeed($user),
        ]);
    }

    private function statCards(User $user): array
    {
        $scanIds = FishScan::where('user_id', $user->id)->where('status', 'done')->select('id');

        return [
            'total_scans' => FishScan::where('user_id', $user->id)->count(),
            'diseases_detected' => ScanResult::whereIn('scan_id', $scanIds)
                ->whereNotNull('disease_id')
                ->distinct()
                ->count('disease_id'),
            'active_ponds' => UserPond::where('user_id', $user->id)->count(),
            'questions_answered' => ForumPost::where('user_id', $user->id)
                ->where('type', 'question')
                ->where('has_best_answer', true)
                ->count(),
        ];
    }

    private function recentScans(User $user): array
    {
        return FishScan::with(['result.disease', 'images', 'pond:id,name'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (FishScan $scan) => $this->scanSummary($scan))
            ->all();
    }

    private function trendingPosts(): array
    {
        return ForumPost::with(['user:id,name,avatar_url,reputation_level', 'category:id,name,slug,icon', 'tags:id,slug', 'scan.result.disease:id,name'])
            ->where('status', 'published')
            ->orderByRaw('(votes_count + comments_count + views_count) DESC')
            ->take(5)
            ->get()
            ->map(fn (ForumPost $post) => $this->postPayload($post))
            ->all();
    }

    private function diseaseTrend(User $user, int $days): array
    {
        $start = now()->startOfDay()->subDays($days - 1);

        $scans = FishScan::where('user_id', $user->id)
            ->where('status', 'done')
            ->where('created_at', '>=', $start)
            ->get(['id', 'created_at']);

        $counts = $scans->countBy(fn (FishScan $scan) => $scan->created_at->toDateString());

        $trend = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $trend[] = ['date' => $date, 'count' => (int) ($counts[$date] ?? 0)];
        }

        return $trend;
    }

    private function topDiseaseThisWeek(User $user): ?array
    {
        $rows = ScanResult::query()
            ->whereIn('scan_id', fn ($q) => $q
                ->select('id')
                ->from('fish_scans')
                ->where('user_id', $user->id)
                ->where('status', 'done')
                ->where('created_at', '>=', now()->subDays(7)))
            ->whereNotNull('disease_id')
            ->with('disease:id,name')
            ->get();

        $counts = $rows->countBy('disease_id')->sortDesc();

        if ($counts->isEmpty()) {
            return null;
        }

        $diseaseId = $counts->keys()->first();
        $result = $rows->firstWhere('disease_id', $diseaseId);

        if (! $result?->disease) {
            return null;
        }

        return [
            'id' => $result->disease->id,
            'name' => $result->disease->name,
            'count' => (int) $counts->first(),
        ];
    }

    private function activityFeed(User $user): array
    {
        $scans = FishScan::with('result.disease')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (FishScan $scan) => [
                'type' => 'scan',
                'title' => $scan->result?->disease?->name
                    ? 'Scan mendeteksi '.$scan->result->disease->name
                    : 'Scan ikan baru',
                'created_at' => $scan->created_at?->toIso8601String(),
            ]);

        $followedPostIds = ForumFollow::where('user_id', $user->id)->pluck('post_id');

        $posts = ForumPost::with('user:id,name')
            ->whereIn('id', $followedPostIds)
            ->where('status', 'published')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (ForumPost $post) => [
                'type' => 'forum',
                'title' => 'Post forum baru: '.$post->title,
                'created_at' => $post->created_at?->toIso8601String(),
            ]);

        return collect(array_merge($scans->all(), $posts->all()))
            ->sortByDesc('created_at')
            ->values()
            ->take(10)
            ->all();
    }
}
