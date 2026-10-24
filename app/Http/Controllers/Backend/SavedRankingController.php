<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\RankingPost;
use App\Models\SavedRanking;
use App\Services\MissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedRankingController extends Controller
{
    /**
     * Maximum number of locally saved rankings accepted in one sync.
     */
    public const SYNC_LIMIT = 200;

    public function store(Request $request, RankingPost $post, MissionService $missions): JsonResponse
    {
        abort_unless($post->status === 'published', 404);

        SavedRanking::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'ranking_post_id' => $post->id,
        ]);
        $missions->record($request->user(), 'save_ranking');

        return response()->json(['saved' => true]);
    }

    public function destroy(Request $request, RankingPost $post): JsonResponse
    {
        SavedRanking::query()
            ->where('user_id', $request->user()->id)
            ->where('ranking_post_id', $post->id)
            ->delete();

        return response()->json(['saved' => false]);
    }

    /**
     * Import rankings saved in the browser before the user signed in.
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ranking_post_ids' => ['present', 'array', 'max:'.self::SYNC_LIMIT],
            'ranking_post_ids.*' => ['string', 'max:26'],
        ]);
        $userId = $request->user()->id;
        $now = now();

        $rows = RankingPost::query()
            ->where('status', 'published')
            ->whereIn('id', array_map('strtolower', $data['ranking_post_ids']))
            ->pluck('id')
            ->map(fn (string $postId): array => [
                'user_id' => $userId,
                'ranking_post_id' => $postId,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        $imported = SavedRanking::query()->insertOrIgnore($rows);

        return response()->json([
            'imported' => $imported,
            'saved_count' => SavedRanking::query()->where('user_id', $userId)->count(),
        ]);
    }
}
