<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\ObjectContentRevision;
use App\Models\PointLedger;
use App\Models\Report;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AchievementService
{
    private const HOLDERS_CACHE_KEY = 'achievements.holders';

    /**
     * Unlock every tier of a category the user has reached, plus this month's seasonal
     * badge for it, paying each new badge's points once. Returns the keys unlocked.
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

            if ($count >= $threshold && ! $owned->has($key)
                && $this->unlock($user, $key, (int) config("gamification.achievement_points.{$tier}"), $awardPoints)) {
                $unlocked[] = $key;
            }
        }

        $seasonThreshold = config("gamification.seasonal.{$category}");
        $month = $this->currentMonth();
        $seasonKey = $this->seasonKey($month, $category);

        if ($seasonThreshold !== null && ! $owned->has($seasonKey)
            && $this->monthlyCount($user, $category, $month) >= $seasonThreshold
            && $this->unlock($user, $seasonKey, (int) config('gamification.seasonal_points'), $awardPoints)) {
            $unlocked[] = $seasonKey;
        }

        $this->grantTrustIfEligible($user);

        return $unlocked;
    }

    /** Unlock a hidden badge once, paying its points. Returns whether it was new. */
    public function unlockHidden(User $user, string $name, bool $awardPoints = true): bool
    {
        $points = config("gamification.hidden.{$name}");

        if ($points === null) {
            throw new InvalidArgumentException("Unknown hidden achievement [{$name}].");
        }

        return $this->unlock($user, "hidden_{$name}", (int) $points, $awardPoints);
    }

    /** Unlock the viral comment badge once a comment draws enough likes from others. */
    public function evaluateComment(Comment $comment): void
    {
        $likes = DB::table('comment_likes')
            ->where('comment_id', $comment->id)
            ->where('user_id', '!=', $comment->user_id)
            ->count();

        if ($comment->status === 'visible' && $comment->user !== null && $likes >= (int) config('gamification.viral_comment_likes')) {
            $this->unlockHidden($comment->user, 'viral_comment');
        }
    }

    /**
     * Every tiered badge with progress, this month's seasonal badges, earlier seasonal
     * badges the user holds, and hidden badges once they are unlocked.
     *
     * @return list<array{key: string, group: string, category: string, tier: string|null, period: string|null, threshold: int, points: int, current: int, unlocked_at: string|null, featured: bool, holders_percent: float}>
     */
    public function progress(User $user): array
    {
        $owned = UserAchievement::query()->where('user_id', $user->id)->orderBy('unlocked_at')->get()->keyBy('achievement_key');
        $holders = $this->holdersPercent();
        $badge = fn (string $key, array $fields): array => [
            'key' => $key,
            ...$fields,
            'unlocked_at' => $owned->get($key)?->unlocked_at->toIso8601String(),
            'featured' => (bool) $owned->get($key)?->featured,
            'holders_percent' => $holders[$key] ?? 0.0,
        ];
        $badges = [];

        foreach (config('gamification.achievements') as $category => $tiers) {
            $current = $this->count($user, $category);

            foreach ($tiers as $tier => $threshold) {
                $badges[] = $badge("{$category}_{$tier}", [
                    'group' => 'tier', 'category' => $category, 'tier' => $tier, 'period' => null,
                    'threshold' => $threshold, 'points' => (int) config("gamification.achievement_points.{$tier}"), 'current' => $current,
                ]);
            }
        }

        $month = $this->currentMonth();
        $currentSeason = [];

        foreach (config('gamification.seasonal') as $category => $threshold) {
            $key = $this->seasonKey($month, $category);
            $currentSeason[$key] = true;
            $badges[] = $badge($key, [
                'group' => 'season', 'category' => $category, 'tier' => null, 'period' => $month->format('Y-m'),
                'threshold' => $threshold, 'points' => (int) config('gamification.seasonal_points'),
                'current' => $this->monthlyCount($user, $category, $month),
            ]);
        }

        foreach ($owned->keys() as $key) {
            if (preg_match('/^season_(\d{4})_(\d{2})_(\w+)$/', $key, $match) && ! isset($currentSeason[$key])) {
                $badges[] = $badge($key, [
                    'group' => 'season', 'category' => $match[3], 'tier' => null, 'period' => "{$match[1]}-{$match[2]}",
                    'threshold' => (int) config("gamification.seasonal.{$match[3]}", 0), 'points' => (int) config('gamification.seasonal_points'),
                    'current' => (int) config("gamification.seasonal.{$match[3]}", 0),
                ]);
            } elseif (str_starts_with($key, 'hidden_')) {
                $badges[] = $badge($key, [
                    'group' => 'hidden', 'category' => 'hidden', 'tier' => substr($key, 7), 'period' => null,
                    'threshold' => 1, 'points' => (int) config('gamification.hidden.'.substr($key, 7), 0), 'current' => 1,
                ]);
            }
        }

        return $badges;
    }

    /** How many hidden badges the user has yet to discover. */
    public function hiddenLockedCount(User $user): int
    {
        $keys = array_map(fn (string $name): string => "hidden_{$name}", array_keys(config('gamification.hidden')));

        return count($keys) - UserAchievement::query()->where('user_id', $user->id)->whereIn('achievement_key', $keys)->count();
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

    /** The all-time count a category's thresholds are measured against. */
    public function count(User $user, string $category): int
    {
        return match ($category) {
            'streak' => (int) (Wallet::query()->where('user_id', $user->id)->value('longest_streak') ?? 0),
            'curator' => MasterObject::query()->where('submitted_by', $user->id)->whereIn('status', ['verified', 'merged'])->count(),
            'writer' => ObjectContentRevision::query()->where('author_id', $user->id)->where('status', 'published')->count(),
            'voice' => $this->receivedLikes()->where('comments.user_id', $user->id)->count(),
            'guardian' => Report::query()->where('reporter_id', $user->id)->where('status', 'resolved')->count(),
            default => throw new InvalidArgumentException("Unknown achievement category [{$category}]."),
        };
    }

    /** The count of a seasonal category within one Jakarta month. */
    public function monthlyCount(User $user, string $category, CarbonImmutable $month): int
    {
        return $this->monthlyQuery($category, $month)->where($this->contributorColumn($category), $user->id)->count();
    }

    /**
     * The top contributors of each seasonal category for one Jakarta month.
     *
     * @return array<string, list<array{user: array{id: int, name: string, username: string, avatar_url: string|null}, count: int}>>
     */
    public function hallOfFame(CarbonImmutable $month): array
    {
        $ttl = $month->isSameMonth($this->currentMonth()) ? now()->addMinutes(10) : now()->addDay();

        return Cache::remember('achievements.hall-of-fame.'.$month->format('Y-m'), $ttl, function () use ($month): array {
            $board = [];

            foreach (array_keys(config('gamification.seasonal')) as $category) {
                $column = $this->contributorColumn($category);
                $counts = $this->monthlyQuery($category, $month)
                    ->selectRaw("{$column} as contributor_id, COUNT(*) as total")
                    ->groupBy($column)
                    ->orderByDesc('total')
                    ->orderBy($column)
                    ->limit((int) config('gamification.hall_of_fame_size'))
                    ->pluck('total', 'contributor_id');
                $users = User::query()->whereIn('id', $counts->keys())->get()->keyBy('id');

                $board[$category] = $counts
                    ->filter(fn (mixed $total, int|string $userId): bool => $users->has($userId))
                    ->map(fn (mixed $total, int|string $userId): array => [
                        'user' => $users->get($userId)->only('id', 'name', 'username', 'avatar_url'),
                        'count' => (int) $total,
                    ])
                    ->values()
                    ->all();
            }

            return $board;
        });
    }

    /** The first day of the current Jakarta month. */
    public function currentMonth(): CarbonImmutable
    {
        return CarbonImmutable::now('Asia/Jakarta')->startOfMonth();
    }

    private function seasonKey(CarbonImmutable $month, string $category): string
    {
        return 'season_'.$month->format('Y_m')."_{$category}";
    }

    /** Rows credited to contributors of a seasonal category within a Jakarta month. */
    private function monthlyQuery(string $category, CarbonImmutable $month): Builder
    {
        $range = [$month->startOfMonth()->utc(), $month->endOfMonth()->utc()];

        return match ($category) {
            'curator' => DB::table('objects')->whereIn('status', ['verified', 'merged'])->whereBetween('verified_at', $range),
            'writer' => DB::table('object_content_revisions')->where('status', 'published')->whereBetween('published_at', $range),
            'voice' => $this->receivedLikes()->whereBetween('comment_likes.created_at', $range),
            default => throw new InvalidArgumentException("No seasonal achievement for [{$category}]."),
        };
    }

    private function contributorColumn(string $category): string
    {
        return match ($category) {
            'curator' => 'submitted_by',
            'writer' => 'author_id',
            'voice' => 'comments.user_id',
            default => throw new InvalidArgumentException("No seasonal achievement for [{$category}]."),
        };
    }

    /** Likes on visible comments, excluding authors liking their own comments. */
    private function receivedLikes(): Builder
    {
        return DB::table('comment_likes')
            ->join('comments', 'comments.id', '=', 'comment_likes.comment_id')
            ->where('comments.status', 'visible')
            ->whereColumn('comment_likes.user_id', '!=', 'comments.user_id');
    }

    /** Record a badge and pay its points once. Returns whether this call unlocked it. */
    private function unlock(User $user, string $key, int $points, bool $awardPoints): bool
    {
        $isNew = DB::transaction(function () use ($user, $key, $points, $awardPoints): bool {
            $achievement = UserAchievement::query()->createOrFirst(
                ['user_id' => $user->id, 'achievement_key' => $key],
                ['unlocked_at' => now('UTC')],
            );

            if (! $achievement->wasRecentlyCreated) {
                return false;
            }

            if ($awardPoints && $points > 0 && $user->canParticipate()) {
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

            return true;
        }, 3);

        if ($isNew) {
            Cache::forget(self::HOLDERS_CACHE_KEY);

            if ($awardPoints) {
                app(NotificationService::class)->achievementUnlocked($user, $key);
            }
        }

        return $isNew;
    }

    /** Make the member a trusted contributor once they hold the trust badge, unless revoked before. */
    private function grantTrustIfEligible(User $user): void
    {
        if ($user->trusted_at !== null || $user->trust_revoked_at !== null || ! $user->canParticipate()) {
            return;
        }

        $holdsBadge = UserAchievement::query()
            ->where('user_id', $user->id)
            ->where('achievement_key', config('gamification.trusted_badge'))
            ->exists();

        if ($holdsBadge) {
            $user->forceFill(['trusted_at' => now('UTC')])->save();
        }
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
}
