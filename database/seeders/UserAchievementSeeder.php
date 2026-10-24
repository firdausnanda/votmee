<?php

namespace Database\Seeders;

use App\Models\PointLedger;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\Wallet;
use App\Services\AchievementService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserAchievementSeeder extends Seeder
{
    /** Members who once claimed their daily bonus right after midnight. */
    private const NIGHT_OWLS = ['demouser', 'yogaaditya', 'jackreynolds'];

    /** @var array<int, CarbonImmutable> */
    private array $nightOwlClaims = [];

    /** @var array<string, list<CarbonImmutable>> */
    private array $milestones = [];

    /**
     * Unlock every badge the seeded activity has earned through the real achievement rules,
     * then date each one to the moment its threshold was reached and pay its points then.
     */
    public function run(AchievementService $achievements): void
    {
        Cache::forget('achievements.first-month');
        $users = User::orderBy('id')->get();
        $this->seedNightOwls($users);

        foreach ($users as $user) {
            foreach (array_keys(config('gamification.achievements')) as $category) {
                $achievements->evaluate($user, $category, awardPoints: false);
            }
        }

        for ($month = $achievements->firstMonth(); $month->lessThan($achievements->currentMonth()); $month = $month->addMonth()) {
            $this->seedSeason($achievements, $users, $month);
            $this->seedChampions($achievements, $users, $month);
        }

        foreach ($users as $user) {
            $this->settle($user);
        }

        $this->feature($achievements, $users);
        Cache::forget('achievements.holders');
        Cache::forget('achievements.hall-of-fame.'.$achievements->currentMonth()->format('Y-m'));
        PointLedgerSeeder::syncBalances();
    }

    /**
     * Move one earlier daily claim of each night owl to just after midnight, which is
     * what unlocks the hidden badge in the live claim flow.
     *
     * @param  Collection<int, User>  $users
     */
    private function seedNightOwls(Collection $users): void
    {
        foreach ($users->whereIn('username', self::NIGHT_OWLS) as $user) {
            $claim = PointLedger::where('user_id', $user->id)->where('type', 'daily_bonus')
                ->where('claimed_on', '<', now('Asia/Jakarta')->toDateString())
                ->inRandomOrder()->first();

            if ($claim === null || UserAchievement::where('user_id', $user->id)->where('achievement_key', 'hidden_night_owl')->exists()) {
                continue;
            }

            $claimedAt = CarbonImmutable::parse($claim->claimed_on, 'Asia/Jakarta')->setTime(0, mt_rand(1, 50), mt_rand(0, 59))->utc();
            $claim->forceFill(['created_at' => $claimedAt, 'updated_at' => $claimedAt])->save();
            UserAchievement::firstOrCreate(['user_id' => $user->id, 'achievement_key' => 'hidden_night_owl'], ['unlocked_at' => $claimedAt]);
            $this->nightOwlClaims[$user->id] = $claimedAt;
        }
    }

    /**
     * Seasonal badges of a finished month, which the live flow unlocked while it was running.
     *
     * @param  Collection<int, User>  $users
     */
    private function seedSeason(AchievementService $achievements, Collection $users, CarbonImmutable $month): void
    {
        foreach (config('gamification.seasonal') as $category => $threshold) {
            foreach ($users as $user) {
                if ($achievements->monthlyCount($user, $category, $month) >= $threshold) {
                    UserAchievement::firstOrCreate(
                        ['user_id' => $user->id, 'achievement_key' => 'season_'.$month->format('Y_m')."_{$category}"],
                        ['unlocked_at' => now('UTC')],
                    );
                }
            }
        }
    }

    /**
     * Crown a finished month's top contributors the way the monthly command does, without
     * sending the notifications.
     *
     * @param  Collection<int, User>  $users
     */
    private function seedChampions(AchievementService $achievements, Collection $users, CarbonImmutable $month): void
    {
        Cache::forget('achievements.hall-of-fame.'.$month->format('Y-m'));

        foreach ($achievements->hallOfFame($month) as $category => $entries) {
            $eligible = array_values(array_filter($entries, fn (array $entry): bool => $users->firstWhere('id', $entry['user']['id'])?->canParticipate() ?? false));
            $topCount = $eligible[0]['count'] ?? 0;

            if ($topCount < (int) config("gamification.seasonal.{$category}")) {
                continue;
            }

            foreach ($eligible as $entry) {
                if ($entry['count'] === $topCount) {
                    UserAchievement::firstOrCreate(
                        ['user_id' => $entry['user']['id'], 'achievement_key' => 'champion_'.$month->format('Y_m')."_{$category}"],
                        ['unlocked_at' => now('UTC')],
                    );
                }
            }
        }
    }

    /** Date every badge the member has not been paid for yet and pay its points at that moment. */
    private function settle(User $user): void
    {
        $this->milestones = [];

        foreach (UserAchievement::where('user_id', $user->id)->get() as $achievement) {
            $idempotencyKey = "achievement:{$user->id}:{$achievement->achievement_key}";

            if (PointLedger::where('idempotency_key', $idempotencyKey)->exists()) {
                continue;
            }

            [$unlockedAt, $points] = $this->moment($user, $achievement->achievement_key);
            $unlockedAt = $this->past($unlockedAt->addMinutes(mt_rand(0, 2))->addSeconds(mt_rand(0, 59)));
            $seenAt = $unlockedAt->lessThan(now('UTC')->subDay()) ? $this->past($unlockedAt->addMinutes(mt_rand(10, 720))) : null;

            $achievement->forceFill([
                'unlocked_at' => $unlockedAt,
                'seen_at' => $seenAt,
                'created_at' => $unlockedAt,
                'updated_at' => $seenAt ?? $unlockedAt,
            ])->save();

            if ($points > 0 && $user->canParticipate()) {
                (new PointLedger)->forceFill([
                    'wallet_id' => Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0])->id,
                    'user_id' => $user->id,
                    'amount' => $points,
                    'type' => 'achievement',
                    'idempotency_key' => $idempotencyKey,
                    'created_at' => $unlockedAt,
                    'updated_at' => $unlockedAt,
                ])->save();
            }
        }
    }

    /**
     * When a badge was earned and the points it pays.
     *
     * @return array{0: CarbonImmutable, 1: int}
     */
    private function moment(User $user, string $key): array
    {
        if (preg_match('/^(\w+)_(bronze|silver|gold)$/', $key, $match)) {
            $threshold = (int) config("gamification.achievements.{$match[1]}.{$match[2]}");

            return [$this->milestones($user, $match[1])[$threshold - 1] ?? now('UTC')->toImmutable(), (int) config("gamification.achievement_points.{$match[2]}")];
        }

        if (preg_match('/^(season|champion)_(\d{4})_(\d{2})_(\w+)$/', $key, $match)) {
            $month = CarbonImmutable::create((int) $match[2], (int) $match[3], 1, 0, 0, 0, 'Asia/Jakarta');

            if ($match[1] === 'champion') {
                return [$month->addMonth()->setTime(1, mt_rand(0, 4))->utc(), (int) config('gamification.champion_points')];
            }

            $inMonth = array_values(array_filter(
                $this->milestones($user, $match[4]),
                fn (CarbonImmutable $at): bool => $at->betweenIncluded($month->utc(), $month->endOfMonth()->utc()),
            ));

            return [$inMonth[(int) config("gamification.seasonal.{$match[4]}") - 1] ?? now('UTC')->toImmutable(), (int) config('gamification.seasonal_points')];
        }

        $name = substr($key, strlen('hidden_'));

        return [$this->nightOwlClaims[$user->id] ?? now('UTC')->toImmutable(), (int) config("gamification.hidden.{$name}", 0)];
    }

    /**
     * When each unit of a category's count was reached, oldest first: entry N is the moment
     * the member's count became N + 1.
     *
     * @return list<CarbonImmutable>
     */
    private function milestones(User $user, string $category): array
    {
        if (isset($this->milestones[$category])) {
            return $this->milestones[$category];
        }

        $times = match ($category) {
            'streak' => $this->streakMilestones($user),
            'curator' => DB::table('objects')->where('submitted_by', $user->id)->whereIn('status', ['verified', 'merged'])->pluck('verified_at'),
            'writer' => DB::table('object_content_revisions')->where('author_id', $user->id)->where('status', 'published')->pluck('published_at'),
            'voice' => DB::table('comment_likes')->join('comments', 'comments.id', '=', 'comment_likes.comment_id')
                ->where('comments.user_id', $user->id)->where('comments.status', 'visible')
                ->whereColumn('comment_likes.user_id', '!=', 'comments.user_id')->pluck('comment_likes.created_at'),
            'guardian' => DB::table('reports')->where('reporter_id', $user->id)->where('status', 'resolved')->pluck('updated_at'),
            'ranker' => DB::table('ranking_posts')->where('creator_id', $user->id)->where('status', 'published')->pluck('published_at'),
            'voter' => DB::table('support_events')->where('user_id', $user->id)->where('status', 'valid')->pluck('occurred_at'),
            'influencer' => DB::table('support_events')
                ->join('ranking_entries', 'ranking_entries.id', '=', 'support_events.ranking_entry_id')
                ->join('ranking_posts', 'ranking_posts.id', '=', 'ranking_entries.ranking_post_id')
                ->where('ranking_posts.creator_id', $user->id)->where('ranking_posts.status', 'published')
                ->where('support_events.source', 'support')->where('support_events.status', 'valid')
                ->whereColumn('support_events.user_id', '!=', 'ranking_posts.creator_id')
                ->groupBy('support_events.user_id')->selectRaw('MIN(support_events.occurred_at) as first_support')->pluck('first_support'),
        };

        return $this->milestones[$category] = collect($times)
            ->filter()
            ->map(fn (mixed $at): CarbonImmutable => $at instanceof CarbonImmutable ? $at : CarbonImmutable::parse((string) $at, 'UTC'))
            ->sort()
            ->values()
            ->all();
    }

    /** @return list<CarbonImmutable> the claim at which each streak length was first reached */
    private function streakMilestones(User $user): array
    {
        $reached = [];
        $streak = 0;
        $previous = null;

        foreach (PointLedger::where('user_id', $user->id)->where('type', 'daily_bonus')->orderBy('claimed_on')->get() as $claim) {
            $day = CarbonImmutable::parse($claim->claimed_on);
            $streak = $previous !== null && $previous->addDay()->isSameDay($day) ? $streak + 1 : 1;
            $previous = $day;

            if ($streak > count($reached)) {
                $reached[] = CarbonImmutable::parse($claim->created_at, 'UTC');
            }
        }

        return $reached;
    }

    /**
     * Most members show off up to three of their best badges; some never set them.
     *
     * @param  Collection<int, User>  $users
     */
    private function feature(AchievementService $achievements, Collection $users): void
    {
        $rank = fn (string $key): int => match (true) {
            str_starts_with($key, 'champion_') => 0,
            str_ends_with($key, '_gold') => 1,
            str_starts_with($key, 'hidden_') => 2,
            str_ends_with($key, '_silver') => 3,
            str_starts_with($key, 'season_') => 4,
            default => 5,
        };

        foreach ($users as $user) {
            if (($user->username !== 'demouser' && crc32($user->username) % 3 === 0)
                || UserAchievement::where('user_id', $user->id)->where('featured', true)->exists()) {
                continue;
            }

            $keys = UserAchievement::where('user_id', $user->id)->orderByDesc('unlocked_at')->pluck('achievement_key')
                ->sortBy($rank)
                ->take(UserAchievement::MAX_FEATURED)
                ->values()
                ->all();

            if ($keys !== []) {
                $achievements->setFeatured($user, $keys);
            }
        }
    }

    private function past(CarbonImmutable $at): CarbonImmutable
    {
        return $at->isFuture() ? now('UTC')->toImmutable()->subMinutes(mt_rand(5, 60)) : $at;
    }
}
