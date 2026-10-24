<?php

namespace Database\Factories;

use App\Models\MissionCompletion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionCompletion>
 */
class MissionCompletionFactory extends Factory
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
            'mission' => 'quick_vote',
            'completed_on' => now('Asia/Jakarta')->toDateString(),
            'amount' => 3,
            'claimed_at' => null,
        ];
    }
}
