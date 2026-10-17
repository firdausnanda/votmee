<?php

namespace App\Services;

use App\Models\RankingPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FeedService
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $scoresForPost = DB::table('support_events')
            ->join('ranking_entries', 'ranking_entries.id', '=', 'support_events.ranking_entry_id')
            ->whereColumn('ranking_entries.ranking_post_id', 'ranking_posts.id')
            ->where('support_events.status', 'valid');

        $query = RankingPost::query()
            ->where('status', 'published')
            ->with(['creator:id,name,username', 'entries.object:id,canonical_name,slug,status'])
            ->withCount(['comments' => fn ($comments) => $comments->where('status', 'visible')])
            ->addSelect([
                'support_points' => (clone $scoresForPost)->selectRaw('COALESCE(SUM(support_events.amount), 0)'),
                'supporters_count' => (clone $scoresForPost)->selectRaw('COUNT(DISTINCT support_events.user_id)'),
            ])
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id));

        if (($filters['sort'] ?? 'latest') === 'popular') {
            $query->orderByDesc('support_points')->orderByDesc('published_at');
        } else {
            if (isset($filters['language_code'])) {
                $query->orderByRaw('CASE WHEN language_code = ? THEN 0 ELSE 1 END', [$filters['language_code']]);
            }

            if (isset($filters['region_code'])) {
                $query->orderByRaw('CASE WHEN region_code = ? OR region_code = ? THEN 0 ELSE 1 END', [$filters['region_code'], 'GLOBAL']);
            }

            $query->orderByDesc('published_at');
        }

        $feed = $query->paginate(20);
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

        return $feed;
    }
}
