<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Models\User;
use App\Support\ImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Profil pengguna (API.md §7.7 — §7.10, FR-08.3 — FR-08.5).
 */
class ProfileController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/user (API.md §7.7).
     */
    public function me(Request $request): JsonResponse
    {
        return $this->ok($this->selfPayload($request->user()));
    }

    /**
     * PUT /api/user/profile (API.md §7.8, FR-08.3).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'sometimes|string|min:3|max:80',
            'bio' => 'sometimes|nullable|string|max:300',
            'province' => 'sometimes|nullable|string|max:80',
            'fish_types' => 'sometimes|array|max:10',
            'fish_types.*' => 'string|max:30',
        ], [
            'name.min' => 'Nama minimal 3 karakter.',
            'name.max' => 'Nama maksimal 80 karakter.',
            'bio.max' => 'Bio maksimal 300 karakter.',
            'fish_types.max' => 'Maksimal 10 jenis ikan.',
        ]);

        $user->fill($data);
        $user->save();

        return $this->ok($this->selfPayload($user), 'Profil berhasil diperbarui');
    }

    /**
     * PUT /api/user/password — ubah password dari dalam aplikasi.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|max:100|confirmed',
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.max' => 'Password baru maksimal 100 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return $this->fail(
                'Password saat ini salah.',
                422,
                ['current_password' => ['Password saat ini salah.']]
            );
        }

        $user->forceFill(['password' => Hash::make($data['password'])])->save();

        // Cabut token perangkat lain; sesi aktif tetap berlaku.
        $token = $user->currentAccessToken();
        $currentTokenId = $token instanceof PersonalAccessToken
            ? $token->getKey()
            : null;
        $tokens = $user->tokens();
        if ($currentTokenId !== null) {
            $tokens->where('id', '!=', $currentTokenId)->delete();
        } else {
            $tokens->delete();
        }

        return $this->ok(null, 'Password berhasil diubah');
    }

    /**
     * POST /api/user/avatar (API.md §7.9) — multipart.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'avatar' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'avatar.required' => 'Foto profil wajib diunggah.',
            'avatar.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'avatar.max' => 'Ukuran foto profil maksimal 2MB.',
        ]);

        $user = $request->user();
        $storage = app(ImageStorage::class);
        $stored = $storage->storeAvatar($request->file('avatar'), (string) $user->id);

        $oldKey = $user->avatar_url ? $this->keyFromUrl((string) $user->avatar_url) : null;

        $user->avatar_url = $stored['avatar_url'];
        $user->save();

        if ($oldKey && $oldKey !== $stored['s3_key'] && $storage->keyExists($oldKey)) {
            $storage->delete($oldKey);
        }

        return $this->ok(['avatar_url' => $stored['avatar_url']], 'Foto profil berhasil diperbarui');
    }

    /**
     * GET /api/users/{id} (API.md §7.10, FR-08.4).
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = User::whereKey($id)->where('is_active', true)->first();

        if (! $user) {
            return $this->fail('Pengguna tidak ditemukan.', 404);
        }

        return $this->ok($this->publicPayload($user, $request->user()));
    }

    private function selfPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar_url,
            'bio' => $user->bio,
            'province' => $user->province,
            'fish_types' => $user->fish_types ?? [],
            'reputation_points' => (int) $user->reputation_points,
            'reputation_level' => $user->reputation_level,
            'role' => $user->role,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    private function publicPayload(User $profile, ?User $viewer): array
    {
        $postsCount = ForumPost::where('user_id', $profile->id)->where('status', 'published')->count();
        $bestAnswers = ForumComment::where('user_id', $profile->id)->where('is_best_answer', true)->count();

        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'username' => $profile->username,
            'avatar_url' => $profile->avatar_url,
            'bio' => $profile->bio,
            'province' => $profile->province,
            'reputation_points' => (int) $profile->reputation_points,
            'reputation_level' => $profile->reputation_level,
            'badges' => $this->badges($profile, $postsCount, $bestAnswers),
            'posts_count' => $postsCount,
            'best_answers_count' => $bestAnswers,
            'joined_at' => $profile->created_at?->toIso8601String(),
            'is_following' => false,
        ];
    }

    /**
     * Badge diturunkan dari kontribusi & level reputasi (FR-08.4).
     *
     * @return array<int, array{key: string, label: string}>
     */
    private function badges(User $user, int $postsCount, int $bestAnswers): array
    {
        $badges = [];

        if ($postsCount >= 1) {
            $badges[] = ['key' => 'kontributor', 'label' => 'Kontributor Forum'];
        }

        if ($bestAnswers >= 1) {
            $badges[] = ['key' => 'jawaban_terbaik', 'label' => 'Penjawab Terbaik'];
        }

        if ((int) $user->reputation_points >= 50) {
            $badges[] = ['key' => 'reputasi_aktif', 'label' => 'Anggota Aktif'];
        }

        if ((int) $user->reputation_points >= 200) {
            $badges[] = ['key' => 'reputasi_ahli', 'label' => 'Ahli Komunitas'];
        }

        if ((int) $user->reputation_points >= 1000) {
            $badges[] = ['key' => 'reputasi_legenda', 'label' => 'Legenda'];
        }

        return $badges;
    }

    private function keyFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        return $path ? ltrim((string) $path, '/') : null;
    }
}
