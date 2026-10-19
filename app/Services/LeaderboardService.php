<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /** @return array<int, array{object_id: string, name: string, slug: string, score: int}> */
    public function leaders(string $period = 'weekly', string $rankType = 'community', ?int $categoryId = null, ?string $regionCode = null): array
    {
        [$start, $end] = $this->bounds($period);
        $scoreSql = $rankType === 'support' ? 'SUM(support_events.amount)' : 'COUNT(DISTINCT support_events.user_id)';

        $query = $this->validEvents()
            ->join('objects as destination', function ($join): void {
                $join->on('destination.id', '=', DB::raw('COALESCE(source.merged_into_id, support_events.object_id)'));
            })
            ->where('destination.status', 'verified')
            ->when($categoryId, fn (Builder $query) => $query->where('destination.category_id', $categoryId))
            ->when($regionCode, function (Builder $query) use ($regionCode): void {
                $query->join('ranking_entries', 'ranking_entries.id', '=', 'support_events.ranking_entry_id')
                    ->join('ranking_posts', 'ranking_posts.id', '=', 'ranking_entries.ranking_post_id')
                    ->where('ranking_posts.region_code', $regionCode);
            })
            ->when($start, fn (Builder $query) => $query->where('support_events.occurred_at', '>=', $start))
            ->when($end, fn (Builder $query) => $query->where('support_events.occurred_at', '<', $end))
            ->selectRaw("destination.id as object_id, destination.canonical_name as name, destination.slug, {$scoreSql} as score")
            ->groupBy('destination.id', 'destination.canonical_name', 'destination.slug')
            ->orderByDesc('score')
            ->orderBy('destination.id')
            ->limit(50)
            ->get();

        return $query->map(fn ($row): array => [
            'object_id' => $row->object_id,
            'name' => $row->name,
            'slug' => $row->slug,
            'score' => (int) $row->score,
        ])->all();
    }

    /** @return array<int, array{user_id: int, name: string, username: string, supporters: int, support_points: int, score: float}> */
    public function creators(string $period = 'weekly'): array
    {
        [$start, $end] = $this->bounds($period);

        $totals = DB::table('support_events')
            ->join('ranking_entries', 'ranking_entries.id', '=', 'support_events.ranking_entry_id')
            ->join('ranking_posts', 'ranking_posts.id', '=', 'ranking_entries.ranking_post_id')
            ->join('users', 'users.id', '=', 'ranking_posts.creator_id')
            ->where('support_events.status', 'valid')
            ->where('ranking_posts.status', 'published')
            ->whereNull('users.deleted_at')
            ->when($start, fn (Builder $query) => $query->where('support_events.occurred_at', '>=', $start))
            ->when($end, fn (Builder $query) => $query->where('support_events.occurred_at', '<', $end))
            ->selectRaw('users.id as user_id, users.name, users.username, COUNT(DISTINCT support_events.user_id) as supporters, SUM(support_events.amount) as support_points')
            ->groupBy('users.id', 'users.name', 'users.username');

        $maximums = DB::query()->fromSub($totals, 'creator_totals')
            ->selectRaw('MAX(supporters) as supporters, MAX(support_points) as support_points')
            ->first();

        if ($maximums->supporters === null) {
            return [];
        }

        $scoreSql = '(40.0 * creator_totals.supporters / ? + 60.0 * creator_totals.support_points / ?)';

        return DB::query()->fromSub($totals, 'creator_totals')
            ->select('creator_totals.*')
            ->selectRaw("{$scoreSql} as score", [(int) $maximums->supporters, (int) $maximums->support_points])
            ->orderByDesc('score')
            ->orderByDesc('support_points')
            ->orderByDesc('supporters')
            ->orderBy('user_id')
            ->limit(50)
            ->get()
            ->map(fn ($row): array => [
                'user_id' => (int) $row->user_id,
                'name' => $row->name,
                'username' => $row->username,
                'supporters' => (int) $row->supporters,
                'support_points' => (int) $row->support_points,
                'score' => round((float) $row->score, 1),
            ])->all();
    }

    /** @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable} */
    private function bounds(string $period): array
    {
        $now = CarbonImmutable::now('Asia/Jakarta');

        return match ($period) {
            'daily' => [$now->startOfDay()->utc(), $now->addDay()->startOfDay()->utc()],
            'weekly' => [$now->startOfWeek(CarbonImmutable::MONDAY)->utc(), $now->startOfWeek(CarbonImmutable::MONDAY)->addWeek()->utc()],
            default => [null, null],
        };
    }

    private function validEvents(): Builder
    {
        return DB::table('support_events')
            ->join('objects as source', 'source.id', '=', 'support_events.object_id')
            ->where('support_events.status', 'valid');
    }
}
