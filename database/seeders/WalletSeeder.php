<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::all();
        }

        foreach ($users as $user) {
            Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance' => fake()->numberBetween(35, 75),
                ]
            );
        }
    }
}
