<?php

namespace Database\Seeders;

use App\Models\PointLedger;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class PointLedgerSeeder extends Seeder
{
    /**
     * Give every member a welcome bonus and a believable history of daily claims,
     * then make each wallet balance equal the sum of its ledger.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::all();
        }

        foreach ($users as $user) {
            $wallet = Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
            $welcomeDay = now('Asia/Jakarta')->subDays(21);

            $this->record($wallet, $user, "welcome:{$user->id}", 20, $welcomeDay->toDateString(), $welcomeDay->utc()->addHours(2));

            // Members claim on most days, but nobody claims every single day.
            for ($daysAgo = 0; $daysAgo <= 14; $daysAgo++) {
                if ($daysAgo > 0 && mt_rand(1, 100) > 70) {
                    continue;
                }

                $day = now('Asia/Jakarta')->subDays($daysAgo);

                if ($daysAgo === 0 && $day->hour < 7) {
                    continue;
                }

                $this->record($wallet, $user, "daily:{$user->id}:{$day->toDateString()}", 5, $day->toDateString(), $day->copy()->startOfDay()->addHours(mt_rand(7, min(22, max(7, $day->hour))))->utc());
            }
        }

        self::syncStreaks();
        self::syncBalances();
    }

    /**
     * Rebuild each wallet's streak from its daily claims, paying the cycle bonus on every
     * completed cycle the way a live claim would.
     */
    public static function syncStreaks(): void
    {
        $cycleDays = (int) config('gamification.streak.cycle_days');
        $bonus = (int) config('gamification.streak.bonus');

        foreach (Wallet::all() as $wallet) {
            $claims = PointLedger::where('user_id', $wallet->user_id)->where('type', 'daily_bonus')->orderBy('claimed_on')->get();
            $streak = 0;
            $longest = 0;
            $previous = null;

            foreach ($claims as $claim) {
                $day = CarbonImmutable::parse($claim->claimed_on)->toDateString();
                $streak = $previous === CarbonImmutable::parse($day)->subDay()->toDateString() ? $streak + 1 : 1;
                $longest = max($longest, $streak);
                $previous = $day;

                if ($streak % $cycleDays === 0 && ! PointLedger::where('idempotency_key', "streak:{$wallet->user_id}:{$day}")->exists()) {
                    (new PointLedger)->forceFill([
                        'wallet_id' => $wallet->id,
                        'user_id' => $wallet->user_id,
                        'amount' => $bonus,
                        'type' => 'streak_bonus',
                        'idempotency_key' => "streak:{$wallet->user_id}:{$day}",
                        'claimed_on' => $day,
                        'created_at' => $claim->created_at,
                        'updated_at' => $claim->created_at,
                    ])->save();
                }
            }

            $wallet->update(['streak_days' => $streak, 'longest_streak' => $longest, 'last_claimed_on' => $previous]);
        }
    }

    /** Make every wallet balance match what its ledger says. */
    public static function syncBalances(): void
    {
        foreach (Wallet::all() as $wallet) {
            $wallet->update(['balance' => max(0, (int) PointLedger::where('user_id', $wallet->user_id)->sum('amount'))]);
        }
    }

    private function record(Wallet $wallet, User $user, string $key, int $amount, string $claimedOn, $at): void
    {
        if (PointLedger::where('idempotency_key', $key)->exists() || PointLedger::where('user_id', $user->id)->where('type', 'daily_bonus')->where('claimed_on', $claimedOn)->exists()) {
            return;
        }

        (new PointLedger)->forceFill([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => 'daily_bonus',
            'idempotency_key' => $key,
            'claimed_on' => $claimedOn,
            'created_at' => $at,
            'updated_at' => $at,
        ])->save();
    }
}
