<?php

namespace Tests\Feature\Admin;

use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserManagementTest extends AdminTestCase
{
    public function test_admin_can_view_users_list(): void
    {
        $this->actingAsAdmin();

        $this->createRegularUser(['name' => 'Warga Melak']);

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Warga Melak');
    }

    public function test_admin_can_view_create_and_edit_pages(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.users.create'))->assertOk();

        $user = $this->createRegularUser();

        $this->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee($user->name);
    }

    public function test_admin_can_store_user(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.users.store'), [
            'name' => 'Pengguna Baru',
            'email' => 'baru@ngingu.test',
            'username' => 'baru_ngingu',
            'phone' => '081200000000',
            'role' => 'user',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'baru@ngingu.test', 'role' => 'user', 'is_active' => true]);

        $user = User::where('email', 'baru@ngingu.test')->first();
        $this->assertTrue(Hash::check('password123', $user->password));

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created']);
    }

    public function test_store_user_requires_valid_data(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.users.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'username', 'role', 'password']);
    }

    public function test_store_user_rejects_duplicate_email_and_username(): void
    {
        $this->actingAsAdmin();

        $this->createRegularUser(['email' => 'taken@ngingu.test', 'username' => 'taken_username']);

        $this->post(route('admin.users.store'), [
            'name' => 'Duplikat',
            'email' => 'taken@ngingu.test',
            'username' => 'taken_username',
            'role' => 'user',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertSessionHasErrors(['email', 'username']);
    }

    public function test_admin_can_update_user(): void
    {
        $this->actingAsAdmin();

        $user = $this->createRegularUser(['name' => 'Nama Lama']);

        $this->put(route('admin.users.update', $user), [
            'name' => 'Nama Baru',
            'email' => $user->email,
            'username' => $user->username,
            'phone' => '081200000000',
            'role' => 'user',
            'is_active' => 1,
        ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['name' => 'Nama Baru']);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = $this->actingAsAdmin();

        $this->from(route('admin.users.edit', $admin))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'username' => $admin->username,
                'role' => 'user',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('superadmin', $admin->fresh()->role);
    }

    public function test_admin_can_toggle_user_active_status(): void
    {
        $this->actingAsAdmin();

        $user = $this->createRegularUser();

        $this->post(route('admin.users.toggle', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertSame(0, $user->fresh()->is_active);
    }

    public function test_admin_cannot_toggle_own_account(): void
    {
        $admin = $this->actingAsAdmin();

        $this->from(route('admin.users.edit', $admin))
            ->post(route('admin.users.toggle', $admin))
            ->assertSessionHasErrors('toggle');

        $this->assertSame(1, $admin->fresh()->is_active);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $this->actingAsAdmin();

        $user = $this->createRegularUser();

        $this->from(route('admin.users.edit', $user))
            ->post(route('admin.users.reset-password', $user))
            ->assertRedirect(route('admin.users.edit', $user))
            ->assertSessionHas('info');

        $fresh = $user->fresh();

        $this->assertFalse(Hash::needsRehash($fresh->password));
        $this->assertFalse(Hash::check('password123', $fresh->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.password_reset']);
    }

    public function test_admin_can_update_user_notifications(): void
    {
        $this->actingAsAdmin();

        $user = $this->createRegularUser();

        $this->from(route('admin.users.edit', $user))
            ->post(route('admin.users.notifications', $user), [
                'via_push' => ['scan_result' => 1, 'forum_reply' => 1],
                'via_email' => ['scan_result' => 1, 'forum_reply' => 0],
                'via_inapp' => ['scan_result' => 1],
            ])
            ->assertSessionHas('success');

        $setting = NotificationSetting::where('user_id', $user->id)->where('type', 'scan_result')->first();
        $this->assertNotNull($setting);
        $this->assertTrue($setting->via_push);
        $this->assertTrue($setting->via_email);
        $this->assertTrue($setting->via_inapp);

        $reply = NotificationSetting::where('user_id', $user->id)->where('type', 'forum_reply')->first();
        $this->assertNotNull($reply);
        $this->assertTrue($reply->via_push);
        $this->assertFalse($reply->via_email);
    }

    public function test_update_notifications_creates_setting_when_missing(): void
    {
        $this->actingAsAdmin();

        $user = $this->createRegularUser();

        $this->post(route('admin.users.notifications', $user), [
            'via_push' => ['security' => 1],
            'via_email' => ['security' => 1],
            'via_inapp' => ['security' => 1],
        ])->assertSessionHas('success');

        $this->assertSame(count(Notification::TYPES), NotificationSetting::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('notification_settings', ['user_id' => $user->id, 'type' => 'security']);
    }
}
