<?php

namespace Database\Seeders;

use App\Models\ForumCategory;
use Illuminate\Database\Seeder;

class ForumCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Identifikasi Penyakit',
                'slug' => 'identifikasi-penyakit',
                'icon' => '🦠',
                'description' => 'Diskusi seputar gejala dan identifikasi penyakit ikan air tawar.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Pengobatan & Perawatan',
                'slug' => 'pengobatan-perawatan',
                'icon' => '💊',
                'description' => 'Metode pengobatan, dosis obat, dan pemulihan ikan sakit.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Kualitas Air',
                'slug' => 'kualitas-air',
                'icon' => '💧',
                'description' => 'Manajemen pH, DO, suhu, amonia, dan ekosistem air kolam.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Pakan & Nutrisi',
                'slug' => 'pakan-nutrisi',
                'icon' => '🐟',
                'description' => 'Formula pakan, FCR, suplemen, dan jadwal pemberian pakan.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Kolam & Pemeliharaan',
                'slug' => 'kolam-pemeliharaan',
                'icon' => '🏞️',
                'description' => 'Teknologi bioflok, kolam terpal, tanah, dan kebersihan kolam.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Budidaya Pemula',
                'slug' => 'budidaya-pemula',
                'icon' => '🌱',
                'description' => 'Tanya jawab dasar dan panduan awal budidaya ikan.',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $cat) {
            ForumCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }
    }
}
