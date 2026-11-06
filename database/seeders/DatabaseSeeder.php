<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Urutan penting: HelpSeeder memakai truncate() sehingga harus jalan dulu,
     * sebelum ada data lain yang bergantung padanya.
     */
    public function run(): void
    {
        $this->call(HelpSeeder::class);

        // 11 kelas output model ViT → tabel diseases (FR-10.11).
        $this->call(DiseaseSeeder::class);

        // Kategori forum komunitas
        $this->call(ForumCategorySeeder::class);

        // Akun demo hanya untuk lokal — jangan pernah membuat kredensial
        // yang diketahui publik di production.
        if (! app()->isProduction()) {
            $this->call(UserSeeder::class);
        }
    }
}
