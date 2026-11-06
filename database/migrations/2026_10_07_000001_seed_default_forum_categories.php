<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categories = [
            [
                'name' => 'Identifikasi Penyakit',
                'slug' => 'identifikasi-penyakit',
                'icon' => '🦠',
                'description' => 'Diskusi seputar gejala dan identifikasi penyakit ikan air tawar.',
                'sort_order' => 1,
                'post_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pengobatan & Perawatan',
                'slug' => 'pengobatan-perawatan',
                'icon' => '💊',
                'description' => 'Metode pengobatan, dosis obat, dan pemulihan ikan sakit.',
                'sort_order' => 2,
                'post_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kualitas Air',
                'slug' => 'kualitas-air',
                'icon' => '💧',
                'description' => 'Manajemen pH, DO, suhu, amonia, dan ekosistem air kolam.',
                'sort_order' => 3,
                'post_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pakan & Nutrisi',
                'slug' => 'pakan-nutrisi',
                'icon' => '🐟',
                'description' => 'Formula pakan, FCR, suplemen, dan jadwal pemberian pakan.',
                'sort_order' => 4,
                'post_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Kolam & Pemeliharaan',
                'slug' => 'kolam-pemeliharaan',
                'icon' => '🏞️',
                'description' => 'Teknologi bioflok, kolam terpal, tanah, dan kebersihan kolam.',
                'sort_order' => 5,
                'post_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Budidaya Pemula',
                'slug' => 'budidaya-pemula',
                'icon' => '🌱',
                'description' => 'Tanya jawab dasar dan panduan awal budidaya ikan.',
                'sort_order' => 6,
                'post_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($categories as $cat) {
            DB::table('forum_categories')->updateOrInsert(
                ['slug' => $cat['slug']],
                $cat
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('forum_categories')->whereIn('slug', [
            'identifikasi-penyakit',
            'pengobatan-perawatan',
            'kualitas-air',
            'pakan-nutrisi',
            'kolam-pemeliharaan',
            'budidaya-pemula',
        ])->delete();
    }
};
