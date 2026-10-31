<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\RankingPost;
use App\Services\AchievementService;
use App\Services\FeedService;
use App\Services\MissionService;
use App\Services\RankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RankingController extends Controller
{
    public function index(Request $request, FeedService $feed): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:for_you,latest,popular'],
            'period' => ['nullable', Rule::in(array_keys(FeedService::POPULAR_PERIODS))],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'language_code' => ['nullable', 'in:id,en'],
            'region_code' => ['nullable', 'string', 'max:16'],
        ]);

        return response()->json($feed->paginate($data, $request->user()));
    }

    public function show(RankingPost $post): JsonResponse
    {
        abort_unless($post->status === 'published', 404);
        $post->load(['creator:id,name,username,avatar_path,is_official', 'entries.object', 'comments' => fn ($query) => $query->where('status', 'visible')->with('user:id,name,username,avatar_path')->withCount('likes')->latest()->limit(50)]);
        $scores = DB::table('support_events')
            ->selectRaw('ranking_entry_id, SUM(amount) as points, COUNT(DISTINCT user_id) as supporters')
            ->where('status', 'valid')
            ->whereIn('ranking_entry_id', $post->entries->pluck('id'))
            ->groupBy('ranking_entry_id')
            ->get()
            ->keyBy('ranking_entry_id');

        return response()->json(['post' => $post, 'scores' => $scores]);
    }

    public function store(Request $request, RankingService $rankings, MissionService $missions, AchievementService $achievements): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'criteria' => ['required', 'string', 'min:10', 'max:3000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'language_code' => ['required', 'in:id,en'],
            'region_code' => ['required', 'string', 'max:16'],
            'object_ids' => ['required', 'array', 'min:2', 'max:20'],
            'object_ids.*' => ['required', 'ulid', 'distinct', 'exists:objects,id'],
        ]);

        $post = $rankings->publish($request->user(), $data);
        $missions->record($request->user(), 'create_ranking', $post->created_at);
        $achievements->evaluate($request->user(), 'ranker');

        return response()->json($post, 201);
    }
}
