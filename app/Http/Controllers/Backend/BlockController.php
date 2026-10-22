<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function store(Request $request, User $user): JsonResponse
    {
        abort_if($request->user()->is($user), 422);

        UserBlock::query()->firstOrCreate(['blocker_id' => $request->user()->id, 'blocked_id' => $user->id]);

        return response()->json(['blocked' => true]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $request->user()->blocks()->where('blocked_id', $user->id)->delete();

        return response()->json(['blocked' => false]);
    }
}
