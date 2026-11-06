<?php

namespace Database\Factories;

use App\Models\FishScan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FishScan>
 */
class FishScanFactory extends Factory
{
    protected $model = FishScan::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'pond_id' => null,
            'fish_type' => fake()->randomElement(['Lele', 'Nila', 'Gurame', 'Udang', 'Bandeng']),
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
