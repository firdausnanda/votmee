<?php

namespace App\Services;

use App\Models\MissionCompletion;
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
     * Record a daily mission once per Jakarta day. Reserve its reward under the
     * day's cap; the wallet is credited only when the member claims it.
     */
    public function completeMission(User $user, string $mission): ?MissionCompletion
    {
        $points = config("gamification.missions.{$mission}");

        if ($points === null) {
            throw new InvalidArgumentException("Unknown mission [{$mission}].");
        }

        if (! $user->canParticipate()) {
            return null;
        }

        $day = $this->today();

        $completion = DB::transaction(function () use ($user, $mission, $points, $day): ?MissionCompletion {
            Wallet::lockedFor($user);
            $rewards = $this->missionRewards($user, $day);
            $key = $this->missionKey($user, $mission, $day);

            if ($rewards->has($key)) {
                return null;
            }

            $amount = min((int) $points, (int) config('gamification.daily_mission_cap') - (int) $rewards->sum());

            if ($amount < 1) {
                return null;
            }

            return MissionCompletion::create([
                'user_id' => $user->id,
                'mission' => $mission,
                'completed_on' => $day,
                'amount' => $amount,
            ]);
        }, 3);

        if ($completion !== null && $this->missionRewards($user, $day)->count() === count(config('gamification.missions'))) {
            $this->achievements->unlockHidden($user, 'perfect_day');
        }

        return $completion;
    }

    public function claimMission(User $user, int $completionId): ?PointLedger
    {
        abort_unless($user->canParticipate(), 403);

        return DB::transaction(function () use ($user, $completionId): ?PointLedger {
            $wallet = Wallet::lockedFor($user);
            $completion = MissionCompletion::query()->where('user_id', $user->id)
                ->whereKey($completionId)->lockForUpdate()->firstOrFail();

            if ($completion->claimed_at !== null) {
                return null;
            }

            $ledger = PointLedger::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'amount' => $completion->amount,
                'type' => 'mission_reward',
                'idempotency_key' => $this->missionKey($user, $completion->mission, $completion->completed_on->toDateString()),
            ]);
            $wallet->increment('balance', $completion->amount);
            $completion->update(['claimed_at' => now('UTC')]);

            return $ledger;
        }, 3);
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
     * Today's missions with completed and claimed status.
     *
     * @return list<array{key: string, points: int, earned: int|null, completion_id: int|null, claimed: bool}>
     */
    public function missions(User $user): array
    {
        $day = $this->today();
        $rewards = $this->missionRewards($user, $day);
        $completions = MissionCompletion::query()->where('user_id', $user->id)
            ->whereDate('completed_on', $day)->get()->keyBy('mission');

        return collect(config('gamification.missions'))
            ->map(function (int $points, string $mission) use ($user, $day, $rewards, $completions): array {
                $completion = $completions->get($mission);
                $legacyReward = $completion === null ? $rewards->get($this->missionKey($user, $mission, $day)) : null;

                return [
                    'key' => $mission,
                    'points' => $points,
                    'earned' => $completion?->amount ?? $legacyReward,
                    'completion_id' => $completion?->id,
                    'claimed' => $completion?->claimed_at !== null || $legacyReward !== null,
                ];
            })
            ->values()
            ->all();
    }

    /** @return list<array{id: int, key: string, amount: int, completed_on: string}> */
    public function pendingMissions(User $user): array
    {
        return MissionCompletion::query()->where('user_id', $user->id)
            ->whereNull('claimed_at')->whereDate('completed_on', '<', $this->today())
            ->orderByDesc('completed_on')->get()
            ->map(fn (MissionCompletion $completion): array => [
                'id' => $completion->id,
                'key' => $completion->mission,
                'amount' => $completion->amount,
                'completed_on' => $completion->completed_on->toDateString(),
            ])->all();
    }

    /**
     * Amounts reserved for today's missions, keyed by idempotency key.
     *
     * @return Collection<string, int>
     */
    private function missionRewards(User $user, string $day): Collection
    {
        $keys = collect(array_keys(config('gamification.missions')))
            ->map(fn (string $mission): string => $this->missionKey($user, $mission, $day));

        $claimed = PointLedger::query()
            ->where('user_id', $user->id)
            ->where('type', 'mission_reward')
            ->whereIn('idempotency_key', $keys)
            ->pluck('amount', 'idempotency_key')
            ->map(fn (mixed $amount): int => (int) $amount);

        $pending = MissionCompletion::query()->where('user_id', $user->id)
            ->whereDate('completed_on', $day)->get()
            ->mapWithKeys(fn (MissionCompletion $completion): array => [
                $this->missionKey($user, $completion->mission, $day) => $completion->amount,
            ]);

        return $claimed->union($pending);
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
