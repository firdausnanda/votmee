<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Content\CommunityContent;
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

        // 1. Core system accounts. Moderators are identified by the `role` column; only the
        // admin holds the `administrator` permission role.
        $accounts = [
            ['admin@votmee.com', 'Admin Votmee', 'admin', 'admin', 'id', 'ID', $adminRole],
            ['moderator@votmee.com', 'Moderator Votmee', 'moderator', 'moderator', 'id', 'ID', $userRole],
            ['user@votmee.com', 'Demo User', 'demouser', 'user', 'id', 'ID', $userRole],
            ['test@example.com', 'Test User', 'testuser', 'user', 'en', 'GLOBAL', $userRole],
        ];

        foreach ($accounts as [$email, $name, $username, $role, $locale, $region, $permissionRole]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'username' => $username,
                'password' => Hash::make('password'),
                'role' => $role,
                'locale' => $locale,
                'region_code' => $region,
                'email_verified_at' => now(),
            ])->syncRoles([$permissionRole]);
        }

        // 2. Community members that author the rankings and discussions in the demo content
        foreach (CommunityContent::members() as $index => $member) {
            $existing = User::where('email', $member['email'])->first();

            User::updateOrCreate(['email' => $member['email']], [
                'name' => $member['name'],
                'username' => $member['username'],
                'password' => Hash::make('password'),
                'role' => 'user',
                'locale' => $member['locale'],
                'region_code' => $member['region'],
                'email_verified_at' => $existing?->email_verified_at ?? now()->subDays(30 + ($index * 3 % 60)),
            ])->syncRoles([$userRole]);
        }

        // 3. Restricted and unverified sample accounts for testing
        User::updateOrCreate(['email' => 'restricted@votmee.com'], [
            'name' => 'Restricted User',
            'username' => 'restricted_user',
            'password' => Hash::make('password'),
            'role' => 'user',
            'locale' => 'id',
            'region_code' => 'ID',
            'email_verified_at' => now(),
            'restricted_until' => now()->addDays(7),
        ])->syncRoles([$userRole]);

        User::updateOrCreate(['email' => 'unverified@votmee.com'], [
            'name' => 'Unverified User',
            'username' => 'unverified_user',
            'password' => Hash::make('password'),
            'role' => 'user',
            'locale' => 'id',
            'region_code' => 'ID',
            'email_verified_at' => null,
        ])->syncRoles([$userRole]);
    }
}
