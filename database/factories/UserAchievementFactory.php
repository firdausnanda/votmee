<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAchievement>
 */
class UserAchievementFactory extends Factory
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
            'achievement_key' => fake()->randomElement(['streak_bronze', 'curator_bronze', 'writer_bronze', 'voice_bronze', 'guardian_bronze']),
            'unlocked_at' => now('UTC'),
            'seen_at' => null,
            'featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['featured' => true]);
    }

    public function seen(): static
    {
        return $this->state(fn (): array => ['seen_at' => now('UTC')]);
    }
}
