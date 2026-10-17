<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request, LeaderboardService $leaderboard): JsonResponse
    {
        $data = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,all_time'],
            'rank_type' => ['nullable', 'in:community,support'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'region_code' => ['nullable', 'string', 'max:16'],
        ]);

        return response()->json([
            'period' => $data['period'] ?? 'weekly',
            'rank_type' => $data['rank_type'] ?? 'community',
            'leaders' => $leaderboard->leaders($data['period'] ?? 'weekly', $data['rank_type'] ?? 'community', $data['category_id'] ?? null, $data['region_code'] ?? null),
        ]);
    }
}
