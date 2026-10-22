<?php

namespace Database\Factories;

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
            'submitted_by' => User::factory(),
            'created_by' => fn (array $attributes) => $attributes['submitted_by'] ?? User::factory(),
            'updated_by' => fn (array $attributes) => $attributes['submitted_by'] ?? User::factory(),
            'canonical_name' => Str::title($name),
            'normalized_name' => Str::lower($name),
            'slug' => Str::slug($name).'-'.Str::lower((string) Str::ulid()),
            'identity_attributes' => [],
            'status' => 'verified',
            'verified_at' => fn (array $attributes) => in_array($attributes['status'], ['verified', 'merged'], true) ? now('UTC') : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'pending']);
    }
}
