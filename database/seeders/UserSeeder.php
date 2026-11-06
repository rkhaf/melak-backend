<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserPond;
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
            'fish_types' => ['Lele', 'Nila'],
            'reputation_points' => 0,
            'ponds' => [],
        ],
        [
            'name' => 'Melak Admin',
            'username' => 'admin',
            'email' => 'admin@melak.id',
            'role' => 'admin',
            'province' => 'Jawa Barat',
            'bio' => 'Moderator forum dan kurator artikel penyakit ikan.',
            'fish_types' => ['Nila', 'Gurame'],
            'reputation_points' => 0,
            'ponds' => [],
        ],
        [
            'name' => 'Budi Santoso',
            'username' => 'budi',
            'email' => 'budi@melak.id',
            'role' => 'user',
            'province' => 'Jawa Barat',
            'bio' => 'Petambak lele di Karawang, sudah 6 tahun menekuni budidaya air tawar.',
            'fish_types' => ['Lele', 'Patin'],
            'reputation_points' => 320,
            'ponds' => [
                [
                    'name' => 'Kolam Lele Karawang',
                    'description' => 'Kolam beton 6 kavling untuk pembesaran lele.',
                    'location_label' => 'Karawang, Jawa Barat',
                    'area_m2' => 240.0,
                    'capacity' => 8000,
                    'fish_types' => ['Lele'],
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
            'bio' => 'Budidaya nila dan gurame di kolam tanah, fokus pada kesehatan air.',
            'fish_types' => ['Nila', 'Gurame', 'Mas'],
            'reputation_points' => 80,
            'ponds' => [
                [
                    'name' => 'Kolam Nila Selatan',
                    'description' => 'Kolam tanah 1.200 m² untuk pembesaran nila merah.',
                    'location_label' => 'Wonogiri, Jawa Tengah',
                    'area_m2' => 1200.0,
                    'capacity' => 15000,
                    'fish_types' => ['Nila'],
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
            'bio' => 'Pemula yang baru mulai beternak udang vaname.',
            'fish_types' => ['Udang Vaname'],
            'reputation_points' => 0,
            'ponds' => [
                [
                    'name' => 'Tambak Vaname Situbondo',
                    'description' => 'Tambak pendederan udang vaname musim pertama.',
                    'location_label' => 'Situbondo, Jawa Timur',
                    'area_m2' => 800.0,
                    'capacity' => 60000,
                    'fish_types' => ['Udang Vaname'],
                    'health_score' => 65,
                ],
            ],
        ],
    ];

    private const SEED_PASSWORD = 'password';

    public function run(): void
    {
        foreach (self::USERS as $data) {
            $ponds = $data['ponds'];
            unset($data['ponds']);

            $user = User::query()->firstOrNew(['email' => $data['email']]);
            $user->fill($data + ['is_active' => true, 'reputation_level' => 'Pemula']);

            if (! $user->exists) {
                // Hanya di-set saat pembuatan agar hash lama tidak diganti tiap seed ulang.
                $user->password = self::SEED_PASSWORD;
            }

            // Level diturunkan dari poin agar konsisten dengan App\Support\Reputation.
            $user->reputation_level = Reputation::levelFor((int) $user->reputation_points);
            $user->save();

            $this->seedPonds($user, $ponds);
        }

        $this->command?->info(sprintf('%d akun demo siap (password: %s).', count(self::USERS), self::SEED_PASSWORD));
    }

    /**
     * @param  array<int, array<string, mixed>>  $ponds
     */
    private function seedPonds(User $user, array $ponds): void
    {
        foreach ($ponds as $data) {
            UserPond::query()->firstOrCreate(
                ['user_id' => $user->id, 'name' => $data['name']],
                $data + ['user_id' => $user->id],
            );
        }
    }
}
