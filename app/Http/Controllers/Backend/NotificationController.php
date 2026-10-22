<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $notifications): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'notifications' => $user->notifications()->limit(10)->get()->map(fn ($notification): array => $notifications->listing($notification))->values(),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return response()->json(['unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->delete();

        return response()->json(['unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    public function clearRead(Request $request): JsonResponse
    {
        $request->validate(['scope' => ['required', 'in:read']]);

        $deleted = $request->user()->readNotifications()->delete();

        return response()->json(['deleted' => $deleted, 'unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now('UTC')]);

        return response()->json(['unread_count' => 0]);
    }
}
