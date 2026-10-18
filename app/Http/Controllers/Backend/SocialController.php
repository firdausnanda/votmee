<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\MasterObject;
use App\Models\RankingPost;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $targetParent = isset($data['parent_id']) ? Comment::findOrFail($data['parent_id']) : null;
        if ($targetParent && $targetParent->ranking_post_id !== $post->id) {
            throw ValidationException::withMessages(['parent_id' => __('votmee.parent_mismatch')]);
        }

        // Normalize 1-level reply threading: if target is already a child reply, link to its root parent
        $effectiveParentId = $targetParent ? ($targetParent->parent_id ?: $targetParent->id) : null;

        return response()->json(Comment::create([
            'ranking_post_id' => $post->id,
            'user_id' => $request->user()->id,
            'parent_id' => $effectiveParentId,
            'body' => $data['body'],
            'status' => 'visible',
        ]), 201);
    }

    public function getComments(Request $request, RankingPost $post): JsonResponse
    {
        abort_unless($post->status === 'published', 404);

        $offset = max(0, (int) $request->input('offset', 0));
        $limit = min(max(1, (int) $request->input('limit', 15)), 50);
        $sort = $request->input('sort', 'latest');

        $query = DB::table('comments')
            ->join('users', 'users.id', '=', 'comments.user_id')
            ->leftJoin('comment_likes', 'comment_likes.comment_id', '=', 'comments.id')
            ->where('comments.ranking_post_id', $post->id)
            ->where('comments.status', 'visible')
            ->selectRaw('comments.id, comments.parent_id, comments.body, comments.created_at, users.name, users.username, COUNT(comment_likes.id) as likes_count')
            ->groupBy('comments.id', 'comments.parent_id', 'comments.body', 'comments.created_at', 'users.name', 'users.username');

        if ($sort === 'popular') {
            $query->orderByDesc('likes_count')->orderByDesc('comments.created_at');
        } else {
            $query->orderByDesc('comments.created_at')->orderByDesc('comments.id');
        }

        $comments = $query->skip($offset)->take($limit)->get();

        $userId = $request->user()?->id;
        $likedCommentIds = $userId
            ? DB::table('comment_likes')
                ->where('user_id', $userId)
                ->whereIn('comment_id', $comments->pluck('id'))
                ->pluck('comment_id')
                ->flip()
                ->toArray()
            : [];

        $comments->transform(function ($c) use ($likedCommentIds) {
            $c->liked = isset($likedCommentIds[$c->id]);
            $c->likes_count = (int) $c->likes_count;

            return $c;
        });

        $totalCount = DB::table('comments')
            ->where('ranking_post_id', $post->id)
            ->where('status', 'visible')
            ->count();

        return response()->json([
            'comments' => $comments,
            'total' => $totalCount,
            'has_more' => ($offset + $comments->count()) < $totalCount,
        ]);
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

    public function toggleCommentLike(Request $request, Comment $comment): JsonResponse
    {
        abort_unless($comment->status === 'visible', 404);
        abort_unless($request->user()->canParticipate(), 403);

        $existing = CommentLike::query()
            ->where('comment_id', $comment->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            CommentLike::create([
                'comment_id' => $comment->id,
                'user_id' => $request->user()->id,
            ]);
            $liked = true;
        }

        $likesCount = CommentLike::query()->where('comment_id', $comment->id)->count();

        return response()->json([
            'liked' => $liked,
            'likes_count' => $likesCount,
        ]);
    }

    public function profile(User $user): JsonResponse
    {
        return response()->json([
            'user' => $user->only('id', 'name', 'username', 'locale', 'region_code'),
            'rankings' => RankingPost::query()->where('creator_id', $user->id)->where('status', 'published')->latest('published_at')->paginate(20),
        ]);
    }
}
