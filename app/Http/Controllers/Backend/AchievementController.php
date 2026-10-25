<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\UserAchievement;
use App\Services\AchievementService;
use App\Services\AvatarFrameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function frame(Request $request, AvatarFrameService $frames): JsonResponse
    {
        $data = $request->validate([
            'frame' => ['present', 'nullable', 'string', Rule::in([...AvatarFrameService::FRAMES, AvatarFrameService::NONE])],
        ]);
        $frames->choose($request->user(), $data['frame']);

        return response()->json(['preferred' => $data['frame'], 'frame' => $frames->frameFor($request->user())]);
    }

    public function seen(Request $request, AchievementService $achievements): JsonResponse
    {
        $achievements->markSeen($request->user());

        return response()->json(['seen' => true]);
    }
}
