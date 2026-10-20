<?php

namespace Database\Factories;

use App\Models\QuickDuel;
use App\Models\RankingEntry;
use App\Models\RankingPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuickDuel>
 */
class QuickDuelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'duel_date' => now('Asia/Jakarta')->toDateString(),
            'ranking_post_id' => RankingPost::factory(),
            'entry_a_id' => fn (array $attributes): int => RankingEntry::factory()->create([
                'ranking_post_id' => $attributes['ranking_post_id'],
                'position' => 1,
            ])->id,
            'entry_b_id' => fn (array $attributes): int => RankingEntry::factory()->create([
                'ranking_post_id' => $attributes['ranking_post_id'],
                'position' => 2,
            ])->id,
        ];
    }
}
