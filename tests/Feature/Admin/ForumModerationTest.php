<?php

namespace Tests\Feature\Admin;

use App\Models\ForumPost;
use App\Models\ForumReport;
use App\Notifications\PostModerated;
use Illuminate\Support\Facades\Notification;

class ForumModerationTest extends AdminTestCase
{
    public function test_moderation_index_filters_by_status(): void
    {
        $pending = ForumPost::factory()->pending()->create(['title' => 'Post Menunggu Review']);
        ForumPost::factory()->create(['title' => 'Post Sudah Tayang']);

        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('admin.forum.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Post Menunggu Review')
            ->assertDontSee('Post Sudah Tayang');

        $this->actingAs($admin)->get(route('admin.forum.index', ['status' => 'all']))
            ->assertSee('Post Menunggu Review')
            ->assertSee('Post Sudah Tayang');

        $this->assertNotNull($pending->fresh());
    }

    public function test_approve_pending_post_publishes_it_with_audit_and_notification(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $post = ForumPost::factory()->pending()->create();

        $this->actingAs($admin)
            ->post(route('admin.forum.approve', $post))
            ->assertRedirect(route('admin.forum.index', ['status' => 'pending']));

        $this->assertSame('published', $post->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'forum.approved',
            'model_id' => $post->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $post->user_id,
            'type' => 'forum_reply',
            'title' => 'Post Anda disetujui',
        ]);

        Notification::assertSentTo($post->user, PostModerated::class, fn ($n) => $n->decision === 'approved');
    }

    public function test_reject_post_sets_rejected_status_and_notifies_author(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $post = ForumPost::factory()->pending()->create();

        $this->actingAs($admin)
            ->post(route('admin.forum.reject', $post))
            ->assertRedirect(route('admin.forum.index', ['status' => 'pending']));

        $this->assertSame('rejected', $post->fresh()->status);

        $this->assertDatabaseHas('audit_logs', ['action' => 'forum.rejected', 'model_id' => $post->id]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $post->user_id,
            'title' => 'Post Anda ditolak',
        ]);
        Notification::assertSentTo($post->user, PostModerated::class, fn ($n) => $n->decision === 'rejected');
    }

    public function test_pin_and_lock_toggles_record_audit(): void
    {
        $admin = $this->createAdmin();
        $post = ForumPost::factory()->create();

        $this->actingAs($admin)->post(route('admin.forum.pin', $post))->assertRedirect();
        $this->assertTrue($post->fresh()->is_pinned);

        $this->actingAs($admin)->post(route('admin.forum.pin', $post))->assertRedirect();
        $this->assertFalse($post->fresh()->is_pinned);

        $this->actingAs($admin)->post(route('admin.forum.lock', $post))->assertRedirect();
        $this->assertSame('locked', $post->fresh()->status);

        $this->actingAs($admin)->post(route('admin.forum.lock', $post))->assertRedirect();
        $this->assertSame('published', $post->fresh()->status);

        $this->assertDatabaseHas('audit_logs', ['action' => 'forum.pinned', 'model_id' => $post->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'forum.locked', 'model_id' => $post->id]);
    }

    public function test_delete_post_removes_it_and_logs_audit(): void
    {
        $admin = $this->createAdmin();
        $post = ForumPost::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.forum.destroy', $post))
            ->assertRedirect(route('admin.forum.index'));

        $this->assertDatabaseMissing('forum_posts', ['id' => $post->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'forum.deleted']);
    }

    public function test_report_review_and_dismiss_flow(): void
    {
        $admin = $this->createAdmin();
        $post = ForumPost::factory()->create();
        $report = ForumReport::create([
            'reporter_id' => $this->createRegularUser()->id,
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Spam');

        $this->actingAs($admin)->post(route('admin.reports.dismiss', $report))->assertRedirect();
        $this->assertSame('dismissed', $report->fresh()->status);
        $this->assertSame($admin->id, $report->fresh()->reviewed_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.dismissed']);

        $second = ForumReport::create([
            'reporter_id' => $report->reporter_id,
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'Konten Kasar',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->post(route('admin.reports.review', $second))->assertRedirect();
        $this->assertSame('reviewed', $second->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.reviewed']);

        $this->actingAs($admin)->post(route('admin.reports.review', $second))->assertSessionHas('info');
        $this->assertSame('reviewed', $second->fresh()->status);
    }
}
