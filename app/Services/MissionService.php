<?php

namespace App\Services;

use App\Models\MissionCompletion;
use App\Models\PointLedger;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Random\Engine\Mt19937;
use Random\Randomizer;

class MissionService
{
    /** Missions, like the daily bonus, follow the Jakarta calendar. */
    private const TIMEZONE = 'Asia/Jakarta';

    /** The daily reward for finishing every assigned mission; weekly "daily_complete" counts these. */
    public const PERFECT_DAY = 'perfect_day';

    public function __construct(private AchievementService $achievements) {}

    /**
     * Re-check a mission after the member did something that may advance it, and
     * reserve its reward once the progress reaches the target. Content missions
     * pass the content's creation time, which picks the week they count toward.
     */
    public function record(User $user, string $mission, ?CarbonInterface $contentCreatedAt = null): ?MissionCompletion
    {
        $definition = $this->definition($mission);

        if (! $user->canParticipate()) {
            return null;
        }

        $period = $this->periodOf($mission);
        $start = $period === 'daily' ? $this->today() : $this->weekOf($contentCreatedAt ?? now());

        if (now()->greaterThan($this->evaluationDeadline($mission, $start))) {
            return null;
        }

        if ($period === 'daily' && ! in_array($mission, $this->dailyAssignment($user, $start), true)) {
            return null;
        }

        if ($this->progress($user, $mission, $start) < $definition['target']) {
            return null;
        }

        $completion = $this->reserve($user, $mission, $period, $start, (int) $definition['points']);

        if ($completion !== null && $period === 'daily') {
            $this->recordPerfectDay($user, $start);
        }

        return $completion;
    }

