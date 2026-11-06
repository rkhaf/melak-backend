<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserGarden;
use App\Support\Reputation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun demo untuk pengembangan lokal (README §Setup).
 *
 * Tidak pernah dipanggil di production — DatabaseSeeder hanya memanggilnya
 * ketika app()->environment('local'), supaya kredensial yang diketahui publik
 * tidak pernah ter-seed di server sungguhan.
 *
 * Semua password: `password`
 */
class UserSeeder extends Seeder
{
    /** @var array<int, array<string, mixed>> */
    private const USERS = [
        [
            'name' => 'Melak Superadmin',
            'username' => 'superadmin',
            'email' => 'superadmin@melak.id',
            'role' => 'superadmin',
            'province' => 'DKI Jakarta',
            'bio' => 'Akun superadmin untuk mengelola panel admin Melak.',
            'plant_types' => ['Tomat', 'Jagung'],
            'reputation_points' => 0,
            'gardens' => [],
        ],
        [
            'name' => 'Melak Admin',
            'username' => 'admin',
            'email' => 'admin@melak.id',
            'role' => 'admin',
            'province' => 'Jawa Barat',
            'bio' => 'Moderator forum dan kurator artikel penyakit tanaman.',
            'plant_types' => ['Cabai', 'Tomat'],
            'reputation_points' => 0,
            'gardens' => [],
        ],
        [
            'name' => 'Budi Santoso',
            'username' => 'budi',
            'email' => 'budi@melak.id',
            'role' => 'user',
            'province' => 'Jawa Barat',
            'bio' => 'Petani tomat dan jagung di Karawang, sudah 6 tahun menekuni budidaya tanaman.',
            'plant_types' => ['Tomat', 'Jagung'],
            'reputation_points' => 320,
            'gardens' => [
                [
                    'name' => 'Kebun Tomat Karawang',
                    'description' => 'Lahan sayur seluas 240 m² dengan tanaman tomat dan jagung.',
                    'location_label' => 'Karawang, Jawa Barat',
                    'area_m2' => 240.0,
                    'capacity' => 500,
                    'plant_types' => ['Tomat', 'Jagung'],
                    'health_score' => 92,
                ],
            ],
        ],
        [
            'name' => 'Sari Wulandari',
            'username' => 'sari',
            'email' => 'sari@melak.id',
            'role' => 'user',
            'province' => 'Jawa Tengah',
            'bio' => 'Budidaya cabai dan tomat di lahan terbuka, fokus pada kesehatan tanaman.',
            'plant_types' => ['Cabai', 'Tomat', 'Paprika'],
            'reputation_points' => 80,
            'gardens' => [
                [
                    'name' => 'Kebun Cabai Selatan',
                    'description' => 'Lahan tanah 1.200 m² untuk budidaya cabai dan tomat.',
                    'location_label' => 'Wonogiri, Jawa Tengah',
                    'area_m2' => 1200.0,
                    'capacity' => 2400,
                    'plant_types' => ['Cabai', 'Tomat'],
                    'health_score' => 78,
                ],
            ],
        ],
        [
            'name' => 'Joko Prasetyo',
            'username' => 'joko',
            'email' => 'joko@melak.id',
            'role' => 'user',
            'province' => 'Jawa Timur',
            'bio' => 'Pemula yang baru mulai menanam sayuran di lahan rumah.',
            'plant_types' => ['Jagung', 'Cabai'],
            'reputation_points' => 0,
            'gardens' => [
                [
                    'name' => 'Kebun Sayur Situbondo',
                    'description' => 'Kebun sayur rumahan untuk jagung dan cabai.',
                    'location_label' => 'Situbondo, Jawa Timur',
                    'area_m2' => 800.0,
                    'capacity' => 300,
                    'plant_types' => ['Jagung', 'Cabai'],
                    'health_score' => 65,
                ],
            ],
        ],
    ];

    private const SEED_PASSWORD = 'password';

    public function run(): void
    {
        foreach (self::USERS as $data) {
            $gardens = $data['gardens'];
            unset($data['gardens']);

            $user = User::query()->firstOrNew(['email' => $data['email']]);
            $user->fill($data + ['is_active' => true, 'reputation_level' => 'Pemula']);

            if (! $user->exists) {
                // Hanya di-set saat pembuatan agar hash lama tidak diganti tiap seed ulang.
                $user->password = self::SEED_PASSWORD;
            }

            // Level diturunkan dari poin agar konsisten dengan App\Support\Reputation.
            $user->reputation_level = Reputation::levelFor((int) $user->reputation_points);
            $user->save();

            $this->seedGardens($user, $gardens);
        }

        $this->command?->info(sprintf('%d akun demo siap (password: %s).', count(self::USERS), self::SEED_PASSWORD));
    }

    /**
     * @param  array<int, array<string, mixed>>  $gardens
     */
    private function seedGardens(User $user, array $gardens): void
    {
        foreach ($gardens as $data) {
            UserGarden::query()->firstOrCreate(
                ['user_id' => $user->id, 'name' => $data['name']],
                $data + ['user_id' => $user->id],
            );
        }
    }
}
