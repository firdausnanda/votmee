<?php

namespace App\Services;

use App\Models\PointLedger;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\StreakReminder;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\DatabaseNotification;

class GamificationService
{
    /** Streaks follow the Jakarta calendar, like the daily bonus. */
    public function today(): string
    {
        return now('Asia/Jakarta')->toDateString();
    }

    /**
     * Extend the streak of a wallet the caller has already locked, paying the cycle
     * bonus when the streak reaches a multiple of the cycle length.
     */
    public function advanceStreak(Wallet $wallet, string $day): ?PointLedger
    {
        $lastClaimedOn = $wallet->last_claimed_on?->toDateString();

        if ($lastClaimedOn === $day) {
            return null;
        }

        $yesterday = CarbonImmutable::parse($day)->subDay()->toDateString();
        $streak = $lastClaimedOn === $yesterday ? $wallet->streak_days + 1 : 1;
        $wallet->update([
            'streak_days' => $streak,
            'longest_streak' => max($wallet->longest_streak, $streak),
            'last_claimed_on' => $day,
        ]);

        DatabaseNotification::query()
            ->where('notifiable_type', (new User)->getMorphClass())
            ->where('notifiable_id', $wallet->user_id)
            ->where('type', StreakReminder::class)
            ->whereNull('read_at')
            ->update(['read_at' => now('UTC')]);

        if ($streak % $this->cycleDays() !== 0) {
            return null;
        }

        $bonus = (int) config('gamification.streak.bonus');
        $ledger = PointLedger::create([
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'amount' => $bonus,
            'type' => 'streak_bonus',
            'idempotency_key' => "streak:{$wallet->user_id}:{$day}",
            'claimed_on' => $day,
        ]);
        $wallet->increment('balance', $bonus);

        return $ledger;
    }

    /**
     * The viewer's streak as the wallet shows it. A streak with a missed day reads
     * as zero even before the next claim resets it.
     *
     * @return array{days: int, longest: int, claimed_today: bool, cycle_days: int, cycle_progress: int, bonus: int}
     */
    public function streak(User $user): array
    {
        $wallet = Wallet::query()->where('user_id', $user->id)->first();
        $today = $this->today();
        $lastClaimedOn = $wallet?->last_claimed_on?->toDateString();
        $claimedToday = $lastClaimedOn === $today;
        $isActive = $claimedToday || $lastClaimedOn === CarbonImmutable::parse($today)->subDay()->toDateString();
        $days = $isActive ? $wallet->streak_days : 0;
        $cycleDays = $this->cycleDays();
        $progress = $days % $cycleDays;

        return [
            'days' => $days,
            'longest' => $wallet?->longest_streak ?? 0,
            'claimed_today' => $claimedToday,
            'cycle_days' => $cycleDays,
            'cycle_progress' => $progress === 0 && $days > 0 && $claimedToday ? $cycleDays : $progress,
            'bonus' => (int) config('gamification.streak.bonus'),
        ];
    }

    private function cycleDays(): int
    {
        return max(1, (int) config('gamification.streak.cycle_days'));
    }
}
