<?php

namespace Database\Factories;

use App\Models\ForumCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ForumCategory>
 */
class ForumCategoryFactory extends Factory
{
    protected $model = ForumCategory::class;

    public function definition(): array
    {
        // 1. Pilih nama secara acak tanpa fake()->unique()
        $baseName = fake()->randomElement([
            'Identifikasi Penyakit',
            'Pengobatan & Perawatan',
            'Kualitas Air',
            'Pakan & Nutrisi',
            'Kolam & Pemeliharaan',
            'Budidaya Pemula',
        ]);

        // 2. Buat string acak unik untuk membedakan slug jika nama terpilih berulang
        $uniqueSuffix = fake()->unique()->numberBetween(100, 99999);

        return [
            'name' => $baseName,
            // 3. Gabungkan slug nama dasar dengan suffix unik
            'slug' => Str::slug($baseName) . '-' . $uniqueSuffix,
            'icon' => fake()->randomElement(['🦠', '💊', '💧', '🐟', '🏞️']),
            'description' => fake()->sentence(8),
            'sort_order' => fake()->numberBetween(1, 20),
            'post_count' => 0,
        ];
    }
    //update factory
}