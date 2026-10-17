<?php

namespace Database\Factories;

use App\Models\RankingPost;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'target_type' => 'ranking_post',
            'target_id' => fn (): string => RankingPost::factory()->create()->id,
            'reason' => fake()->sentence(),
            'status' => 'open',
        ];
    }
}
