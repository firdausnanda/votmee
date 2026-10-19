<?php

namespace Database\Factories;

use App\Models\MasterObject;
use App\Models\ObjectAlias;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ObjectAlias>
 */
class ObjectAliasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $alias = fake()->unique()->words(2, true);

        return [
            'object_id' => MasterObject::factory(),
            'alias' => $alias,
            'normalized_alias' => mb_strtolower($alias),
        ];
    }
}
