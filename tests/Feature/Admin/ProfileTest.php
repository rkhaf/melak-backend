<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Hash;

class ProfileTest extends AdminTestCase
{
    public function test_admin_can_view_profile_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Profil');
    }

    public function test_admin_can_update_profile(): void
    {
        $admin = $this->actingAsAdmin();

        $this->put(route('admin.profile.update'), [
            'name' => 'Administrator Baru',
            'email' => $admin->email,
            'username' => $admin->username,
            'phone' => '081200000000',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['name' => 'Administrator Baru']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'profile.updated']);
    }

    public function test_update_profile_requires_valid_data(): void
    {
        $this->actingAsAdmin();

        $this->put(route('admin.profile.update'), [
            'name' => '',
            'email' => 'not-an-email',
            'username' => '',
        ])
            ->assertSessionHasErrors(['name', 'email', 'username']);
    }

    public function test_update_profile_rejects_email_already_used_by_other_user(): void
    {
        $this->actingAsAdmin();

        $other = $this->createRegularUser(['email' => 'other@ngingu.test']);

        $this->put(route('admin.profile.update'), [
            'name' => 'Administrator',
            'email' => $other->email,
            'username' => 'something_new',
        ])
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_change_password(): void
    {
        $admin = $this->actingAsAdmin();

        $this->put(route('admin.profile.password'), [
            'current_password' => 'password123',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password-123', $admin->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'profile.password_changed']);
    }

    public function test_change_password_fails_with_wrong_current_password(): void
    {
        $this->actingAsAdmin();

        $this->put(route('admin.profile.password'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertSessionHasErrors('current_password');
    }

    public function test_change_password_requires_confirmation(): void
    {
        $this->actingAsAdmin();

        $this->put(route('admin.profile.password'), [
            'current_password' => 'password123',
            'password' => 'new-password-123',
            'password_confirmation' => 'different',
        ])
            ->assertSessionHasErrors('password');
    }
}
