<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class AdminTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createUser(array $attributes = []): User
    {
        static $counter = 0;

        return User::create(array_merge([
            'name' => 'Pengguna '.($counter++),
            'username' => 'user'.$counter.'_'.uniqid(),
            'email' => 'user'.$counter.'_'.uniqid().'@example.com',
            'password' => 'password123',
            'role' => 'superadmin',
            'is_active' => true,
        ], $attributes));
    }

    protected function createAdmin(array $attributes = []): User
    {
        return $this->createUser(array_merge([
            'name' => 'Administrator',
            'role' => 'superadmin',
        ], $attributes));
    }

    protected function createRegularUser(array $attributes = []): User
    {
        return $this->createUser(array_merge([
            'name' => 'Warga',
            'role' => 'user',
        ], $attributes));
    }

    protected function actingAsAdmin(): User
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        return $admin;
    }
}
