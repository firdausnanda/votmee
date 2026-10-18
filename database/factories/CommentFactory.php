<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\RankingPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ranking_post_id' => RankingPost::factory(),
            'user_id' => User::factory(),
            'created_by' => fn (array $attributes) => $attributes['user_id'] ?? User::factory(),
            'updated_by' => fn (array $attributes) => $attributes['user_id'] ?? User::factory(),
            'body' => fake()->sentence(),
            'status' => 'visible',
        ];
    }
}
