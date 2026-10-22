<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentImage;
use App\Models\CommentLike;
use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\RankingPost;
use App\Models\Report;
use App\Models\User;
use App\Services\CommentImageProcessor;
use App\Services\GamificationService;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SocialController extends Controller
{
    public function comment(Request $request, RankingPost $post, CommentImageProcessor $images): JsonResponse
    {
        abort_unless($post->status === 'published', 404);
        abort_unless($request->user()->canParticipate(), 403);
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:3000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            ...CommentImageProcessor::rules(),
        ]);

        $targetParent = isset($data['parent_id']) ? Comment::findOrFail($data['parent_id']) : null;
        if ($targetParent && ($targetParent->ranking_post_id !== $post->id || $targetParent->status !== 'visible')) {
            throw ValidationException::withMessages(['parent_id' => __('votmee.parent_mismatch')]);
        }

        // Normalize 1-level reply threading: if target is already a child reply, link to its root parent
        $effectiveParentId = $targetParent ? ($targetParent->parent_id ?: $targetParent->id) : null;

        return $this->createComment($request, $images, [
            'ranking_post_id' => $post->id,
            'user_id' => $request->user()->id,
            'parent_id' => $effectiveParentId,
            'body' => $data['body'],
            'status' => 'visible',
        ]);
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
        CommentImage::attachToComments($comments);

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

    public function objectComment(Request $request, MasterObject $object, CommentImageProcessor $images): JsonResponse
    {
        abort_unless($object->status === 'verified' && $request->user()->canParticipate(), 403);
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:3000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            ...CommentImageProcessor::rules(),
        ]);
        $objectIds = $object->mergedFamilyIds();
        $parent = isset($data['parent_id']) ? Comment::findOrFail($data['parent_id']) : null;
        if ($parent && (! $objectIds->contains($parent->object_id) || $parent->status !== 'visible')) {
            throw ValidationException::withMessages(['parent_id' => __('votmee.parent_mismatch')]);
        }

        return $this->createComment($request, $images, [
            'object_id' => $parent?->object_id ?? $object->id,
            'user_id' => $request->user()->id,
            'parent_id' => $parent ? ($parent->parent_id ?: $parent->id) : null,
            'body' => $data['body'],
            'status' => 'visible',
        ]);
    }

    public function getObjectComments(Request $request, MasterObject $object): JsonResponse
    {
        abort_unless($object->status === 'verified', 404);
        $objectIds = $object->mergedFamilyIds();
        $offset = max(0, (int) $request->input('offset', 0));
        $limit = min(max(1, (int) $request->input('limit', 15)), 50);
        $sort = $request->input('sort', 'latest');
        $query = DB::table('comments')
            ->join('users', 'users.id', '=', 'comments.user_id')
            ->leftJoin('comment_likes', 'comment_likes.comment_id', '=', 'comments.id')
            ->whereIn('comments.object_id', $objectIds)
            ->where('comments.status', 'visible')
            ->selectRaw('comments.id, comments.parent_id, comments.body, comments.created_at, users.name, users.username, COUNT(comment_likes.id) as likes_count')
            ->groupBy('comments.id', 'comments.parent_id', 'comments.body', 'comments.created_at', 'users.name', 'users.username');

        if ($sort === 'popular') {
            $query->orderByDesc('likes_count')->orderByDesc('comments.created_at');
        } else {
            $query->orderByDesc('comments.created_at')->orderByDesc('comments.id');
        }
        $comments = $query->skip($offset)->take($limit)->get();
        $likedCommentIds = $request->user()
            ? DB::table('comment_likes')->where('user_id', $request->user()->id)->whereIn('comment_id', $comments->pluck('id'))->pluck('comment_id')->flip()->toArray()
            : [];
        $comments->transform(function ($comment) use ($likedCommentIds) {
            $comment->liked = isset($likedCommentIds[$comment->id]);
            $comment->likes_count = (int) $comment->likes_count;

            return $comment;
        });
        CommentImage::attachToComments($comments);
        $total = Comment::query()->whereIn('object_id', $objectIds)->where('status', 'visible')->count();

        return response()->json(['comments' => $comments, 'total' => $total, 'has_more' => $offset + $comments->count() < $total]);
    }

    public function articleComment(Request $request, ObjectArticle $article, CommentImageProcessor $images): JsonResponse
    {
        $this->ensurePublishedArticle($article);
        abort_unless($request->user()->canParticipate(), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:3000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            ...CommentImageProcessor::rules(),
        ]);
        $parent = isset($data['parent_id']) ? Comment::findOrFail($data['parent_id']) : null;

        if ($parent && ($parent->article_id !== $article->id || $parent->status !== 'visible')) {
            throw ValidationException::withMessages(['parent_id' => __('votmee.parent_mismatch')]);
        }

        return $this->createComment($request, $images, [
            'article_id' => $article->id,
            'user_id' => $request->user()->id,
            'parent_id' => $parent ? ($parent->parent_id ?: $parent->id) : null,
            'body' => $data['body'],
            'status' => 'visible',
        ]);
    }

    public function getArticleComments(Request $request, ObjectArticle $article): JsonResponse
    {
        $this->ensurePublishedArticle($article);

        $offset = max(0, (int) $request->input('offset', 0));
        $limit = min(max(1, (int) $request->input('limit', 15)), 50);
        $sort = $request->input('sort', 'latest');
        $query = DB::table('comments')
            ->join('users', 'users.id', '=', 'comments.user_id')
            ->leftJoin('comment_likes', 'comment_likes.comment_id', '=', 'comments.id')
            ->where('comments.article_id', $article->id)
            ->where('comments.status', 'visible')
            ->selectRaw('comments.id, comments.parent_id, comments.body, comments.created_at, users.name, users.username, COUNT(comment_likes.id) as likes_count')
            ->groupBy('comments.id', 'comments.parent_id', 'comments.body', 'comments.created_at', 'users.name', 'users.username');

        if ($sort === 'popular') {
            $query->orderByDesc('likes_count')->orderByDesc('comments.created_at');
        } else {
            $query->orderByDesc('comments.created_at')->orderByDesc('comments.id');
        }

        $comments = $query->skip($offset)->take($limit)->get();
        $likedCommentIds = $request->user()
            ? DB::table('comment_likes')->where('user_id', $request->user()->id)
                ->whereIn('comment_id', $comments->pluck('id'))->pluck('comment_id')->flip()->toArray()
            : [];
        $comments->transform(function ($comment) use ($likedCommentIds) {
            $comment->liked = isset($likedCommentIds[$comment->id]);
            $comment->likes_count = (int) $comment->likes_count;

            return $comment;
        });
        CommentImage::attachToComments($comments);
        $total = Comment::query()->where('article_id', $article->id)->where('status', 'visible')->count();

        return response()->json(['comments' => $comments, 'total' => $total, 'has_more' => $offset + $comments->count() < $total]);
    }

    public function report(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target_type' => ['required', 'in:ranking_post,comment,object,object_article'],
            'target_id' => ['required', 'string', 'max:40'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $model = match ($data['target_type']) {
            'ranking_post' => RankingPost::class,
            'comment' => Comment::class,
            'object' => MasterObject::class,
            'object_article' => ObjectArticle::class,
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

    public function toggleCommentLike(Request $request, Comment $comment, GamificationService $gamification): JsonResponse
    {
        abort_unless($comment->status === 'visible', 404);
        abort_unless($request->user()->canParticipate(), 403);

        if ($comment->article_id !== null) {
            $this->ensurePublishedArticle($comment->article);
        }

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

            if ($comment->user_id !== $request->user()->id && $comment->user !== null) {
                $gamification->completeMission($comment->user, 'comment_liked');
            }
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

    /**
     * Serve a comment image while its comment is visible in a public ranking, object or article.
     */
    public function commentImage(Request $request, CommentImage $image): StreamedResponse
    {
        $comment = $image->comment;
        abort_unless($comment?->status === 'visible', 404);

        if ($comment->ranking_post_id !== null) {
            abort_unless($comment->rankingPost?->status === 'published', 404);
        } elseif ($comment->object_id !== null) {
            abort_unless(in_array($comment->object?->status, ['verified', 'merged'], true), 404);
        } else {
            $this->ensurePublishedArticle($comment->article);
        }

        $disk = Storage::disk('local');
        $path = $image->path;
        $variant = $request->query('size');
        if (is_string($variant) && array_key_exists($variant, CommentImageProcessor::VARIANTS)
            && $disk->exists(CommentImageProcessor::variantPath($path, $variant))) {
            $path = CommentImageProcessor::variantPath($path, $variant);
        }

        abort_unless($disk->exists($path), 404);

        // Comments can be hidden by moderators: browsers may keep the image for a day (the hidden comment
        // disappears from every page anyway), but shared caches such as a CDN must drop it within minutes.
        $lastModified = $disk->lastModified($path);
        $response = $disk->response($path, null, ['X-Content-Type-Options' => 'nosniff']);
        $response->setPublic();
        $response->setMaxAge(86400);
        $response->setSharedMaxAge(600);
        $response->setLastModified(new DateTimeImmutable('@'.$lastModified));
        $response->setEtag(sha1($path.'|'.$lastModified));
        $response->isNotModified($request);

        return $response;
    }

    /**
     * Create a comment together with its uploaded images.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createComment(Request $request, CommentImageProcessor $images, array $attributes): JsonResponse
    {
        $comment = DB::transaction(function () use ($request, $images, $attributes): Comment {
            $comment = Comment::create($attributes);
            $images->attach($comment, $request->file('images', []));

            return $comment;
        });

        return response()->json([
            ...$comment->toArray(),
            'images' => $comment->images->map(fn (CommentImage $image): array => $image->toListing())->all(),
        ], 201);
    }

    private function ensurePublishedArticle(ObjectArticle $article): void
    {
        $article->loadMissing(['publishedRevision', 'object']);
        $revision = $article->publishedRevision;
        abort_unless($revision?->status === 'published' && $revision->article_id === $article->id, 404);

        $object = $article->object;

        while ($object->status === 'merged') {
            $object = MasterObject::findOrFail($object->merged_into_id);
        }

        abort_unless($object->status === 'verified', 404);
    }
}
