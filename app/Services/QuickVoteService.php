<?php

namespace App\Services;

use App\Models\QuickDuel;
use App\Models\RankingEntry;
use App\Models\RankingPost;
use App\Models\SupportEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuickVoteService
{
    public const DUELS_PER_DAY = 12;

    public const MIN_VOTES_FOR_RESULT = 10;

    private const OBJECT_COOLDOWN_DAYS = 7;

    private const CANDIDATE_POSTS = 300;

    public function __construct(private FeedService $feed, private MissionService $missions) {}

    /** The quick vote day follows the Jakarta calendar, like the daily bonus. */
    public function today(): string
    {
        return now('Asia/Jakarta')->toDateString();
    }

    /**
     * The duel shown to a viewer today: the one they already voted on, otherwise the
     * duel that best matches the categories they interact with.
     *
     * @return array<string, mixed>|null
     */
    public function duelFor(?User $user): ?array
    {
        $date = $this->today();
        $duels = $this->dailyDuels($date);

        if ($duels->isEmpty()) {
            return null;
        }

        $vote = $user ? $this->voteOn($user, $date) : null;
        $duel = $vote
            ? $duels->first(fn (QuickDuel $duel): bool => in_array($vote->ranking_entry_id, [$duel->entry_a_id, $duel->entry_b_id], true))
            : null;
        $duel ??= $this->pickFor($user, $duels);

        return $this->present($duel, $vote);
    }

    /** @return array<string, mixed> */
    public function vote(User $user, QuickDuel $duel, RankingEntry $entry): array
    {
        if (! $user->canParticipate()) {
            throw ValidationException::withMessages(['account' => __('votmee.account_ineligible')]);
        }

        $date = $this->today();

        if ($duel->duel_date->toDateString() !== $date) {
            throw ValidationException::withMessages(['duel' => __('votmee.quick_vote_closed')]);
        }

        if (! in_array($entry->id, [$duel->entry_a_id, $duel->entry_b_id], true)) {
            throw ValidationException::withMessages(['ranking_entry_id' => __('votmee.quick_vote_invalid_entry')]);
        }

        $duel->loadMissing(['post', 'entryA.object', 'entryB.object']);
        $entry->loadMissing('object');

        if ($duel->post->status !== 'published' || $entry->object->status !== 'verified') {
            throw ValidationException::withMessages(['duel' => __('votmee.quick_vote_closed')]);
        }

        $event = $this->voteOn($user, $date) ?? $this->record($user, $entry, $date);

        if ($event->ranking_entry_id !== $entry->id || $event->status !== 'valid') {
            throw ValidationException::withMessages(['ranking_entry_id' => __('votmee.quick_vote_done')]);
        }

        $this->missions->record($user, 'quick_vote');

        return $this->present($duel, $event);
    }

    /**
     * Build the day's duels once. Candidates are published rankings whose neighbouring
     * entries have the closest scores, spread across categories, and no object repeats
     * inside the cooldown window so exposure stays fair.
     */
    public function generate(string $date): int
    {
        $day = CarbonImmutable::parse($date, 'Asia/Jakarta');
        $recent = QuickDuel::query()
            ->where('duel_date', '>', $day->subDays(self::OBJECT_COOLDOWN_DAYS)->toDateString())
            ->where('duel_date', '<', $date)
            ->get(['ranking_post_id', 'entry_a_id', 'entry_b_id']);
        $recentObjectIds = RankingEntry::query()
            ->whereIn('id', $recent->pluck('entry_a_id')->merge($recent->pluck('entry_b_id')))
            ->pluck('object_id')
            ->flip();

        $posts = RankingPost::query()
            ->where('status', 'published')
            ->whereNotIn('id', $recent->pluck('ranking_post_id'))
            ->with('entries.object:id,status')
            ->latest('published_at')
            ->limit(self::CANDIDATE_POSTS)
            ->get();
        $scores = DB::table('support_events')
            ->where('status', 'valid')
            ->whereIn('ranking_entry_id', $posts->flatMap(fn (RankingPost $post) => $post->entries->pluck('id')))
            ->selectRaw('ranking_entry_id, SUM(amount) as points')
            ->groupBy('ranking_entry_id')
            ->pluck('points', 'ranking_entry_id');

        $candidates = $posts
            ->map(fn (RankingPost $post): ?array => $this->closestPair($post, $scores, $recentObjectIds))
            ->filter()
            ->groupBy('category_id')
            ->map(fn (Collection $group): Collection => $group->sortBy('gap')->values())
            ->sortKeys();

        $usedObjectIds = [];
        $created = 0;

        for ($round = 0; $created < self::DUELS_PER_DAY; $round++) {
            $remaining = false;

            foreach ($candidates as $group) {
                $candidate = $group[$round] ?? null;

                if ($candidate === null) {
                    continue;
                }

                $remaining = true;

                if ($created >= self::DUELS_PER_DAY || array_intersect($candidate['object_ids'], $usedObjectIds) !== []) {
                    continue;
                }

                $usedObjectIds = [...$usedObjectIds, ...$candidate['object_ids']];
                $duel = QuickDuel::query()->firstOrCreate(
                    ['duel_date' => $date, 'ranking_post_id' => $candidate['post_id']],
                    ['entry_a_id' => $candidate['entry_a_id'], 'entry_b_id' => $candidate['entry_b_id']],
                );
                $created += $duel->wasRecentlyCreated ? 1 : 0;
            }

            if (! $remaining) {
                break;
            }
        }

        return $created;
    }

    /** @return Collection<int, QuickDuel> */
    private function dailyDuels(string $date): Collection
    {
        $load = fn (): Collection => QuickDuel::query()
            ->where('duel_date', $date)
            ->with(['post:id,title,slug,category_id,status', 'entryA.object:id,canonical_name,slug', 'entryB.object:id,canonical_name,slug'])
            ->orderBy('id')
            ->get();
        $duels = $load();

        if ($duels->isEmpty() && Cache::add("quick-duels:generated:{$date}", true, 300)) {
            $this->generate($date);
            $duels = $load();
        }

        return $duels;
    }

    /**
     * @param  Collection<int, QuickDuel>  $duels
     */
    private function pickFor(?User $user, Collection $duels): QuickDuel
    {
        if ($user === null) {
            return $duels->first();
        }

        $affinity = $this->feed->affinityCategoryIds($user);

        return $duels->sortBy(function (QuickDuel $duel) use ($affinity, $user): int {
            $rank = array_search($duel->post->category_id, $affinity, true);

            return ($rank === false ? count($affinity) : $rank) * 10_000_000_000 + crc32($user->id.'|'.$duel->id);
        })->first();
    }

    /**
     * @param  Collection<int|string, mixed>  $scores
     * @param  Collection<string, int>  $recentObjectIds
     * @return array{post_id: string, category_id: int, gap: int, entry_a_id: int, entry_b_id: int, object_ids: list<string>}|null
     */
    private function closestPair(RankingPost $post, Collection $scores, Collection $recentObjectIds): ?array
    {
        $eligible = $post->entries
            ->filter(fn (RankingEntry $entry): bool => $entry->object?->status === 'verified' && ! $recentObjectIds->has($entry->object_id))
            ->sortByDesc(fn (RankingEntry $entry): int => (int) ($scores[$entry->id] ?? 0))
            ->take(4)
            ->values();
        $best = null;

        for ($index = 0; $index < $eligible->count() - 1; $index++) {
            $gap = (int) ($scores[$eligible[$index]->id] ?? 0) - (int) ($scores[$eligible[$index + 1]->id] ?? 0);

            if ($best === null || $gap < $best['gap']) {
                $best = ['gap' => $gap, 'index' => $index];
            }
        }

        if ($best === null) {
            return null;
        }

        [$first, $second] = [$eligible[$best['index']], $eligible[$best['index'] + 1]];

        return [
            'post_id' => $post->id,
            'category_id' => $post->category_id,
            'gap' => $best['gap'],
            'entry_a_id' => $first->id,
            'entry_b_id' => $second->id,
            'object_ids' => [$first->object_id, $second->object_id],
        ];
    }

    private function voteOn(User $user, string $date): ?SupportEvent
    {
        return SupportEvent::query()->where('user_id', $user->id)->where('vote_date', $date)->first();
    }

    /**
     * The (user, vote_date) unique key is what enforces one free vote per day, so a
     * concurrent second request resolves to the vote that won the race.
     */
    private function record(User $user, RankingEntry $entry, string $date): SupportEvent
    {
        try {
            return SupportEvent::query()->create([
                'user_id' => $user->id,
                'ranking_entry_id' => $entry->id,
                'object_id' => $entry->object_id,
                'ledger_id' => null,
                'amount' => 1,
                'source' => 'quick_vote',
                'vote_date' => $date,
                'status' => 'valid',
                'occurred_at' => now('UTC'),
            ]);
        } catch (UniqueConstraintViolationException) {
            return $this->voteOn($user, $date) ?? throw ValidationException::withMessages(['ranking_entry_id' => __('votmee.quick_vote_done')]);
        }
    }

    /**
     * Percentages stay hidden until the viewer has voted, and until enough people voted
     * for them to mean something.
     *
     * @return array<string, mixed>
     */
    private function present(QuickDuel $duel, ?SupportEvent $vote): array
    {
        $results = null;

        if ($vote !== null) {
            $counts = SupportEvent::query()
                ->where('source', 'quick_vote')
                ->where('status', 'valid')
                ->where('vote_date', $duel->duel_date->toDateString())
                ->whereIn('ranking_entry_id', [$duel->entry_a_id, $duel->entry_b_id])
                ->selectRaw('ranking_entry_id, COUNT(*) as total')
                ->groupBy('ranking_entry_id')
                ->pluck('total', 'ranking_entry_id');
            $total = (int) $counts->sum();
            $enough = $total >= self::MIN_VOTES_FOR_RESULT;
            $firstPercent = $enough ? (int) round(((int) ($counts[$duel->entry_a_id] ?? 0)) / $total * 100) : null;
            $results = [
                'total' => $total,
                'enough' => $enough,
                'min_votes' => self::MIN_VOTES_FOR_RESULT,
                'percents' => $enough ? [$duel->entry_a_id => $firstPercent, $duel->entry_b_id => 100 - $firstPercent] : null,
            ];
        }

        return [
            'id' => $duel->id,
            'date' => $duel->duel_date->toDateString(),
            'ranking' => ['title' => $duel->post->title, 'slug' => $duel->post->slug, 'language_code' => $duel->post->language_code],
            'entries' => array_map(fn (RankingEntry $entry): array => [
                'id' => $entry->id,
                'name' => $entry->object->canonical_name,
                'slug' => $entry->object->slug,
            ], [$duel->entryA, $duel->entryB]),
            'my_vote' => $vote?->ranking_entry_id,
            'results' => $results,
        ];
    }
}
