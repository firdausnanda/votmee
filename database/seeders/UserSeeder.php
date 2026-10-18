<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles exist even if UserSeeder is executed independently
        $adminRole = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // 1. Core System Accounts
        $admin = User::updateOrCreate(
            ['email' => 'admin@votmee.com'],
            [
                'name' => 'Admin Votmee',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'locale' => 'id',
                'region_code' => 'ID',
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles([$adminRole]);

        $moderator = User::updateOrCreate(
            ['email' => 'moderator@votmee.com'],
            [
                'name' => 'Moderator Votmee',
                'username' => 'moderator',
                'password' => Hash::make('password'),
                'role' => 'moderator',
                'locale' => 'id',
                'region_code' => 'ID',
                'email_verified_at' => now(),
            ]
        );
        $moderator->syncRoles([$adminRole]);

        $demoUser = User::updateOrCreate(
            ['email' => 'user@votmee.com'],
            [
                'name' => 'Demo User',
                'username' => 'demouser',
                'password' => Hash::make('password'),
                'role' => 'user',
                'locale' => 'id',
                'region_code' => 'ID',
                'email_verified_at' => now(),
            ]
        );
        $demoUser->syncRoles([$userRole]);

        $testUser = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'username' => 'testuser',
                'password' => Hash::make('password'),
                'role' => 'user',
                'locale' => 'en',
                'region_code' => 'GLOBAL',
                'email_verified_at' => now(),
            ]
        );
        $testUser->syncRoles([$userRole]);

        // 2. Realistic Indonesian & International Community Users using Faker
        $fakerId = fake('id_ID');

        for ($i = 1; $i <= 25; $i++) {
            $email = "user{$i}@community.votmee.com";
            $existing = User::where('email', $email)->first();
            $name = $existing?->name ?? $fakerId->name();
            $username = $existing?->username ?? (strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fakerId->unique()->userName())).$i);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'username' => $username,
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'locale' => 'id',
                    'region_code' => 'ID',
                    'email_verified_at' => $existing?->email_verified_at ?? now()->subDays(rand(1, 60)),
                ]
            );
            $user->syncRoles([$userRole]);
        }

        // 3. Global/International Community Users using standard Faker
        for ($i = 26; $i <= 35; $i++) {
            $email = "global{$i}@community.votmee.com";
            $existing = User::where('email', $email)->first();
            $name = $existing?->name ?? fake()->name();
            $username = $existing?->username ?? (strtolower(preg_replace('/[^a-zA-Z0-9]/', '', fake()->unique()->userName())).$i);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'username' => $username,
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'locale' => 'en',
                    'region_code' => 'GLOBAL',
                    'email_verified_at' => $existing?->email_verified_at ?? now()->subDays(rand(1, 60)),
                ]
            );
            $user->syncRoles([$userRole]);
        }

        // 4. Restricted and Unverified sample accounts for testing
        $restrictedUser = User::updateOrCreate(
            ['email' => 'restricted@votmee.com'],
            [
                'name' => 'Restricted User',
                'username' => 'restricted_user',
                'password' => Hash::make('password'),
                'role' => 'user',
                'locale' => 'id',
                'region_code' => 'ID',
                'email_verified_at' => now(),
                'restricted_until' => now()->addDays(7),
            ]
        );
        $restrictedUser->syncRoles([$userRole]);

        $unverifiedUser = User::updateOrCreate(
            ['email' => 'unverified@votmee.com'],
            [
                'name' => 'Unverified User',
                'username' => 'unverified_user',
                'password' => Hash::make('password'),
                'role' => 'user',
                'locale' => 'id',
                'region_code' => 'ID',
                'email_verified_at' => null,
            ]
        );
        $unverifiedUser->syncRoles([$userRole]);
    }
}
