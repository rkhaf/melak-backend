<?php

namespace Database\Factories;

use App\Models\Disease;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Disease>
 */
class DiseaseFactory extends Factory
{
    protected $model = Disease::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'White Spot (Ich)',
            'Aeromonas Hydrophila',
            'Columnaris',
            'Trichodina',
            'Velvet (Oodinium)',
            'Saprolegnia',
            'Ichthyophthirius Mild',
            'Kutu Air',
        ]);

        return [
            'name' => $name,
            'name_en' => $name,
            'slug' => Str::slug($name),
            'category' => fake()->randomElement(['bacteria', 'virus', 'parasite', 'fungi', 'other']),
            'severity' => fake()->randomElement(['ringan', 'sedang', 'berat', 'kritis']),
            'description' => fake()->paragraph(),
            'causes' => fake()->paragraph(),
            'symptoms' => fake()->paragraph(),
            'recovery_days_min' => fake()->numberBetween(3, 7),
            'recovery_days_max' => fake()->numberBetween(8, 21),
            'is_published' => true,
            'created_by' => null,
        ];
    }
}
