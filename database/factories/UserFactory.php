<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'user',
            'locale' => fake()->randomElement(['id', 'id', 'id', 'en']),
            'region_code' => fake()->randomElement(['ID', 'ID', 'GLOBAL']),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $roleName = in_array($user->role, ['admin', 'administrator', 'moderator'], true) ? 'administrator' : 'user';

            if (Schema::hasTable(config('permission.table_names.roles', 'roles'))) {
                $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                $user->assignRole($role);
            }
        });
    }

    /**
     * Indicate that the user is an administrator.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'locale' => 'id',
            'region_code' => 'ID',
        ]);
    }

    /**
     * Indicate that the user is a moderator.
     */
    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'moderator',
            'locale' => 'id',
            'region_code' => 'ID',
        ]);
    }

    /**
     * Indicate that the user is the official Votmee account.
     */
    public function official(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_official' => true,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user account is temporarily restricted.
     */
    public function restricted(): static
    {
        return $this->state(fn (array $attributes) => [
            'restricted_until' => now()->addDays(7),
        ]);
    }
}
