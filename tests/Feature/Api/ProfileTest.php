<?php

namespace Tests\Feature\Api;

use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/user')
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_me_returns_own_profile(): void
    {
        $user = User::factory()->create([
            'username' => 'petani_lele',
            'bio' => 'Petambak pemula',
            'province' => 'Jawa Barat',
        ]);

        $this->actingAs($user)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.username', 'petani_lele')
            ->assertJsonPath('data.bio', 'Petambak pemula')
            ->assertJsonPath('data.province', 'Jawa Barat')
            ->assertJsonPath('data.reputation_level', 'Pemula')
            ->assertJsonStructure(['data' => ['id', 'name', 'username', 'email', 'avatar_url', 'bio', 'province', 'fish_types', 'reputation_points', 'reputation_level', 'role']]);
    }

    public function test_update_profile_updates_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/user/profile', [
            'name' => 'Budi Santoso',
            'bio' => 'Fokus budidaya lele',
            'province' => 'Jawa Tengah',
            'fish_types' => ['Lele', 'Nila'],
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Budi Santoso')
            ->assertJsonPath('data.bio', 'Fokus budidaya lele')
            ->assertJsonPath('data.fish_types.0', 'Lele');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Budi Santoso', 'province' => 'Jawa Tengah']);

        $this->actingAs($user)->putJson('/api/user/profile', ['name' => 'X'])
            ->assertStatus(422);
    }

    public function test_upload_avatar_stores_file_and_replaces_old(): void
    {
        Storage::fake('public');
        config(['ngingu.upload.disk' => 'public']);

        $user = User::factory()->create();

        $first = $this->actingAs($user)
            ->post('/api/user/avatar', ['avatar' => UploadedFile::fake()->image('foto.png')])
            ->assertOk()
            ->assertJsonStructure(['data' => ['avatar_url']]);

        $avatarUrl = $first->json('data.avatar_url');
        $this->assertNotEmpty($avatarUrl);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'avatar_url' => $avatarUrl]);
        $this->assertNotEmpty(Storage::disk('public')->allFiles(), 'avatar file stored on disk');

        $this->actingAs($user)
            ->post('/api/user/avatar', ['avatar' => UploadedFile::fake()->create('besar.jpg', 3000)])
            ->assertStatus(422);

        $this->actingAs($user)
            ->post('/api/user/avatar', [])
            ->assertStatus(422);
    }

    public function test_public_profile_returns_counts_and_badges(): void
    {
        $user = User::factory()->create(['bio' => 'Petambak', 'reputation_points' => 60, 'reputation_level' => 'Aktif']);
        User::whereKey($user->id)->toBase()->update(['created_at' => now()->subYear()]);

        ForumPost::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'published']);
        ForumPost::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
        ForumComment::factory()->create(['user_id' => $user->id, 'is_best_answer' => true]);

        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', $user->name)
            ->assertJsonPath('data.bio', 'Petambak')
            ->assertJsonPath('data.reputation_points', 60)
            ->assertJsonPath('data.reputation_level', 'Aktif')
            ->assertJsonPath('data.posts_count', 2)
            ->assertJsonPath('data.best_answers_count', 1)
            ->assertJsonPath('data.is_following', false);

        $badgeKeys = array_column($response->json('data.badges'), 'key');
        $this->assertContains('kontributor', $badgeKeys);
        $this->assertContains('jawaban_terbaik', $badgeKeys);
        $this->assertContains('reputasi_aktif', $badgeKeys);
        $this->assertNotNull($response->json('data.joined_at'));
    }

    public function test_public_profile_404_for_unknown_and_inactive_user(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);

        $this->getJson('/api/users/non-existent-id')->assertStatus(404);
        $this->getJson("/api/users/{$inactive->id}")->assertStatus(404);
    }

    public function test_change_password_requires_auth_and_valid_input(): void
    {
        $this->putJson('/api/user/password', [
            'current_password' => 'rahasia123',
            'password' => 'baruan8x',
            'password_confirmation' => 'baruan8x',
        ])->assertStatus(401);

        $user = User::factory()->create(['password' => Hash::make('rahasia123')]);

        // Password baru terlalu pendek.
        $this->actingAs($user)->putJson('/api/user/password', [
            'current_password' => 'rahasia123',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertStatus(422)->assertJsonPath('success', false);

        // Konfirmasi tidak cocok.
        $this->actingAs($user)->putJson('/api/user/password', [
            'current_password' => 'rahasia123',
            'password' => 'baruan8x',
            'password_confirmation' => 'beda1234',
        ])->assertStatus(422);

        // Current password salah.
        $this->actingAs($user)->putJson('/api/user/password', [
            'current_password' => 'salah-banget',
            'password' => 'baruan8x',
            'password_confirmation' => 'baruan8x',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.current_password.0', 'Password saat ini salah.');

        $this->assertTrue(
            Hash::check('rahasia123', $user->fresh()->password),
            'password unchanged after failed attempts'
        );
    }

    public function test_change_password_updates_hash_and_keeps_current_token(): void
    {
        $user = User::factory()->create(['password' => Hash::make('rahasia123')]);
        $current = $user->createToken('perangkat-saat-ini');
        $other = $user->createToken('perangkat-lain');

        $this->withToken($current->plainTextToken)
            ->putJson('/api/user/password', [
                'current_password' => 'rahasia123',
                'password' => 'baruan8x',
                'password_confirmation' => 'baruan8x',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Password berhasil diubah');

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('baruan8x', $fresh->password));
        $this->assertFalse(Hash::check('rahasia123', $fresh->password));

        // Token aktif dipertahankan, token lain dicabut.
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $current->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $other->accessToken->id]);
    }
}