    /**
     * Pay a reserved reward into the wallet. Content rewards are checked again so
     * content hidden since completion no longer pays; expired rewards never pay.
     */
    public function claim(User $user, int $completionId): ?PointLedger
    {
        abort_unless($user->canParticipate(), 403);

        $completion = MissionCompletion::query()->where('user_id', $user->id)->whereKey($completionId)->firstOrFail();

        if ($completion->claimed_at !== null) {
            return null;
        }

        $start = CarbonImmutable::parse($completion->completed_on->toDateString(), self::TIMEZONE);

        if (now()->greaterThan($this->claimDeadline($completion->mission, $completion->period, $start))) {
            throw ValidationException::withMessages(['mission' => __('votmee.mission_expired')]);
        }

        if ($completion->period !== 'daily' && $this->progress($user, $completion->mission, $start) < $this->definition($completion->mission)['target']) {
            $completion->delete();

            throw ValidationException::withMessages(['mission' => __('votmee.mission_invalid')]);
        }

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
     * Today's assigned missions, this week's missions and challenges, and older
     * rewards still waiting for a claim.
     *
     * @return array{daily: list<array<string, mixed>>, perfect_day: array<string, mixed>, weekly: list<array<string, mixed>>, challenges: list<array<string, mixed>>, pending: list<array{id: int, key: string, period: string, amount: int, completed_on: string, expires_at: string}>, day_ends_at: string, week_ends_at: string}
     */
    public function overview(User $user): array
    {
        $today = $this->today();
        $week = $this->weekOf(now());
        $reserved = $this->completions($user, $today, $week);
        $legacy = $this->legacyRewards($user, $today);
        $daily = collect($this->dailyAssignment($user, $today))
            ->merge($reserved->where('period', 'daily')->where('completed_on', $today->toDateString())->pluck('mission'))
            ->reject(fn (string $mission): bool => $mission === self::PERFECT_DAY || ! isset(config('gamification.missions.daily.pool')[$mission]))
            ->unique()
            ->map(fn (string $mission): array => $this->item($user, $mission, $today, $reserved, $legacy->get($mission)))
            ->values();
        $perfectDay = $reserved->first(fn (MissionCompletion $completion): bool => $completion->mission === self::PERFECT_DAY && $completion->completed_on->toDateString() === $today->toDateString());
        $weekly = fn (string $period): array => collect(config("gamification.missions.{$this->configGroup($period)}.pool"))
            ->keys()
            ->map(fn (string $mission): array => $this->item($user, $mission, $week, $reserved))
            ->all();

        return [
            'daily' => $daily->all(),
            'perfect_day' => [
                'key' => self::PERFECT_DAY,
                'period' => 'daily',
                'target' => $daily->count(),
                'progress' => $daily->whereNotNull('earned')->count(),
                'points' => (int) config('gamification.missions.daily.complete_bonus'),
                'earned' => $perfectDay?->amount,
                'completion_id' => $perfectDay?->id,
                'claimed' => $perfectDay?->claimed_at !== null,
            ],
            'weekly' => $weekly('weekly'),
            'challenges' => $weekly('challenge'),
            'pending' => $this->pending($user, $today, $week),
            'day_ends_at' => $today->addDay()->toIso8601String(),
            'week_ends_at' => $week->addWeek()->toIso8601String(),
        ];
    }

    /**
     * The missions a member gets on a Jakarta day: the fixed ones plus a rotating
     * pick from the pool. The pick is stable for that member and day.
     *
     * @return list<string>
     */
    public function dailyAssignment(User $user, CarbonImmutable $day): array
    {
        $pool = array_keys(config('gamification.missions.daily.pool'));
        $always = array_values(array_intersect(config('gamification.missions.daily.always'), $pool));
        $rotating = array_values(array_diff($pool, $always));
        $randomizer = new Randomizer(new Mt19937(crc32("{$user->id}:{$day->toDateString()}")));
        $slots = max(0, (int) config('gamification.missions.daily.slots') - count($always));

        return [...$always, ...array_slice($randomizer->shuffleArray($rotating), 0, $slots)];
    }

    /** The member's progress on a mission for the period starting at $start. */
    public function progress(User $user, string $mission, CarbonImmutable $start): int
    {
        $day = [$start->utc(), $start->endOfDay()->utc()];
        $week = [$start->utc(), $start->addDays(6)->endOfDay()->utc()];
        $reviewedBy = $start->addWeek()->addDays((int) config('gamification.missions.weekly.review_grace_days'))->utc();

        return match ($mission) {
            'quick_vote' => DB::table('support_events')->where('user_id', $user->id)->where('source', 'quick_vote')
                ->where('status', 'valid')->whereDate('vote_date', $start->toDateString())->count(),
            'support' => DB::table('support_events')->where('user_id', $user->id)->where('source', 'support')
                ->where('status', 'valid')->whereBetween('occurred_at', $day)->count(),
            'write_comments' => $this->visibleComments()->where('user_id', $user->id)->whereBetween('created_at', $day)->count(),
            'article_comment' => $this->visibleComments()->where('user_id', $user->id)->whereNotNull('article_id')->whereBetween('created_at', $day)->count(),
            'like_comments' => DB::table('comment_likes')->join('comments', 'comments.id', '=', 'comment_likes.comment_id')
                ->where('comment_likes.user_id', $user->id)->where('comments.user_id', '!=', $user->id)->where('comments.status', 'visible')
                ->whereBetween('comment_likes.created_at', $day)->distinct()->count('comment_likes.comment_id'),
            'save_ranking' => DB::table('saved_rankings')->join('ranking_posts', 'ranking_posts.id', '=', 'saved_rankings.ranking_post_id')
                ->where('saved_rankings.user_id', $user->id)->where('ranking_posts.creator_id', '!=', $user->id)
                ->where('ranking_posts.status', 'published')->whereBetween('saved_rankings.created_at', $day)->count(),
            'mention_friend' => DB::table('comment_mentions')->join('comments', 'comments.id', '=', 'comment_mentions.comment_id')
                ->where('comments.user_id', $user->id)->where('comment_mentions.user_id', '!=', $user->id)->where('comments.status', 'visible')
                ->whereBetween('comments.created_at', $day)->distinct()->count('comment_mentions.comment_id'),
            'create_ranking' => DB::table('ranking_posts')->where('creator_id', $user->id)->where('status', 'published')
                ->whereBetween('created_at', $week)->count(),
            'write_article' => DB::table('object_articles')->where('author_id', $user->id)->whereNotNull('published_revision_id')
                ->whereBetween('created_at', $week)
                ->whereExists(fn (Builder $query) => $query->from('object_content_revisions')
                    ->whereColumn('object_content_revisions.article_id', 'object_articles.id')
                    ->where('status', 'published')->where('published_at', '<=', $reviewedBy))
                ->count(),
            'improve_summary' => DB::table('object_content_revisions')->where('author_id', $user->id)->whereNull('article_id')
                ->where('status', 'published')->whereBetween('created_at', $week)->where('published_at', '<=', $reviewedBy)->count(),
            'submit_object' => DB::table('objects')->where('submitted_by', $user->id)->where('status', 'verified')
                ->whereBetween('created_at', $week)->where('verified_at', '<=', $reviewedBy)->count(),
            'daily_complete' => MissionCompletion::query()->where('user_id', $user->id)->where('mission', self::PERFECT_DAY)
                ->whereBetween('completed_on', [$start->toDateString(), $start->addDays(6)->toDateString()])->count(),
            'popular_ranking' => $this->bestContent($this->rankingsCreated($user, $week), fn (object $post, array $window): Collection => DB::table('support_events')
                ->join('ranking_entries', 'ranking_entries.id', '=', 'support_events.ranking_entry_id')
                ->where('ranking_entries.ranking_post_id', $post->id)->where('support_events.source', 'support')
                ->where('support_events.status', 'valid')->whereBetween('support_events.occurred_at', $window)
                ->get(['support_events.user_id', 'support_events.occurred_at as acted_at']), $user, perUser: 1),
            'discussed_ranking' => $this->bestContent($this->rankingsCreated($user, $week), fn (object $post, array $window): Collection => $this->visibleComments()
                ->where('ranking_post_id', $post->id)->whereBetween('created_at', $window)
                ->get(['user_id', 'created_at as acted_at']), $user, perUser: $this->discussionShare()),
            'read_article' => $this->bestContent($this->articlesCreated($user, $week), fn (object $article, array $window): Collection => $this->visibleComments()
                ->where('article_id', $article->id)->whereBetween('created_at', $window)
                ->get(['user_id', 'created_at as acted_at']), $user, perUser: 1),
            'liked_comment' => $this->bestContent($this->visibleComments()->where('user_id', $user->id)->whereBetween('created_at', $week)
                ->get(['id', 'created_at as published_at']), fn (object $comment, array $window): Collection => DB::table('comment_likes')
                ->where('comment_id', $comment->id)->whereBetween('created_at', $window)
                ->get(['user_id', 'created_at as acted_at']), $user, perUser: 1),
            default => throw new InvalidArgumentException("Unknown mission [{$mission}]."),
        };
    }

    /** @return array{target: int, points: int, distinct?: int} */
    private function definition(string $mission): array
    {
        $definition = config("gamification.missions.{$this->configGroup($this->periodOf($mission))}.pool.{$mission}");

        if ($definition === null) {
            throw new InvalidArgumentException("Unknown mission [{$mission}].");
        }

        return $definition;
    }

    /** Whether a mission resets daily, weekly, or is a challenge on the week's content. */
    private function periodOf(string $mission): string
    {
        return match (true) {
            isset(config('gamification.missions.daily.pool')[$mission]), $mission === self::PERFECT_DAY => 'daily',
            isset(config('gamification.missions.weekly.pool')[$mission]) => 'weekly',
            isset(config('gamification.missions.challenges.pool')[$mission]) => 'challenge',
            default => throw new InvalidArgumentException("Unknown mission [{$mission}]."),
        };
    }

    private function configGroup(string $period): string
    {
        return $period === 'challenge' ? 'challenges' : $period;
    }

    /** The last moment a mission of the period starting at $start can still be completed. */
    private function evaluationDeadline(string $mission, CarbonImmutable $start): CarbonImmutable
    {
        return match ($this->periodOf($mission)) {
            'daily' => $start->endOfDay(),
            'weekly' => $start->addWeek()->addDays((int) config('gamification.missions.weekly.review_grace_days')),
            default => $start->addWeek()->addDays((int) config('gamification.missions.challenges.window_days')),
        };
    }

    /** The last moment a reserved reward can be claimed. */
    private function claimDeadline(string $mission, string $period, CarbonImmutable $start): CarbonImmutable
    {
        $closesAt = $period === 'daily' ? $start->endOfDay() : $this->evaluationDeadline($mission, $start);

        return $closesAt->addDays((int) config('gamification.missions.claim_days'));
    }

    /**
     * Reserve a mission's reward once per period. Daily and weekly rewards share
     * their period's cap; the perfect day bonus and challenges stand outside it.
     */
    private function reserve(User $user, string $mission, string $period, CarbonImmutable $start, int $points): ?MissionCompletion
    {
        return DB::transaction(function () use ($user, $mission, $period, $start, $points): ?MissionCompletion {
            Wallet::lockedFor($user);
            $date = $start->toDateString();
            $reserved = MissionCompletion::query()->where('user_id', $user->id)->where('period', $period)
                ->whereDate('completed_on', $date)->pluck('amount', 'mission')->map(fn (mixed $amount): int => (int) $amount);

            if ($period === 'daily') {
                $reserved = $reserved->union($this->legacyRewards($user, $start));
            }

            if ($reserved->has($mission)) {
                return null;
            }

            $cap = match (true) {
                $mission === self::PERFECT_DAY, $period === 'challenge' => null,
                default => (int) config("gamification.missions.{$period}.cap"),
            };
            $amount = $cap === null ? $points : min($points, $cap - (int) $reserved->except(self::PERFECT_DAY)->sum());

            if ($amount < 1) {
                return null;
            }

            return MissionCompletion::create([
                'user_id' => $user->id,
                'mission' => $mission,
                'period' => $period,
                'completed_on' => $date,
                'amount' => $amount,
            ]);
        }, 3);
    }

    /** Reserve the perfect day bonus once every mission assigned for the day is done. */
    private function recordPerfectDay(User $user, CarbonImmutable $day): void
    {
        $done = MissionCompletion::query()->where('user_id', $user->id)->where('period', 'daily')
            ->whereDate('completed_on', $day->toDateString())->pluck('mission')
            ->merge($this->legacyRewards($user, $day)->keys());

        if (array_diff($this->dailyAssignment($user, $day), $done->all()) !== []) {
            return;
        }

        if ($this->reserve($user, self::PERFECT_DAY, 'daily', $day, (int) config('gamification.missions.daily.complete_bonus')) !== null) {
            $this->achievements->unlockHidden($user, 'perfect_day');
            $this->record($user, 'daily_complete', $day);
        }
    }

    /**
     * The best progress among the member's content: actions by other members
     * within the window after each item was published, counting each member at
     * most $perUser times and only once their account was old enough.
     *
     * @param  Collection<int, object>  $contents
     * @param  callable(object, array{CarbonImmutable, CarbonImmutable}): Collection<int, object>  $actions
     */
    private function bestContent(Collection $contents, callable $actions, User $owner, int $perUser): int
    {
        $windowDays = (int) config('gamification.missions.challenges.window_days');
        $minimumAge = (int) config('gamification.missions.challenges.min_account_age_days');

        return (int) $contents->map(function (object $content) use ($actions, $owner, $perUser, $windowDays, $minimumAge): int {
            $publishedAt = CarbonImmutable::parse($content->published_at, 'UTC');
            $rows = $actions($content, [$publishedAt, $publishedAt->addDays($windowDays)])
                ->reject(fn (object $row): bool => (int) $row->user_id === $owner->id);
            $members = User::query()->whereIn('id', $rows->pluck('user_id')->unique())
                ->whereNotNull('email_verified_at')
                ->where(fn ($query) => $query->whereNull('restricted_until')->orWhere('restricted_until', '<', now('UTC')))
                ->pluck('created_at', 'id');

            return $rows
                ->filter(fn (object $row): bool => $members->has($row->user_id)
                    && $members->get($row->user_id)->lessThanOrEqualTo(CarbonImmutable::parse($row->acted_at, 'UTC')->subDays($minimumAge)))
                ->countBy('user_id')
                ->sum(fn (int $count): int => min($count, $perUser));
        })->max() ?? 0;
    }

    /** @return Collection<int, object> */
    private function rankingsCreated(User $user, array $week): Collection
    {
        return DB::table('ranking_posts')->where('creator_id', $user->id)->where('status', 'published')
            ->whereBetween('created_at', $week)->whereNotNull('published_at')->get(['id', 'published_at']);
    }

    /** Articles created in the week that are visible, with the time they were first published. */
    private function articlesCreated(User $user, array $week): Collection
    {
        return DB::table('object_articles')
            ->join('object_content_revisions', 'object_content_revisions.article_id', '=', 'object_articles.id')
            ->where('object_articles.author_id', $user->id)->whereNotNull('object_articles.published_revision_id')
            ->whereBetween('object_articles.created_at', $week)->where('object_content_revisions.status', 'published')
            ->groupBy('object_articles.id')
            ->selectRaw('object_articles.id, MIN(object_content_revisions.published_at) as published_at')
            ->get();
    }

    /** How many comments one member may add toward the discussion challenge. */
    private function discussionShare(): int
    {
        $definition = config('gamification.missions.challenges.pool.discussed_ranking');

        return (int) ceil($definition['target'] / max(1, $definition['distinct'] ?? $definition['target']));
    }

    private function visibleComments(): Builder
    {
        return DB::table('comments')->where('status', 'visible');
    }

    /**
     * One mission as the wallet shows it.
     *
     * @param  Collection<int, MissionCompletion>  $reserved
     * @return array{key: string, period: string, target: int, progress: int, points: int, earned: int|null, completion_id: int|null, claimed: bool}
     */
    private function item(User $user, string $mission, CarbonImmutable $start, Collection $reserved, ?int $legacyReward = null): array
    {
        $definition = $this->definition($mission);
        $completion = $reserved->first(fn (MissionCompletion $completion): bool => $completion->mission === $mission
            && $completion->completed_on->toDateString() === $start->toDateString());
        $earned = $completion?->amount ?? $legacyReward;

        return [
            'key' => $mission,
            'period' => $this->periodOf($mission),
            'target' => (int) $definition['target'],
            'progress' => $earned !== null ? (int) $definition['target'] : min((int) $definition['target'], $this->progress($user, $mission, $start)),
            'points' => (int) $definition['points'],
            'earned' => $earned,
            'completion_id' => $completion?->id,
            'claimed' => $completion?->claimed_at !== null || ($completion === null && $legacyReward !== null),
        ];
    }

    /** @return Collection<int, MissionCompletion> */
    private function completions(User $user, CarbonImmutable $today, CarbonImmutable $week): Collection
    {
        return MissionCompletion::query()->where('user_id', $user->id)
            ->where(fn ($query) => $query->where(fn ($query) => $query->where('period', 'daily')->whereDate('completed_on', $today->toDateString()))
                ->orWhere(fn ($query) => $query->where('period', '!=', 'daily')->whereDate('completed_on', $week->toDateString())))
            ->get();
    }

    /** @return list<array{id: int, key: string, period: string, amount: int, completed_on: string, expires_at: string}> */
    private function pending(User $user, CarbonImmutable $today, CarbonImmutable $week): array
    {
        return MissionCompletion::query()->where('user_id', $user->id)->whereNull('claimed_at')
            ->where(fn ($query) => $query->where(fn ($query) => $query->where('period', 'daily')->whereDate('completed_on', '<', $today->toDateString()))
                ->orWhere(fn ($query) => $query->where('period', '!=', 'daily')->whereDate('completed_on', '<', $week->toDateString())))
            ->orderByDesc('completed_on')->get()
            ->map(function (MissionCompletion $completion): array {
                $start = CarbonImmutable::parse($completion->completed_on->toDateString(), self::TIMEZONE);

                return [
                    'id' => $completion->id,
                    'key' => $completion->mission,
                    'period' => $completion->period,
                    'amount' => $completion->amount,
                    'completed_on' => $completion->completed_on->toDateString(),
                    'expires_at' => $this->claimDeadline($completion->mission, $completion->period, $start)->toIso8601String(),
                ];
            })
            ->filter(fn (array $completion): bool => now()->lessThanOrEqualTo($completion['expires_at']))
            ->values()
            ->all();
    }

    /**
     * Daily rewards paid straight into the wallet before rewards waited for a claim.
     *
     * @return Collection<string, int>
     */
    private function legacyRewards(User $user, CarbonImmutable $day): Collection
    {
        $keys = collect(array_keys(config('gamification.missions.daily.pool')))
            ->mapWithKeys(fn (string $mission): array => [$this->missionKey($user, $mission, $day->toDateString()) => $mission]);

        return PointLedger::query()->where('user_id', $user->id)->where('type', 'mission_reward')
            ->whereIn('idempotency_key', $keys->keys())
            ->whereNotIn('idempotency_key', MissionCompletion::query()->where('user_id', $user->id)->whereDate('completed_on', $day->toDateString())
                ->get()->map(fn (MissionCompletion $completion): string => $this->missionKey($user, $completion->mission, $day->toDateString())))
            ->pluck('amount', 'idempotency_key')
            ->mapWithKeys(fn (mixed $amount, string $key): array => [$keys->get($key) => (int) $amount]);
    }

    private function missionKey(User $user, string $mission, string $periodStart): string
    {
        return "mission:{$user->id}:{$mission}:{$periodStart}";
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)->startOfDay();
    }

    /** The Monday that starts the Jakarta week containing $moment. */
    private function weekOf(CarbonInterface $moment): CarbonImmutable
    {
        return CarbonImmutable::parse($moment)->setTimezone(self::TIMEZONE)->startOfWeek(CarbonInterface::MONDAY);
    }
}
