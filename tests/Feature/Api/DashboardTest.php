<?php

namespace Tests\Feature\Api;

use App\Models\Disease;
use App\Models\FishScan;
use App\Models\ForumFollow;
use App\Models\ForumPost;
use App\Models\ScanResult;
use App\Models\User;
use App\Models\UserPond;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function establishedUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        User::whereKey($user->id)->toBase()->update(['created_at' => now()->subDays(30)]);

        return $user->refresh();
    }

    private function doneScan(User $user, ?Disease $disease, array $overrides = []): FishScan
    {
        $scan = FishScan::factory()->create([
            'user_id' => $user->id,
            'status' => 'done',
            ...$overrides,
        ]);

        if ($disease) {
            ScanResult::create([
                'scan_id' => $scan->id,
                'disease_id' => $disease->id,
                'confidence_score' => 91.5,
                'top_predictions' => [],
                'low_confidence_warning' => false,
                'model_version' => 'fake-v1',
            ]);
        }

        return $scan;
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_dashboard_returns_stat_cards_recent_scans_and_trending_posts(): void
    {
        $user = $this->establishedUser();
        $ich = Disease::factory()->create(['name' => 'White Spot (Ich)', 'slug' => 'white-spot-ich']);
        $columnaris = Disease::factory()->create(['name' => 'Columnaris', 'slug' => 'columnaris']);

        $this->doneScan($user, $ich);
        $this->doneScan($user, $ich);
        $this->doneScan($user, $columnaris);
        UserPond::create(['user_id' => $user->id, 'name' => 'Kolam A', 'health_score' => 100]);
        ForumPost::factory()->create(['user_id' => $user->id, 'type' => 'question', 'has_best_answer' => true]);

        $response = $this->actingAs($user)->getJson('/api/dashboard?period=7d');

        $response->assertOk()
            ->assertJsonPath('data.stat_cards.total_scans', 3)
            ->assertJsonPath('data.stat_cards.diseases_detected', 2)
            ->assertJsonPath('data.stat_cards.active_ponds', 1)
            ->assertJsonPath('data.stat_cards.questions_answered', 1)
            ->assertJsonStructure(['data' => ['stat_cards', 'recent_scans', 'trending_posts', 'disease_trend', 'top_disease_this_week', 'activity_feed']]);

        $this->assertCount(3, $response->json('data.recent_scans'));

        $diseases = array_filter(array_column($response->json('data.recent_scans'), 'disease'), fn ($d) => $d !== null);
        $names = array_unique(array_column($diseases, 'name'));
        sort($names);
        $this->assertSame(['Columnaris', 'White Spot (Ich)'], $names);
        $this->assertCount(7, $response->json('data.disease_trend'));
        $this->assertSame(now()->toDateString(), $response->json('data.disease_trend.6.date'));
        $this->assertSame(3, (int) $response->json('data.disease_trend.6.count'));
        $this->assertSame('White Spot (Ich)', $response->json('data.top_disease_this_week.name'));
        $this->assertSame(2, (int) $response->json('data.top_disease_this_week.count'));
    }

    public function test_dashboard_trend_supports_period_and_zero_fills_days(): void
    {
        $user = $this->establishedUser();
        $this->doneScan($user, null);

        $response = $this->actingAs($user)->getJson('/api/dashboard?period=30d');

        $response->assertOk();
        $trend = $response->json('data.disease_trend');
        $this->assertCount(30, $trend);
        $this->assertSame(now()->subDays(29)->toDateString(), $trend[0]['date']);
        $this->assertSame(0, (int) $trend[0]['count']);
        $this->assertSame(now()->toDateString(), $trend[29]['date']);
        $this->assertSame(1, (int) $trend[29]['count']);

        $this->actingAs($user)->getJson('/api/dashboard?period=14d')->assertStatus(422);
    }

    public function test_dashboard_trending_posts_and_activity_feed(): void
    {
        $user = $this->establishedUser();
        $follower = $this->establishedUser();

        $hot = ForumPost::factory()->create(['title' => 'Post panas', 'votes_count' => 9, 'comments_count' => 5, 'views_count' => 0]);
        ForumPost::factory()->create(['title' => 'Post sepi', 'votes_count' => 1, 'comments_count' => 0, 'views_count' => 0]);
        ForumFollow::create(['user_id' => $user->id, 'post_id' => $hot->id]);

        $response = $this->actingAs($user)->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.trending_posts.0.title', 'Post panas');

        $feed = $response->json('data.activity_feed');
        $this->assertSame('forum', $feed[0]['type']);
        $this->assertStringContainsString('Post panas', $feed[0]['title']);

        $this->assertSame(0, count(array_filter($feed, fn ($item) => $item['type'] === 'scan')));
    }

    public function test_dashboard_of_other_user_is_isolated(): void
    {
        $owner = $this->establishedUser();
        $viewer = $this->establishedUser();
        $this->doneScan($owner, null);

        $response = $this->actingAs($viewer)->getJson('/api/dashboard');
        $response->assertOk()->assertJsonPath('data.stat_cards.total_scans', 0);
    }
}
