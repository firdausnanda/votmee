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
            ->when($rankType !== 'support', fn (Builder $query) => $query->where('support_events.source', 'support'))
            ->when($categoryId, fn (Builder $query) => $query->where('destination.category_id', $categoryId))
            ->when($regionCode, function (Builder $query) use ($regionCode): void {
                $query->join('ranking_entries', 'ranking_entries.id', '=', 'support_events.ranking_entry_id')
                    ->join('ranking_posts', 'ranking_posts.id', '=', 'ranking_entries.ranking_post_id')
                    ->where('ranking_posts.region_code', $regionCode);
            })
            ->when($start, fn (Builder $query) => $query->where('support_events.occurred_at', '>=', $start))
            ->when($end, fn (Builder $query) => $query->where('support_events.occurred_at', '<', $end))
            ->selectRaw("destination.id as object_id, destination.canonical_name as name, destination.slug, destination.image_path, {$scoreSql} as score")
            ->groupBy('destination.id', 'destination.canonical_name', 'destination.slug', 'destination.image_path')
            ->orderByDesc('score')
            ->orderBy('destination.id')
            ->limit(50)
            ->get();

        return $query->map(fn ($row): array => [
            'object_id' => $row->object_id,
            'id' => $row->object_id,
            'name' => $row->name,
            'slug' => $row->slug,
            'image_path' => $row->image_path,
            'score' => (int) $row->score,
        ])->all();
    }

    /**
     * Objects whose support points grew the most over the last 7 days compared with the 7 days before.
     *
     * @return array<int, array{object_id: string, id: string, name: string, slug: string, image_path: ?string, score: int, delta: int}>
     */
    public function rising(int $limit = 8): array
    {
        $current = CarbonImmutable::now('UTC')->subDays(7)->toDateTimeString();
        $previous = CarbonImmutable::now('UTC')->subDays(14)->toDateTimeString();

        return $this->validEvents()
            ->join('objects as destination', function ($join): void {
                $join->on('destination.id', '=', DB::raw('COALESCE(source.merged_into_id, support_events.object_id)'));
            })
            ->where('destination.status', 'verified')
            ->where('support_events.occurred_at', '>=', $previous)
            ->selectRaw(
                'destination.id as object_id, destination.canonical_name as name, destination.slug, destination.image_path, '
                .'SUM(CASE WHEN support_events.occurred_at >= ? THEN support_events.amount ELSE 0 END) as current_points, '
                .'SUM(CASE WHEN support_events.occurred_at >= ? THEN support_events.amount ELSE -support_events.amount END) as delta',
                [$current, $current],
            )
            ->groupBy('destination.id', 'destination.canonical_name', 'destination.slug', 'destination.image_path')
            ->havingRaw('SUM(CASE WHEN support_events.occurred_at >= ? THEN support_events.amount ELSE -support_events.amount END) > 0', [$current])
            ->orderByDesc('delta')
            ->orderByDesc('current_points')
            ->orderBy('destination.id')
            ->limit($limit)
            ->get()
            ->map(fn ($row): array => [
                'object_id' => $row->object_id,
                'id' => $row->object_id,
                'name' => $row->name,
                'slug' => $row->slug,
                'image_path' => $row->image_path,
                'score' => (int) $row->current_points,
                'delta' => (int) $row->delta,
            ])
            ->all();
    }

    /** @return array<int, array{user_id: int, name: string, username: string, supporters: int, support_points: int, score: float, avatar_frame: string|null, name_flair: string|null}> */
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
            ->selectRaw('users.id as user_id, users.name, users.username, users.avatar_path, COUNT(DISTINCT CASE WHEN support_events.source = \'support\' THEN support_events.user_id END) as supporters, SUM(support_events.amount) as support_points')
            ->groupBy('users.id', 'users.name', 'users.username', 'users.avatar_path');

        $maximums = DB::query()->fromSub($totals, 'creator_totals')
            ->selectRaw('MAX(supporters) as supporters, MAX(support_points) as support_points')
            ->first();

        if ($maximums->supporters === null) {
            return [];
        }

        $scoreSql = '(40.0 * creator_totals.supporters / ? + 60.0 * creator_totals.support_points / ?)';

        $creators = DB::query()->fromSub($totals, 'creator_totals')
            ->select('creator_totals.*')
            ->selectRaw("{$scoreSql} as score", [max(1, (int) $maximums->supporters), max(1, (int) $maximums->support_points)])
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
                'avatar_url' => ProfileImageProcessor::url((int) $row->user_id, 'avatar', $row->avatar_path),
                'supporters' => (int) $row->supporters,
                'support_points' => (int) $row->support_points,
                'score' => round((float) $row->score, 1),
            ]);
        $flair = app(AvatarFrameService::class)->flairFor($creators->pluck('user_id'));

        return $creators->map(fn (array $creator): array => [...$creator, ...($flair[$creator['user_id']] ?? [])])->all();
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
