<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\RankingPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RankingPost>
 */
class RankingPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(5);

        return [
            'creator_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower((string) Str::ulid()),
            'criteria' => fake()->paragraph(),
            'language_code' => 'id',
            'region_code' => 'GLOBAL',
            'status' => 'published',
            'published_at' => now('UTC'),
        ];
    }
}
