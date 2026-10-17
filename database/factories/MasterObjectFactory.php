<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\MasterObject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MasterObject>
 */
class MasterObjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'submitted_by' => User::factory(),
            'canonical_name' => Str::title($name),
            'normalized_name' => Str::lower($name),
            'slug' => Str::slug($name).'-'.Str::lower((string) Str::ulid()),
            'identity_attributes' => [],
            'status' => 'verified',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'pending']);
    }
}
