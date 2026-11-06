<?php

namespace Tests\Feature\Api;

use App\Models\ForumCategory;
use App\Models\ForumComment;
use App\Models\ForumFollow;
use App\Models\ForumPost;
use App\Models\User;
use App\Notifications\ForumMention;
use App\Notifications\ForumReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForumTest extends TestCase
{
    use RefreshDatabase;

    private function author(): User
    {
        return $this->establishedUser(['username' => 'penulis']);
    }

    private function establishedUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        User::whereKey($user->id)->toBase()->update(['created_at' => now()->subDays(30)]);

        return $user->refresh();
    }

    public function test_categories_endpoint_returns_category_list(): void
    {
        ForumCategory::factory()->count(2)->create();

        $this->getJson('/api/forum/categories')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [['id', 'name', 'slug', 'icon', 'description', 'post_count']]]);
    }

    public function test_post_list_filters_and_sorts(): void
    {
        $author = $this->author();
        $category = ForumCategory::factory()->create(['slug' => 'pengobatan']);

        $older = ForumPost::factory()->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
            'type' => 'question',
            'title' => 'Ikan putih di sirip',
            'content_html' => '<p>Bintik putih menular</p>',
        ]);
        ForumPost::whereKey($older->id)->toBase()->update(['created_at' => now()->subMinutes(30)]);

        $newer = ForumPost::factory()->discussion()->create(['title' => 'Diskusi pakan']);
        ForumPost::whereKey($newer->id)->toBase()->update(['created_at' => now()]);

        $this->getJson('/api/forum/posts')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/forum/posts?type=question')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Ikan putih di sirip');

        $this->getJson('/api/forum/posts?category=pengobatan')
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/forum/posts?q=menular')
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/forum/posts?sort=terbaru')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Diskusi pakan');
    }

    public function test_pending_post_is_hidden_from_guests_but_visible_to_author(): void
    {
        $newbie = User::factory()->create();

        $pending = ForumPost::factory()->pending()->create(['user_id' => $newbie->id]);

        $this->getJson('/api/forum/posts')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->getJson("/api/forum/posts/{$pending->id}")->assertStatus(404);

        $this->actingAs($newbie)->getJson('/api/forum/posts')
            ->assertJsonPath('meta.total', 1);

        $this->actingAs($newbie)->getJson("/api/forum/posts/{$pending->id}")->assertOk();
    }

    public function test_store_post_is_published_immediately_for_brand_new_account(): void
    {
        // Tidak ada pramoderasi berdasarkan usia akun (FR-05.5) — post akun
        // baru (bahkan baru dibuat) langsung tayang.
        $newbie = User::factory()->create(['created_at' => now()]);
        $category = ForumCategory::factory()->create();

        $response = $this->actingAs($newbie)->postJson('/api/forum/posts', [
            'type' => 'question',
            'title' => 'Bagaimana mengobati kembung?',
            'content_html' => '<p>Ikan lele saya kembung sejak kemarin.</p>',
            'category_id' => $category->id,
            'tags' => ['lele', 'kembung'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.title', 'Bagaimana mengobati kembung?')
            ->assertJsonStructure(['data' => ['id', 'type', 'title', 'excerpt', 'category', 'author', 'tags', 'votes_count', 'comments_count', 'views_count', 'status', 'created_at']]);

        $this->assertCount(2, $response->json('data.tags'));
        $this->assertDatabaseHas('forum_post_tags', ['post_id' => $response->json('data.id')]);
        $this->assertSame(2, (int) $newbie->fresh()->reputation_points, 'published post awards reputation immediately');
    }

    public function test_store_post_published_for_established_account_and_awards_reputation(): void
    {
        $author = $this->author();
        $category = ForumCategory::factory()->create();

        $this->actingAs($author)->postJson('/api/forum/posts', [
            'type' => 'discussion',
            'title' => 'Tips menjaga kolam tetap jernih',
            'content_html' => '<p>Gunakan filter biofilter yang cukup.</p>',
            'category_id' => $category->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'published');

        $this->assertSame(2, (int) $author->fresh()->reputation_points);
        $this->assertDatabaseHas('forum_categories', ['id' => $category->id, 'post_count' => 1]);
    }

    public function test_store_post_blocked_word_goes_to_pending(): void
    {
        // Kata terblokir tetap jadi satu-satunya alasan post ditahan —
        // berlaku juga untuk akun baru.
        config(['ngingu.forum.blocked_words' => ['judol']]);
        $author = User::factory()->create(['created_at' => now()]);

        $this->actingAs($author)->postJson('/api/forum/posts', [
            'type' => 'discussion',
            'title' => 'Promo judol terbaru',
            'content_html' => '<p>Konten judol.</p>',
            'category_id' => ForumCategory::factory()->create()->id,
        ])->assertCreated()->assertJsonPath('data.status', 'pending');
    }

    public function test_store_post_requires_authentication(): void
    {
        $this->postJson('/api/forum/posts', [])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_show_post_increments_views_once_per_viewer(): void
    {
        $author = $this->author();
        $post = ForumPost::factory()->create(['user_id' => $author->id, 'views_count' => 0]);

        $this->getJson("/api/forum/posts/{$post->id}")->assertOk();
        $this->assertSame(1, (int) $post->fresh()->views_count);

        $this->getJson("/api/forum/posts/{$post->id}")->assertOk();
        $this->assertSame(1, (int) $post->fresh()->views_count);
    }

    public function test_update_post_is_author_only(): void
    {
        $author = $this->author();
        $other = $this->establishedUser();
        $post = ForumPost::factory()->create(['user_id' => $author->id]);

        $this->actingAs($other)->putJson("/api/forum/posts/{$post->id}", ['title' => 'Diambil alih'])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->actingAs($author)->putJson("/api/forum/posts/{$post->id}", ['title' => 'Judul barunya jelas'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Judul barunya jelas');

        $this->assertSame('Judul barunya jelas', $post->fresh()->title);
    }

    public function test_delete_post_allowed_for_author_and_admin(): void
    {
        $author = $this->author();
        $other = $this->establishedUser();
        $admin = $this->establishedUser(['role' => 'admin']);
        $post = ForumPost::factory()->create(['user_id' => $author->id]);

        $this->actingAs($other)->deleteJson("/api/forum/posts/{$post->id}")->assertStatus(403);

        $this->actingAs($admin)->deleteJson("/api/forum/posts/{$post->id}")->assertNoContent();

        $this->assertDatabaseMissing('forum_posts', ['id' => $post->id]);
    }

    public function test_vote_post_updates_count_and_reputation(): void
    {
        $author = $this->author();
        $voter = $this->establishedUser();
        $post = ForumPost::factory()->create(['user_id' => $author->id, 'votes_count' => 0]);

        $this->actingAs($voter)->postJson("/api/forum/posts/{$post->id}/vote", ['value' => 1])
            ->assertOk()
            ->assertJsonPath('data.votes_count', 1)
            ->assertJsonPath('data.my_vote', 1);

        $this->assertSame(2, (int) $author->fresh()->reputation_points);

        $this->actingAs($voter)->postJson("/api/forum/posts/{$post->id}/vote", ['value' => 1])
            ->assertJsonPath('data.votes_count', 1);
        $this->assertSame(2, (int) $author->fresh()->reputation_points, 'no double reward');

        $this->actingAs($voter)->postJson("/api/forum/posts/{$post->id}/vote", ['value' => 0])
            ->assertJsonPath('data.votes_count', 0);

        $this->actingAs($author)->postJson("/api/forum/posts/{$post->id}/vote", ['value' => 1])
            ->assertJsonPath('data.votes_count', 1);
        $this->assertSame(2, (int) $author->fresh()->reputation_points, 'self vote not rewarded');
    }

    public function test_bookmark_and_follow_toggle(): void
    {
        $author = $this->author();
        $user = $this->establishedUser();
        $post = ForumPost::factory()->create(['user_id' => $author->id]);

        $this->actingAs($user)->postJson("/api/forum/posts/{$post->id}/bookmark")
            ->assertOk()->assertJsonPath('data.is_bookmarked', true);
        $this->actingAs($user)->postJson("/api/forum/posts/{$post->id}/bookmark")
            ->assertJsonPath('data.is_bookmarked', false);

        $this->actingAs($user)->postJson("/api/forum/posts/{$post->id}/follow")
            ->assertOk()->assertJsonPath('data.is_following', true);
        $this->actingAs($user)->postJson("/api/forum/posts/{$post->id}/follow")
            ->assertJsonPath('data.is_following', false);
    }

    public function test_report_post_enters_moderation_queue(): void
    {
        $author = $this->author();
        $user = $this->establishedUser();
        $post = ForumPost::factory()->create(['user_id' => $author->id]);

        $this->actingAs($user)->postJson("/api/forum/posts/{$post->id}/report", ['reason' => 'tidak_valid'])
            ->assertStatus(422);

        $this->actingAs($user)->postJson("/api/forum/posts/{$post->id}/report", ['reason' => 'spam', 'notes' => 'Banyak iklan'])
            ->assertCreated()
            ->assertJsonPath('data.reported', true);

        $this->assertDatabaseHas('forum_reports', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reporter_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_comment_lifecycle_with_depth_limit_and_notifications(): void
    {
        Notification::fake();

        $author = $this->author();
        $author->update(['username' => 'penulis']);
        $commenter = $this->establishedUser(['username' => 'komentator']);
        $follower = $this->establishedUser(['username' => 'pengikut']);
        $post = ForumPost::factory()->create(['user_id' => $author->id]);

        ForumFollow::create(['user_id' => $follower->id, 'post_id' => $post->id]);

        $root = $this->actingAs($commenter)->postJson("/api/forum/posts/{$post->id}/comments", [
            'content_html' => '<p>Saya juga pernah mengalami ini @penulis</p>',
        ]);

        $root->assertCreated()
            ->assertJsonPath('data.content_html', '<p>Saya juga pernah mengalami ini @penulis</p>')
            ->assertJsonPath('data.mention_users.0', 'penulis');

        $rootId = $root->json('data.id');
        $this->assertSame(1, (int) $post->fresh()->comments_count);

        $reply = $this->actingAs($author)->postJson("/api/forum/posts/{$post->id}/comments", [
            'parent_id' => $rootId,
            'content_html' => '<p>Terima kasih sudah sharing.</p>',
        ]);

        $reply->assertCreated();

        $this->actingAs($author)->postJson("/api/forum/posts/{$post->id}/comments", [
            'parent_id' => $reply->json('data.id'),
            'content_html' => '<p>Level tiga tidak boleh.</p>',
            'image_urls' => ['https://cdn.melak.id/a.jpg'],
        ])->assertStatus(422)->assertJsonPath('message', 'Balasan maksimal 2 level.');

        Notification::assertSentTo($author, ForumReply::class);
        Notification::assertSentTo($follower, ForumReply::class);
        Notification::assertSentTo($author, ForumMention::class);
    }

    public function test_comment_update_and_delete_permissions(): void
    {
        $admin = $this->establishedUser(['role' => 'admin']);
        $other = $this->establishedUser();
        $post = ForumPost::factory()->create(['comments_count' => 1]);
        $comment = ForumComment::factory()->create(['post_id' => $post->id, 'user_id' => $other->id]);

        $this->actingAs($admin)->putJson("/api/forum/comments/{$comment->id}", ['content_html' => '<p>Diubah orang lain</p>'])
            ->assertStatus(403);

        $this->actingAs($other)->putJson("/api/forum/comments/{$comment->id}", ['content_html' => '<p>Diperbarui penulisnya</p>'])
            ->assertOk()
            ->assertJsonPath('data.content_html', '<p>Diperbarui penulisnya</p>');

        $stranger = $this->establishedUser();
        $this->actingAs($stranger)->deleteJson("/api/forum/comments/{$comment->id}")->assertStatus(403);

        $this->actingAs($admin)->deleteJson("/api/forum/comments/{$comment->id}")->assertNoContent();
        $this->assertSame(0, (int) $post->fresh()->comments_count);
    }

    public function test_comment_vote(): void
    {
        $author = $this->author();
        $voter = $this->establishedUser();
        $post = ForumPost::factory()->create(['user_id' => $author->id]);
        $comment = ForumComment::factory()->create(['post_id' => $post->id, 'user_id' => $author->id]);

        $this->actingAs($voter)->postJson("/api/forum/comments/{$comment->id}/vote", ['value' => 1])
            ->assertOk()
            ->assertJsonPath('data.votes_count', 1)
            ->assertJsonPath('data.my_vote', 1);

        $this->assertSame(1, (int) $author->fresh()->reputation_points);
    }

    public function test_mark_best_answer_only_by_post_author(): void
    {
        $author = $this->author();
        $commenter = $this->establishedUser();
        $post = ForumPost::factory()->create(['user_id' => $author->id]);
        $comment = ForumComment::factory()->create(['post_id' => $post->id, 'user_id' => $commenter->id]);

        $this->actingAs($commenter)->postJson("/api/forum/comments/{$comment->id}/best")
            ->assertStatus(403)
            ->assertJsonPath('message', 'Hanya penulis post yang dapat menandai jawaban terbaik.');

        $this->actingAs($author)->postJson("/api/forum/comments/{$comment->id}/best")
            ->assertOk()
            ->assertJsonPath('data.is_best_answer', true);

        $this->assertTrue((bool) $comment->fresh()->is_best_answer);
        $this->assertTrue((bool) $post->fresh()->has_best_answer);
        $this->assertSame(10, (int) $commenter->fresh()->reputation_points);

        $this->actingAs($author)->postJson("/api/forum/comments/{$comment->id}/best")
            ->assertJsonPath('data.is_best_answer', false);

        $this->assertFalse((bool) $post->fresh()->has_best_answer);
    }

    public function test_comment_store_requires_authentication(): void
    {
        $post = ForumPost::factory()->create();

        $this->postJson("/api/forum/posts/{$post->id}/comments", ['content_html' => '<p>Halo</p>'])
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }
}
