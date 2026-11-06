<?php

namespace Database\Factories;

use App\Models\ForumComment;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForumComment>
 */
class ForumCommentFactory extends Factory
{
    protected $model = ForumComment::class;

    public function definition(): array
    {
        return [
            'post_id' => ForumPost::factory(),
            'parent_id' => null,
            'user_id' => User::factory(),
            'content_html' => '<p>'.fake()->sentence(12).'</p>',
            'images' => [],
            'votes_count' => 0,
            'is_best_answer' => false,
        ];
    }

    public function best(): static
    {
        return $this->state(fn (array $attributes) => ['is_best_answer' => true]);
    }
}
