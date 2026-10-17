<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\ObjectAlias;
use App\Models\PointLedger;
use App\Models\RankingPost;
use App\Models\Report;
use App\Models\SupportEvent;
use App\Models\User;
use App\Services\ModerationService;
use App\Services\ObjectMatchingService;
use App\Services\SupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModerationController extends Controller
{
    public function queue(): JsonResponse
    {
        return response()->json([
            'objects' => MasterObject::query()->whereIn('status', ['pending', 'needs_revision'])->oldest()->paginate(20),
            'reports' => Report::query()->where('status', 'open')->oldest()->paginate(20),
        ]);
    }

    public function reviewObject(Request $request, MasterObject $object, ModerationService $moderation): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:verified,needs_revision,rejected'], 'reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $moderation->reviewObject($request->user(), $object, $data['status'], $data['reason']);

        return response()->json($object->refresh());
    }

    public function addAlias(Request $request, MasterObject $object, ModerationService $moderation, ObjectMatchingService $matching): JsonResponse
    {
        $data = $request->validate(['alias' => ['required', 'string', 'min:2', 'max:255'], 'reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $normalized = $matching->normalize($data['alias']);

        if (ObjectAlias::query()->where('object_id', $object->id)->where('normalized_alias', $normalized)->exists()) {
            throw ValidationException::withMessages(['alias' => __('votmee.alias_exists')]);
        }

        $alias = DB::transaction(function () use ($request, $object, $moderation, $data, $normalized): ObjectAlias {
            $alias = ObjectAlias::create(['object_id' => $object->id, 'category_id' => $object->category_id, 'alias' => $data['alias'], 'normalized_alias' => $normalized]);
            $moderation->audit($request->user(), 'object.alias.add', 'object', $object->id, ['alias' => $data['alias'], 'reason' => $data['reason']]);

            return $alias;
        });

        return response()->json($alias, 201);
    }

    public function setTranslation(Request $request, MasterObject $object, ModerationService $moderation): JsonResponse
    {
        $data = $request->validate(['locale' => ['required', 'in:id,en'], 'name' => ['required', 'string', 'min:2', 'max:255'], 'reason' => ['required', 'string', 'min:5', 'max:2000']]);
        DB::transaction(function () use ($request, $object, $moderation, $data): void {
            DB::table('object_translations')->upsert([
                'object_id' => $object->id,
                'locale' => $data['locale'],
                'name' => $data['name'],
                'created_at' => now('UTC'),
                'updated_at' => now('UTC'),
            ], ['object_id', 'locale'], ['name', 'updated_at']);
            $moderation->audit($request->user(), 'object.translation.set', 'object', $object->id, $data);
        });

        return response()->json(DB::table('object_translations')->where('object_id', $object->id)->where('locale', $data['locale'])->first());
    }

    public function mergeObject(Request $request, MasterObject $object, ModerationService $moderation): JsonResponse
    {
        $data = $request->validate(['target_id' => ['required', 'ulid', 'exists:objects,id'], 'reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $moderation->mergeObject($request->user(), $object, MasterObject::findOrFail($data['target_id']), $data['reason']);

        return response()->json($object->refresh());
    }

    public function undoMerge(Request $request, MasterObject $object, ModerationService $moderation): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $moderation->undoMerge($request->user(), $object, $data['reason']);

        return response()->json($object->refresh());
    }

    public function hidePost(Request $request, RankingPost $post, ModerationService $moderation): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $moderation->hidePost($request->user(), $post, $data['reason']);

        return response()->json($post->refresh());
    }

    public function hideComment(Request $request, Comment $comment, ModerationService $moderation): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $moderation->hideComment($request->user(), $comment, $data['reason']);

        return response()->json($comment->refresh());
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $data = $request->validate(['action' => ['nullable', 'string', 'max:80'], 'target_type' => ['nullable', 'string', 'max:40']]);

        return response()->json(DB::table('audit_logs')
            ->when($data['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($data['target_type'] ?? null, fn ($query, $type) => $query->where('target_type', $type))
            ->orderByDesc('id')
            ->paginate(50));
    }

    public function ledger(Request $request): JsonResponse
    {
        $data = $request->validate(['user_id' => ['nullable', 'integer', 'exists:users,id'], 'type' => ['nullable', 'in:daily_bonus,support,reversal']]);

        return response()->json(PointLedger::query()
            ->when($data['user_id'] ?? null, fn ($query, $id) => $query->where('user_id', $id))
            ->when($data['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->latest()
            ->paginate(50));
    }

    public function resolveReport(Request $request, Report $report, ModerationService $moderation): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:resolved,dismissed'], 'reason' => ['required', 'string', 'min:5', 'max:2000']]);
        $moderation->resolveReport($request->user(), $report, $data['status'], $data['reason']);

        return response()->json($report->refresh());
    }

    public function reverseSupport(Request $request, SupportEvent $event, SupportService $support, ModerationService $moderation): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:2000']]);
        DB::transaction(function () use ($request, $event, $support, $moderation, $data): void {
            $support->reverse($event);
            $moderation->audit($request->user(), 'support.reverse', 'support_event', (string) $event->id, ['reason' => $data['reason']]);
        });

        return response()->json($event->refresh());
    }

    public function restrictUser(Request $request, User $user, ModerationService $moderation): JsonResponse
    {
        $data = $request->validate(['until' => ['required', 'date', 'after:now'], 'reason' => ['required', 'string', 'min:5', 'max:2000']]);
        DB::transaction(function () use ($request, $user, $moderation, $data): void {
            $user->update(['restricted_until' => $data['until']]);
            $moderation->audit($request->user(), 'user.restrict', 'user', (string) $user->id, $data);
        });

        return response()->json(['user_id' => $user->id, 'restricted_until' => $user->restricted_until]);
    }
}
