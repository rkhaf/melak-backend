<?php

namespace Database\Factories;

use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForumPost>
 */
class ForumPostFactory extends Factory
{
    protected $model = ForumPost::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);

        return [
            'user_id' => User::factory(),
            'category_id' => ForumCategory::factory(),
            'scan_id' => null,
            'title' => $title,
            'content_html' => '<p>'.fake()->paragraph().'</p>',
            'image_urls' => [],
            'type' => fake()->randomElement(['question', 'discussion']),
            'status' => 'published',
            'is_pinned' => false,
            'views_count' => fake()->numberBetween(0, 50),
            'votes_count' => 0,
            'comments_count' => 0,
            'has_best_answer' => false,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    public function question(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'question']);
    }

    public function discussion(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'discussion']);
    }
}
