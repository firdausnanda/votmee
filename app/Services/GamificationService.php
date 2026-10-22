<?php

namespace App\Services;

use App\Models\PointLedger;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\StreakReminder;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GamificationService
{
    public function __construct(private AchievementService $achievements) {}

    /** Streaks and missions follow the Jakarta calendar, like the daily bonus. */
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
     * Pay a daily mission once per Jakarta day. The payout is trimmed so the day's
     * mission total never exceeds the configured cap.
     */
    public function completeMission(User $user, string $mission): ?PointLedger
    {
        $points = config("gamification.missions.{$mission}");

        if ($points === null) {
            throw new InvalidArgumentException("Unknown mission [{$mission}].");
        }

        if (! $user->canParticipate()) {
            return null;
        }

        $day = $this->today();

        $ledger = DB::transaction(function () use ($user, $mission, $points, $day): ?PointLedger {
            $wallet = Wallet::lockedFor($user);
            $rewards = $this->missionRewards($user, $day);
            $key = $this->missionKey($user, $mission, $day);

            if ($rewards->has($key)) {
                return null;
            }

            $amount = min((int) $points, (int) config('gamification.daily_mission_cap') - (int) $rewards->sum());

            if ($amount < 1) {
                return null;
            }

            $ledger = PointLedger::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => 'mission_reward',
                'idempotency_key' => $key,
            ]);
            $wallet->increment('balance', $amount);

            return $ledger;
        }, 3);

        if ($ledger !== null && $this->missionRewards($user, $day)->count() === count(config('gamification.missions'))) {
            $this->achievements->unlockHidden($user, 'perfect_day');
        }

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

    /**
     * Today's missions with the points each one paid, if any.
     *
     * @return list<array{key: string, points: int, earned: int|null}>
     */
    public function missions(User $user): array
    {
        $day = $this->today();
        $rewards = $this->missionRewards($user, $day);

        return collect(config('gamification.missions'))
            ->map(fn (int $points, string $mission): array => [
                'key' => $mission,
                'points' => $points,
                'earned' => $rewards->get($this->missionKey($user, $mission, $day)),
            ])
            ->values()
            ->all();
    }

    /**
     * Amounts already paid for today's missions, keyed by idempotency key.
     *
     * @return Collection<string, int>
     */
    private function missionRewards(User $user, string $day): Collection
    {
        $keys = collect(array_keys(config('gamification.missions')))
            ->map(fn (string $mission): string => $this->missionKey($user, $mission, $day));

        return PointLedger::query()
            ->where('user_id', $user->id)
            ->where('type', 'mission_reward')
            ->whereIn('idempotency_key', $keys)
            ->pluck('amount', 'idempotency_key')
            ->map(fn (mixed $amount): int => (int) $amount);
    }

    private function missionKey(User $user, string $mission, string $day): string
    {
        return "mission:{$user->id}:{$mission}:{$day}";
    }

    private function cycleDays(): int
    {
        return max(1, (int) config('gamification.streak.cycle_days'));
    }
}
