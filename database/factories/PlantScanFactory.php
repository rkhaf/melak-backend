<?php

namespace Database\Factories;

use App\Models\PlantScan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlantScan>
 */
class PlantScanFactory extends Factory
{
    protected $model = PlantScan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'garden_id' => null,
            'plant_type' => fake()->randomElement(['Tomat', 'Jagung', 'Cabai', 'Paprika']),
            'symptoms_desc' => fake()->sentence(6),
            'status' => 'done',
            'error_code' => null,
            'error_message' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn () => ['status' => 'processing']);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'error_code' => 'ML_TIMEOUT',
            'error_message' => 'Prediksi melewati batas 30 detik. Coba lagi.',
        ]);
    }
}
