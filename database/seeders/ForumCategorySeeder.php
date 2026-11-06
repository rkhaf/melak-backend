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
                'description' => 'Diskusi gejala, identifikasi, dan diagnosis penyakit tanaman.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Pengobatan & Perawatan',
                'slug' => 'pengobatan-perawatan',
                'icon' => '💊',
                'description' => 'Cara merawat tanaman sakit dan langkah pengendalian yang aman.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Tanah & Nutrisi',
                'slug' => 'tanah-nutrisi',
                'icon' => '🌱',
                'description' => 'Diskusi kondisi tanah, pemupukan, dan nutrisi tanaman.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Hama & Pengendalian',
                'slug' => 'hama-pengendalian',
                'icon' => '🐛',
                'description' => 'Identifikasi hama dan pengendalian terpadu.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Kebun & Pemeliharaan',
                'slug' => 'kebun-pemeliharaan',
                'icon' => '🏞️',
                'description' => 'Persiapan lahan, penyiraman, pemangkasan, dan perawatan kebun.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Budidaya Pemula',
                'slug' => 'budidaya-pemula',
                'icon' => '🌱',
                'description' => 'Tanya jawab dasar dan panduan awal budidaya sayuran dan tanaman pangan.',
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
