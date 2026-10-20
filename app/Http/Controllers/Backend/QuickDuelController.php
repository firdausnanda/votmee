<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\QuickDuel;
use App\Models\RankingEntry;
use App\Services\QuickVoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuickDuelController extends Controller
{
    public function show(Request $request, QuickVoteService $quickVotes): JsonResponse
    {
        return response()->json(['duel' => $quickVotes->duelFor($request->user())]);
    }

    public function vote(Request $request, QuickDuel $duel, QuickVoteService $quickVotes): JsonResponse
    {
        $data = $request->validate(['ranking_entry_id' => ['required', 'integer', 'exists:ranking_entries,id']]);

        return response()->json(['duel' => $quickVotes->vote($request->user(), $duel, RankingEntry::findOrFail($data['ranking_entry_id']))]);
    }
}
