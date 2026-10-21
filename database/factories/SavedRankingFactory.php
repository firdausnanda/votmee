<?php

namespace Database\Factories;

use App\Models\RankingPost;
use App\Models\SavedRanking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedRanking>
 */
class SavedRankingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ranking_post_id' => RankingPost::factory(),
        ];
    }
}
