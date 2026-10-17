<?php

namespace Database\Factories;

use App\Models\MasterObject;
use App\Models\RankingEntry;
use App\Models\RankingPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RankingEntry>
 */
class RankingEntryFactory extends Factory
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
            'object_id' => MasterObject::factory(),
            'position' => 1,
        ];
    }
}
