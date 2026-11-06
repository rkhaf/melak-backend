<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Notifications\UserBanned;
use Illuminate\Support\Facades\Notification;

class UserBanTest extends AdminTestCase
{
    public function test_ban_disables_account_with_reason_duration_audit_and_notification(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $target = $this->createRegularUser(['email' => 'warga@example.com']);

        $this->actingAs($admin)->post(route('admin.users.ban', $target), [
            'reason' => 'Posting konten terlarang',
            'duration_days' => 7,
        ])->assertRedirect(route('admin.users.index'));

        $target->refresh();

        $this->assertSame(0, $target->is_active);
        $this->assertNotNull($target->banned_at);
        $this->assertSame('Posting konten terlarang', $target->ban_reason);
        $this->assertTrue($target->ban_expires_at->isFuture());
        $this->assertTrue($target->isBanned());

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'user.banned',
            'model_id' => $target->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $target->id,
            'type' => 'security',
            'title' => 'Akun dinonaktifkan',
        ]);

        Notification::assertSentTo($target, UserBanned::class, fn ($n) => $n->reason === 'Posting konten terlarang');
    }

    public function test_ban_without_duration_is_permanent(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createRegularUser();

        $this->actingAs($admin)->post(route('admin.users.ban', $target), [
            'reason' => 'Pelanggaran berat',
        ])->assertRedirect(route('admin.users.index'));

        $target->refresh();

        $this->assertNull($target->ban_expires_at);
        $this->assertTrue($target->isBanned());
        $this->assertSame(0, $target->is_active);
    }

    public function test_ban_requires_reason(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createRegularUser();

        $this->actingAs($admin)
            ->post(route('admin.users.ban', $target), [])
            ->assertSessionHasErrors('reason');

        $this->assertSame(1, $target->fresh()->is_active);
    }

    public function test_admin_cannot_ban_own_account(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('admin.users.ban', $admin), ['reason' => 'tes'])
            ->assertSessionHasErrors('ban');

        $this->assertSame(1, $admin->fresh()->is_active);
    }

    public function test_unban_reactivates_account_and_clears_ban_fields(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $target = $this->createRegularUser([
            'is_active' => false,
            'banned_at' => now(),
            'ban_reason' => 'Alasan lama',
            'ban_expires_at' => now()->addDay(),
        ]);

        $this->actingAs($admin)->post(route('admin.users.unban', $target))
            ->assertRedirect(route('admin.users.index'));

        $target->refresh();

        $this->assertSame(1, $target->is_active);
        $this->assertNull($target->banned_at);
        $this->assertNull($target->ban_reason);
        $this->assertNull($target->ban_expires_at);
        $this->assertFalse($target->isBanned());

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.unbanned', 'model_id' => $target->id]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $target->id,
            'type' => 'security',
            'title' => 'Akun diaktifkan kembali',
        ]);
    }

    public function test_expired_ban_no_longer_counts_as_banned(): void
    {
        $target = $this->createRegularUser([
            'banned_at' => now()->subDays(10),
            'ban_expires_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $this->assertFalse($target->isBanned());
    }

    public function test_user_index_filters_by_join_date(): void
    {
        $old = $this->createUser(['name' => 'User Lama']);
        User::whereKey($old->id)->toBase()->update(['created_at' => now()->subDays(40)]);
        $new = $this->createUser(['name' => 'User Baru']);

        $admin = $this->actingAsAdmin();

        $this->actingAs($admin)->get(route('admin.users.index', [
            'from' => now()->subDays(10)->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
        ]))
            ->assertOk()
            ->assertSee('User Baru')
            ->assertDontSee('User Lama');

        $this->assertNotNull($new->fresh());
    }
}
