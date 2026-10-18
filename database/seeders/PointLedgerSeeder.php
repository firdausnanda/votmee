<?php

namespace Database\Seeders;

use App\Models\PointLedger;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class PointLedgerSeeder extends Seeder
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
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 25]
            );

            // 1. Initial Welcome Bonus transaction
            $welcomeKey = "welcome:{$user->id}";
            if (! PointLedger::where('idempotency_key', $welcomeKey)->exists()) {
                PointLedger::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $user->id,
                    'amount' => 20,
                    'type' => 'daily_bonus',
                    'idempotency_key' => $welcomeKey,
                    'claimed_on' => now('Asia/Jakarta')->subDays(15)->toDateString(),
                    'created_at' => now('UTC')->subDays(15),
                    'updated_at' => now('UTC')->subDays(15),
                ]);
            }

            // 2. Daily bonus claim history over past 7 days (simulate user activity)
            for ($daysAgo = 0; $daysAgo <= 7; $daysAgo++) {
                // Not every user claims every day, 65% chance
                if ($daysAgo > 0 && rand(1, 100) > 65) {
                    continue;
                }

                $claimedDate = now('Asia/Jakarta')->subDays($daysAgo)->toDateString();
                $idempotencyKey = "daily:{$user->id}:{$claimedDate}";

                $exists = PointLedger::where('user_id', $user->id)
                    ->where('type', 'daily_bonus')
                    ->where('claimed_on', $claimedDate)
                    ->exists();

                if (! $exists) {
                    $timestamp = now('UTC')->subDays($daysAgo)->subHours(rand(1, 12));
                    PointLedger::create([
                        'wallet_id' => $wallet->id,
                        'user_id' => $user->id,
                        'amount' => 5,
                        'type' => 'daily_bonus',
                        'idempotency_key' => $idempotencyKey,
                        'claimed_on' => $claimedDate,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);
                }
            }
        }
    }
}
