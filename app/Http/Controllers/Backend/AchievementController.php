<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\UserAchievement;
use App\Services\AchievementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function feature(Request $request, AchievementService $achievements): JsonResponse
    {
        $data = $request->validate([
            'keys' => ['present', 'array', 'max:'.UserAchievement::MAX_FEATURED],
            'keys.*' => ['string', 'max:40'],
        ]);
        $achievements->setFeatured($request->user(), $data['keys']);

        return response()->json(['featured' => array_values(array_unique($data['keys']))]);
    }

    public function seen(Request $request, AchievementService $achievements): JsonResponse
    {
        $achievements->markSeen($request->user());

        return response()->json(['seen' => true]);
    }
}
