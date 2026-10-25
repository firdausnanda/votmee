<?php

namespace App\Services;

use App\Models\RankingPost;
use App\Models\SavedRanking;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class FeedService
{
    /** @var array<string, int|null> */
    public const POPULAR_PERIODS = ['today' => 1, 'week' => 7, 'all' => null];

    /**
     * @param  array{q?: string|null, saved_by?: int|null, sort?: string|null, period?: string|null, category_id?: int|null, language_code?: string|null, region_code?: string|null}  $filters
     */
    public function paginate(array $filters, ?User $viewer = null, int $perPage = 20): LengthAwarePaginator
    {
        $now = CarbonImmutable::now('UTC');
        $scoresForPost = DB::table('support_events')
            ->join('ranking_entries', 'ranking_entries.id', '=', 'support_events.ranking_entry_id')
            ->whereColumn('ranking_entries.ranking_post_id', 'ranking_posts.id')
            ->where('support_events.status', 'valid');

        $query = RankingPost::query()
            ->where('status', 'published')
            ->with(['creator:id,name,username,avatar_path', 'entries.object:id,canonical_name,slug,status,image_path'])
            ->withCount(['comments' => fn ($comments) => $comments->where('status', 'visible')])
            ->addSelect([
                'support_points' => (clone $scoresForPost)->selectRaw('COALESCE(SUM(support_events.amount), 0)'),
                'supporters_count' => (clone $scoresForPost)->where('support_events.source', 'support')->selectRaw('COUNT(DISTINCT support_events.user_id)'),
            ])
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('criteria', 'like', '%'.$search.'%');
            }))
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($viewer, fn ($query) => $query->withExists(['savedRankings as is_saved' => fn ($saved) => $saved->where('user_id', $viewer->id)]))
            ->when($filters['saved_by'] ?? null, fn ($query, $userId) => $query->whereHas('savedRankings', fn ($saved) => $saved->where('user_id', $userId)));

        match ($filters['sort'] ?? 'for_you') {
            'popular' => $this->orderByTrending($query, $scoresForPost, $filters['period'] ?? 'week', $now),
            'latest' => $query->orderByDesc('published_at')->orderByDesc('ranking_posts.id'),
            'saved' => $query->orderByDesc(SavedRanking::query()
                ->select('created_at')
                ->whereColumn('saved_rankings.ranking_post_id', 'ranking_posts.id')
                ->where('user_id', $filters['saved_by'] ?? $viewer?->id)
                ->limit(1))
                ->orderByDesc('ranking_posts.id'),
            default => $this->orderForViewer($query, $scoresForPost, $filters, $viewer, $now),
        };

        $feed = $query->paginate($perPage);
        $entryIds = $feed->getCollection()->flatMap(fn (RankingPost $post) => $post->entries->pluck('id'));
        $entryScores = DB::table('support_events')
            ->where('status', 'valid')
            ->whereIn('ranking_entry_id', $entryIds)
            ->selectRaw('ranking_entry_id, SUM(amount) as points')
            ->groupBy('ranking_entry_id')
            ->pluck('points', 'ranking_entry_id');

        $feed->getCollection()->each(function (RankingPost $post) use ($entryScores): void {
            $topEntries = $post->entries
                ->sortByDesc(fn ($entry) => $entryScores[$entry->id] ?? 0)
                ->take(2)
                ->values();
            $topEntries->each(fn ($entry) => $entry->setAttribute('support_points', (int) ($entryScores[$entry->id] ?? 0)));
            $post->setRelation('entries', $topEntries);
        });
        app(AvatarFrameService::class)->fill($feed->getCollection()->pluck('creator'));

        return $feed;
    }

    /**
     * Rank by momentum inside the period: unique recent supporters first so one large
     * supporter cannot carry a post alone, then recent points and all-time points.
     */
    private function orderByTrending(Builder $query, QueryBuilder $scoresForPost, string $period, CarbonImmutable $now): void
    {
        $days = array_key_exists($period, self::POPULAR_PERIODS) ? self::POPULAR_PERIODS[$period] : self::POPULAR_PERIODS['week'];
        $recentScores = (clone $scoresForPost)
            ->when($days, fn ($scores) => $scores->where('support_events.occurred_at', '>=', $now->subDays($days)));

        $query->addSelect([
            'recent_supporters' => (clone $recentScores)->where('support_events.source', 'support')->selectRaw('COUNT(DISTINCT support_events.user_id)'),
            'recent_points' => (clone $recentScores)->selectRaw('COALESCE(SUM(support_events.amount), 0)'),
        ])
            ->orderByDesc('recent_supporters')
            ->orderByDesc('recent_points')
            ->orderByDesc('support_points')
            ->orderByDesc('published_at')
            ->orderByDesc('ranking_posts.id');
    }

    /**
     * Blend relevance (language, region, category affinity), freshness and weekly momentum
     * into one score. Posts the viewer created or already supported drop to the end.
     *
     * @param  array{language_code?: string|null, region_code?: string|null}  $filters
     */
    private function orderForViewer(Builder $query, QueryBuilder $scoresForPost, array $filters, ?User $viewer, CarbonImmutable $now): void
    {
        $recentSupporters = (clone $scoresForPost)
            ->where('support_events.source', 'support')
            ->where('support_events.occurred_at', '>=', $now->subDays(7))
            ->selectRaw('COUNT(DISTINCT support_events.user_id)');
        $parts = [];
        $bindings = [];

        if ($filters['language_code'] ?? null) {
            $parts[] = 'CASE WHEN ranking_posts.language_code = ? THEN 3 ELSE 0 END';
            $bindings[] = $filters['language_code'];
        }

        if ($filters['region_code'] ?? null) {
            $parts[] = 'CASE WHEN ranking_posts.region_code = ? THEN 2 WHEN ranking_posts.region_code = ? THEN 1 ELSE 0 END';
            array_push($bindings, $filters['region_code'], 'GLOBAL');
        }

        $categoryIds = $viewer ? $this->affinityCategoryIds($viewer) : [];

        if ($categoryIds !== []) {
            $parts[] = 'CASE WHEN ranking_posts.category_id IN ('.implode(', ', array_fill(0, count($categoryIds), '?')).') THEN 3 ELSE 0 END';
            array_push($bindings, ...$categoryIds);
        }

        $parts[] = 'CASE WHEN ranking_posts.published_at >= ? THEN 4 WHEN ranking_posts.published_at >= ? THEN 2 WHEN ranking_posts.published_at >= ? THEN 1 ELSE 0 END';
        array_push($bindings, $now->subDay(), $now->subDays(7), $now->subDays(30));

        $momentum = '('.$recentSupporters->toSql().')';
        $parts[] = "CASE WHEN {$momentum} >= 10 THEN 3 WHEN {$momentum} >= 3 THEN 2 WHEN {$momentum} >= 1 THEN 1 ELSE 0 END";

        foreach (range(1, 3) as $occurrence) {
            array_push($bindings, ...$recentSupporters->getBindings());
        }

        $query->selectRaw('('.implode(' + ', $parts).') as feed_score', $bindings);

        if ($viewer) {
            $query->selectRaw(
                'CASE WHEN ranking_posts.creator_id = ? OR EXISTS ('
                    .'SELECT 1 FROM support_events JOIN ranking_entries ON ranking_entries.id = support_events.ranking_entry_id '
                    .'WHERE ranking_entries.ranking_post_id = ranking_posts.id AND support_events.user_id = ? AND support_events.status = ?'
                    .') THEN 1 ELSE 0 END as viewer_engaged',
                [$viewer->id, $viewer->id, 'valid'],
            )->orderBy('viewer_engaged');
        }

        $query->orderByDesc('feed_score')
            ->orderByDesc('published_at')
            ->orderByDesc('ranking_posts.id');
    }

    /**
     * Categories the viewer interacts with most through support and comments.
     *
     * @return list<int>
     */
    public function affinityCategoryIds(User $viewer): array
    {
        $supported = DB::table('support_events')
            ->join('ranking_entries', 'ranking_entries.id', '=', 'support_events.ranking_entry_id')
            ->join('ranking_posts', 'ranking_posts.id', '=', 'ranking_entries.ranking_post_id')
            ->where('support_events.user_id', $viewer->id)
            ->where('support_events.status', 'valid')
            ->select('ranking_posts.category_id');
        $commented = DB::table('comments')
            ->join('ranking_posts', 'ranking_posts.id', '=', 'comments.ranking_post_id')
            ->where('comments.user_id', $viewer->id)
            ->select('ranking_posts.category_id');

        return DB::query()
            ->fromSub($supported->unionAll($commented), 'interactions')
            ->select('category_id')
            ->groupBy('category_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(5)
            ->pluck('category_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
