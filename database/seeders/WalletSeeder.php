<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Every member starts with an empty wallet; the balance is built up by the point ledger.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::all();
        }

        foreach ($users as $user) {
            Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
        }
    }
}
