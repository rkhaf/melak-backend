<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('user_ponds', 'user_gardens');
        Schema::table('user_gardens', function ($table) {
            $table->renameColumn('fish_types', 'plant_types');
        });

        Schema::table('users', function ($table) {
            $table->renameColumn('fish_types', 'plant_types');
        });

        Schema::rename('disease_fish_types', 'disease_plant_types');
        Schema::table('disease_plant_types', function ($table) {
            $table->renameColumn('fish_type', 'plant_type');
        });

        Schema::rename('fish_scans', 'plant_scans');
        Schema::table('plant_scans', function ($table) {
            $table->renameColumn('fish_type', 'plant_type');
            $table->renameColumn('pond_id', 'garden_id');
        });

        DB::table('forum_categories')->where('slug', 'identifikasi-penyakit')->update([
            'description' => 'Diskusi gejala, identifikasi, dan diagnosis penyakit tanaman.',
        ]);
        DB::table('forum_categories')->where('slug', 'pengobatan-perawatan')->update([
            'description' => 'Cara merawat tanaman sakit dan langkah pengendalian yang aman.',
        ]);
        DB::table('forum_categories')->where('slug', 'kualitas-air')->update([
            'name' => 'Tanah & Nutrisi',
            'slug' => 'tanah-nutrisi',
            'icon' => '🌱',
            'description' => 'Diskusi kondisi tanah, pemupukan, dan nutrisi tanaman.',
        ]);
        DB::table('forum_categories')->where('slug', 'pakan-nutrisi')->update([
            'name' => 'Hama & Pengendalian',
            'slug' => 'hama-pengendalian',
            'icon' => '🐛',
            'description' => 'Identifikasi hama dan pengendalian terpadu.',
        ]);
        DB::table('forum_categories')->where('slug', 'kolam-pemeliharaan')->update([
            'name' => 'Kebun & Pemeliharaan',
            'slug' => 'kebun-pemeliharaan',
            'icon' => '🏞️',
            'description' => 'Persiapan lahan, penyiraman, pemangkasan, dan perawatan kebun.',
        ]);
        DB::table('forum_categories')->where('slug', 'budidaya-pemula')->update([
            'description' => 'Tanya jawab dasar dan panduan awal budidaya sayuran dan tanaman pangan.',
        ]);
    }

    public function down(): void
    {
        Schema::table('plant_scans', function ($table) {
            $table->renameColumn('plant_type', 'fish_type');
            $table->renameColumn('garden_id', 'pond_id');
        });
        Schema::rename('plant_scans', 'fish_scans');

        Schema::table('disease_plant_types', function ($table) {
            $table->renameColumn('plant_type', 'fish_type');
        });
        Schema::rename('disease_plant_types', 'disease_fish_types');

        Schema::table('users', function ($table) {
            $table->renameColumn('plant_types', 'fish_types');
        });

        Schema::table('user_gardens', function ($table) {
            $table->renameColumn('plant_types', 'fish_types');
        });
        Schema::rename('user_gardens', 'user_ponds');

        DB::table('forum_categories')->where('slug', 'identifikasi-penyakit')->update([
            'description' => 'Diskusi seputar gejala dan identifikasi penyakit ikan air tawar.',
        ]);
        DB::table('forum_categories')->where('slug', 'pengobatan-perawatan')->update([
            'description' => 'Metode pengobatan, dosis obat, dan pemulihan ikan sakit.',
        ]);
        DB::table('forum_categories')->where('slug', 'tanah-nutrisi')->update([
            'name' => 'Kualitas Air',
            'slug' => 'kualitas-air',
            'icon' => '💧',
            'description' => 'Manajemen pH, DO, suhu, amonia, dan ekosistem air kolam.',
        ]);
        DB::table('forum_categories')->where('slug', 'hama-pengendalian')->update([
            'name' => 'Pakan & Nutrisi',
            'slug' => 'pakan-nutrisi',
            'icon' => '🐟',
            'description' => 'Formula pakan, FCR, suplemen, dan jadwal pemberian pakan.',
        ]);
        DB::table('forum_categories')->where('slug', 'kebun-pemeliharaan')->update([
            'name' => 'Kolam & Pemeliharaan',
            'slug' => 'kolam-pemeliharaan',
            'description' => 'Teknologi bioflok, kolam terpal, tanah, dan kebersihan kolam.',
        ]);
        DB::table('forum_categories')->where('slug', 'budidaya-pemula')->update([
            'description' => 'Tanya jawab dasar dan panduan awal budidaya ikan.',
        ]);
    }
};
