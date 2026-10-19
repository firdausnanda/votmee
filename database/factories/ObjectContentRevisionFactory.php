<?php

namespace Database\Factories;

use App\Models\MasterObject;
use App\Models\ObjectContentRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ObjectContentRevision>
 */
class ObjectContentRevisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'object_id' => MasterObject::factory(),
            'author_id' => User::factory(),
            'title' => null,
            'body' => fake()->paragraph(3),
            'sources' => ['https://example.com/reference'],
            'language_code' => 'id',
            'status' => 'pending',
        ];
    }
}
