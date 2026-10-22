<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'direct_key' => fn (): string => fake()->unique()->numerify('#####:#####'),
            'initiator_id' => User::factory(),
            'last_message_at' => now(),
        ];
    }
}
