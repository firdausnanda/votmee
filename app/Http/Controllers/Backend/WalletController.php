<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PointLedger;
use App\Models\RankingEntry;
use App\Models\Wallet;
use App\Services\GamificationService;
use App\Services\SupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'balance' => Wallet::query()->where('user_id', $request->user()->id)->value('balance') ?? 0,
            'ledger' => PointLedger::query()->where('user_id', $request->user()->id)->latest()->paginate(20),
        ]);
    }

    public function claim(Request $request, SupportService $support, GamificationService $gamification): JsonResponse
    {
        $ledger = $support->claimDaily($request->user());
        $streak = $gamification->streak($request->user());

        return response()->json([
            'ledger' => $ledger,
            'balance' => Wallet::query()->where('user_id', $request->user()->id)->value('balance'),
            'claimed' => $ledger->wasRecentlyCreated,
            'streak' => $streak,
            'streak_bonus' => $ledger->wasRecentlyCreated && $streak['cycle_progress'] === $streak['cycle_days'] ? $streak['bonus'] : 0,
        ]);
    }

    public function support(Request $request, SupportService $support): JsonResponse
    {
        $data = $request->validate([
            'ranking_entry_id' => ['required', 'integer', 'exists:ranking_entries,id'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ]);
        $event = $support->support($request->user(), RankingEntry::findOrFail($data['ranking_entry_id']), $data['amount'], $data['idempotency_key']);

        return response()->json($event, 201);
    }
}
