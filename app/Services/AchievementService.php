<?php

namespace App\Services;

use App\Models\MasterObject;
use App\Models\ObjectContentRevision;
use App\Models\PointLedger;
use App\Models\Report;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AchievementService
{
    private const HOLDERS_CACHE_KEY = 'achievements.holders';

    /**
     * Unlock every tier of a category the user has reached, paying each new badge's
     * points once. Returns the keys unlocked by this call.
     *
     * @return list<string>
     */
    public function evaluate(User $user, string $category, bool $awardPoints = true): array
    {
        $tiers = config("gamification.achievements.{$category}");

        if ($tiers === null) {
            throw new InvalidArgumentException("Unknown achievement category [{$category}].");
        }

        $count = $this->count($user, $category);
        $owned = UserAchievement::query()->where('user_id', $user->id)->pluck('achievement_key')->flip();
        $unlocked = [];

        foreach ($tiers as $tier => $threshold) {
            $key = "{$category}_{$tier}";

            if ($count < $threshold || $owned->has($key)) {
                continue;
            }

            $isNew = DB::transaction(function () use ($user, $key, $tier, $awardPoints): bool {
                $achievement = UserAchievement::query()->createOrFirst(
                    ['user_id' => $user->id, 'achievement_key' => $key],
                    ['unlocked_at' => now('UTC')],
                );

                if (! $achievement->wasRecentlyCreated) {
                    return false;
                }

                if ($awardPoints && $user->canParticipate()) {
                    $this->payPoints($user, $key, (int) config("gamification.achievement_points.{$tier}"));
                }

                return true;
            }, 3);

            if ($isNew) {
                $unlocked[] = $key;
            }
        }

        if ($unlocked !== []) {
            Cache::forget(self::HOLDERS_CACHE_KEY);
        }

        return $unlocked;
    }

    /**
     * Every badge with the user's progress toward it, in category and tier order.
     *
     * @return list<array{key: string, category: string, tier: string, threshold: int, points: int, current: int, unlocked_at: string|null, featured: bool, holders_percent: float}>
     */
    public function progress(User $user): array
    {
        $owned = UserAchievement::query()->where('user_id', $user->id)->get()->keyBy('achievement_key');
        $holders = $this->holdersPercent();
        $badges = [];

        foreach (config('gamification.achievements') as $category => $tiers) {
            $current = $this->count($user, $category);

            foreach ($tiers as $tier => $threshold) {
                $key = "{$category}_{$tier}";
                $achievement = $owned->get($key);
                $badges[] = [
                    'key' => $key,
                    'category' => $category,
                    'tier' => $tier,
                    'threshold' => $threshold,
                    'points' => (int) config("gamification.achievement_points.{$tier}"),
                    'current' => $current,
                    'unlocked_at' => $achievement?->unlocked_at->toIso8601String(),
                    'featured' => (bool) $achievement?->featured,
                    'holders_percent' => $holders[$key] ?? 0.0,
                ];
            }
        }

        return $badges;
    }

    /**
     * Replace the badges shown beside the user's name. Only unlocked badges qualify.
     *
     * @param  list<string>  $keys
     */
    public function setFeatured(User $user, array $keys): void
    {
        $keys = array_values(array_unique($keys));
        $ownedCount = UserAchievement::query()->where('user_id', $user->id)->whereIn('achievement_key', $keys)->count();

        if (count($keys) > UserAchievement::MAX_FEATURED || $ownedCount !== count($keys)) {
            throw ValidationException::withMessages(['keys' => __('votmee.achievement_feature_invalid')]);
        }

        DB::transaction(function () use ($user, $keys): void {
            UserAchievement::query()->where('user_id', $user->id)->update(['featured' => false]);
            UserAchievement::query()->where('user_id', $user->id)->whereIn('achievement_key', $keys)->update(['featured' => true]);
        });
    }

    /**
     * Badges unlocked since the user last dismissed the unlock notice.
     *
     * @return list<string>
     */
    public function unseen(User $user): array
    {
        return UserAchievement::query()
            ->where('user_id', $user->id)
            ->whereNull('seen_at')
            ->orderBy('unlocked_at')
            ->pluck('achievement_key')
            ->all();
    }

    public function markSeen(User $user): void
    {
        UserAchievement::query()->where('user_id', $user->id)->whereNull('seen_at')->update(['seen_at' => now('UTC')]);
    }

    /** The count a category's thresholds are measured against. */
    public function count(User $user, string $category): int
    {
        return match ($category) {
            'streak' => (int) (Wallet::query()->where('user_id', $user->id)->value('longest_streak') ?? 0),
            'curator' => MasterObject::query()->where('submitted_by', $user->id)->whereIn('status', ['verified', 'merged'])->count(),
            'writer' => ObjectContentRevision::query()->where('author_id', $user->id)->where('status', 'published')->count(),
            'voice' => DB::table('comment_likes')
                ->join('comments', 'comments.id', '=', 'comment_likes.comment_id')
                ->where('comments.user_id', $user->id)
                ->where('comments.status', 'visible')
                ->where('comment_likes.user_id', '!=', $user->id)
                ->count(),
            'guardian' => Report::query()->where('reporter_id', $user->id)->where('status', 'resolved')->count(),
            default => throw new InvalidArgumentException("Unknown achievement category [{$category}]."),
        };
    }

    /**
     * Share of members holding each badge, as a percentage with one decimal.
     *
     * @return array<string, float>
     */
    private function holdersPercent(): array
    {
        return Cache::remember(self::HOLDERS_CACHE_KEY, now()->addMinutes(10), function (): array {
            $members = max(1, User::query()->whereNotNull('email_verified_at')->count());

            return UserAchievement::query()
                ->selectRaw('achievement_key, COUNT(*) as holders')
                ->groupBy('achievement_key')
                ->pluck('holders', 'achievement_key')
                ->map(fn (mixed $holders): float => round(min(100, (int) $holders / $members * 100), 1))
                ->all();
        });
    }

    private function payPoints(User $user, string $key, int $points): void
    {
        if ($points < 1) {
            return;
        }

        $wallet = Wallet::lockedFor($user);
        PointLedger::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'amount' => $points,
            'type' => 'achievement',
            'idempotency_key' => "achievement:{$user->id}:{$key}",
        ]);
        $wallet->increment('balance', $points);
    }
}
