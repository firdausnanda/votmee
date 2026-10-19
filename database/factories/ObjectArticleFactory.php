<?php

namespace Database\Factories;

use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ObjectArticle>
 */
class ObjectArticleFactory extends Factory
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
            'type' => 'experience',
        ];
    }
}
