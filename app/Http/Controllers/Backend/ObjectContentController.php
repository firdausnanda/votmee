<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\ObjectContentRevision;
use App\Services\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ObjectContentController extends Controller
{
    public function storeSummary(Request $request, MasterObject $object): JsonResponse
    {
        abort_unless($object->status === 'verified' && $request->user()->canParticipate(), 403);
        $data = $request->validate([
            'body' => ['required', 'string', 'min:30', 'max:15000'],
            'body_format' => ['sometimes', 'in:markdown'],
            'language_code' => ['required', 'in:id,en'],
            'sources' => ['required', 'array', 'min:1', 'max:10'],
            'sources.*' => ['required', 'url', 'starts_with:https://', 'max:2048'],
        ]);

        if ($object->contentRevisions()->whereNull('article_id')->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['body' => __('votmee.review_invalid')]);
        }

        $revision = $object->contentRevisions()->create([
            ...$data,
            'body_format' => $data['body_format'] ?? 'plain',
            'author_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return response()->json($revision, 201);
    }

    public function storeArticle(Request $request, MasterObject $object): JsonResponse
    {
        abort_unless($object->status === 'verified' && $request->user()->canParticipate(), 403);
        $data = $request->validate([
            'type' => ['required', 'in:informative,experience'],
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'body' => ['required', 'string', 'min:30', 'max:15000'],
            'body_format' => ['sometimes', 'in:markdown'],
            'language_code' => ['required', 'in:id,en'],
            'sources' => ['required_if:type,informative', 'array', 'max:10'],
            'sources.*' => ['required', 'url', 'starts_with:https://', 'max:2048'],
        ]);

        if ($data['type'] === 'informative' && count($data['sources'] ?? []) === 0) {
            throw ValidationException::withMessages(['sources' => __('validation.required', ['attribute' => 'sources'])]);
        }

        $article = DB::transaction(function () use ($object, $request, $data): ObjectArticle {
            $article = $object->articles()->create(['author_id' => $request->user()->id, 'type' => $data['type']]);
            $article->revisions()->create([
                'object_id' => $object->id,
                'author_id' => $request->user()->id,
                'title' => $data['title'],
                'body' => $data['body'],
                'body_format' => $data['body_format'] ?? 'plain',
                'sources' => $data['sources'] ?? [],
                'language_code' => $data['language_code'],
                'status' => 'pending',
            ]);

            return $article;
        });

        return response()->json($article->load('revisions'), 201);
    }

    public function reviseArticle(Request $request, ObjectArticle $article): JsonResponse
    {
        abort_unless($article->author_id === $request->user()->id && $article->object->status === 'verified' && $request->user()->canParticipate(), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'body' => ['required', 'string', 'min:30', 'max:15000'],
            'body_format' => ['sometimes', 'in:markdown'],
            'language_code' => ['required', 'in:id,en'],
            'sources' => [$article->type === 'informative' ? 'required' : 'nullable', 'array', 'max:10'],
            'sources.*' => ['required', 'url', 'starts_with:https://', 'max:2048'],
        ]);

        if ($article->type === 'informative' && count($data['sources'] ?? []) === 0) {
            throw ValidationException::withMessages(['sources' => __('validation.required', ['attribute' => 'sources'])]);
        }
        if ($article->revisions()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['body' => __('votmee.review_invalid')]);
        }

        $revision = $article->revisions()->create([
            ...$data,
            'body_format' => $data['body_format'] ?? 'plain',
            'object_id' => $article->object_id,
            'author_id' => $request->user()->id,
            'sources' => $data['sources'] ?? [],
            'status' => 'pending',
        ]);

        return response()->json($revision, 201);
    }

    public function review(Request $request, ObjectContentRevision $revision, ModerationService $moderation): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:published,needs_revision,rejected'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);
        $moderation->reviewContentRevision($request->user(), $revision, $data['status'], $data['reason']);

        return response()->json($revision->refresh());
    }

    public function hideArticle(Request $request, ObjectArticle $article, ModerationService $moderation): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $moderation->hideArticle($request->user(), $article, $data['reason']);

        return response()->json($article->refresh());
    }
}
