<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\RankingPost;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SocialController extends Controller
{
    public function comment(Request $request, RankingPost $post): JsonResponse
    {
        abort_unless($post->status === 'published', 404);
        abort_unless($request->user()->canParticipate(), 403);
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:3000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        if (isset($data['parent_id']) && Comment::findOrFail($data['parent_id'])->ranking_post_id !== $post->id) {
            throw ValidationException::withMessages(['parent_id' => __('votmee.parent_mismatch')]);
        }

        return response()->json(Comment::create([
            'ranking_post_id' => $post->id,
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
            'status' => 'visible',
        ]), 201);
    }

    public function report(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target_type' => ['required', 'in:ranking_post,comment,object'],
            'target_id' => ['required', 'string', 'max:40'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $model = match ($data['target_type']) {
            'ranking_post' => RankingPost::class,
            'comment' => Comment::class,
            'object' => MasterObject::class,
        };

        if (! $model::query()->whereKey($data['target_id'])->exists()) {
            throw ValidationException::withMessages(['target_id' => __('votmee.report_target_missing')]);
        }

        return response()->json(Report::create([
            'reporter_id' => $request->user()->id,
            'target_type' => $data['target_type'],
            'target_id' => $data['target_id'],
            'reason' => $data['reason'],
        ]), 201);
    }

    public function profile(User $user): JsonResponse
    {
        return response()->json([
            'user' => $user->only('id', 'name', 'username', 'locale', 'region_code'),
            'rankings' => RankingPost::query()->where('creator_id', $user->id)->where('status', 'published')->latest('published_at')->paginate(20),
        ]);
    }
}
