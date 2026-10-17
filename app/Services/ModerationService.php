<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\RankingPost;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModerationService
{
    public function reviewObject(User $actor, MasterObject $object, string $status, string $reason): void
    {
        if (! in_array($status, ['verified', 'needs_revision', 'rejected'], true) || $object->status === 'merged') {
            throw ValidationException::withMessages(['status' => __('votmee.review_invalid')]);
        }

        DB::transaction(function () use ($actor, $object, $status, $reason): void {
            $object->update(['status' => $status, 'verified_by' => $status === 'verified' ? $actor->id : null]);
            $this->audit($actor, 'object.review', 'object', $object->id, ['status' => $status, 'reason' => $reason]);
        });
    }

    public function mergeObject(User $actor, MasterObject $source, MasterObject $target, string $reason): void
    {
        if ($source->id === $target->id || $target->status !== 'verified' || $source->status === 'merged' || $source->category_id !== $target->category_id) {
            throw ValidationException::withMessages(['target_id' => __('votmee.merge_target_invalid')]);
        }

        DB::transaction(function () use ($actor, $source, $target, $reason): void {
            $sourceStatus = $source->status;
            $source->update(['status' => 'merged', 'merged_into_id' => $target->id]);
            DB::table('object_merges')->insert([
                'source_id' => $source->id,
                'target_id' => $target->id,
                'actor_id' => $actor->id,
                'source_status' => $sourceStatus,
                'reason' => $reason,
                'created_at' => now('UTC'),
                'updated_at' => now('UTC'),
            ]);
            $this->audit($actor, 'object.merge', 'object', $source->id, ['target_id' => $target->id, 'reason' => $reason]);
        });
    }

    public function undoMerge(User $actor, MasterObject $source, string $reason): void
    {
        if ($source->status !== 'merged') {
            throw ValidationException::withMessages(['object' => __('votmee.not_merged')]);
        }

        DB::transaction(function () use ($actor, $source, $reason): void {
            $merge = DB::table('object_merges')->where('source_id', $source->id)->whereNull('reversed_at')->latest('id')->lockForUpdate()->first();

            if ($merge === null) {
                throw ValidationException::withMessages(['object' => __('votmee.merge_history_missing')]);
            }

            DB::table('support_events')
                ->where('object_id', $merge->target_id)
                ->whereIn('ranking_entry_id', DB::table('ranking_entries')->where('object_id', $source->id)->select('id'))
                ->update(['object_id' => $source->id]);
            $source->update(['status' => $merge->source_status, 'merged_into_id' => null]);
            DB::table('object_merges')->where('id', $merge->id)->update(['reversed_at' => now('UTC'), 'updated_at' => now('UTC')]);
            $this->audit($actor, 'object.merge.undo', 'object', $source->id, ['target_id' => $merge->target_id, 'reason' => $reason]);
        });
    }

    public function hidePost(User $actor, RankingPost $post, string $reason): void
    {
        DB::transaction(function () use ($actor, $post, $reason): void {
            $post->update(['status' => 'hidden']);
            $this->audit($actor, 'post.hide', 'ranking_post', $post->id, ['reason' => $reason]);
        });
    }

    public function hideComment(User $actor, Comment $comment, string $reason): void
    {
        DB::transaction(function () use ($actor, $comment, $reason): void {
            $comment->update(['status' => 'hidden']);
            $this->audit($actor, 'comment.hide', 'comment', (string) $comment->id, ['reason' => $reason]);
        });
    }

    public function resolveReport(User $actor, Report $report, string $status, string $reason): void
    {
        if (! in_array($status, ['resolved', 'dismissed'], true)) {
            throw ValidationException::withMessages(['status' => __('votmee.report_status_invalid')]);
        }

        DB::transaction(function () use ($actor, $report, $status, $reason): void {
            $report->update(['status' => $status]);
            $this->audit($actor, 'report.'.$status, 'report', (string) $report->id, ['reason' => $reason]);
        });
    }

    /** @param array<string, mixed> $details */
    public function audit(User $actor, string $action, string $targetType, string $targetId, array $details): void
    {
        DB::table('moderation_actions')->insert([
            'actor_id' => $actor->id,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'action' => $action,
            'reason' => (string) ($details['reason'] ?? ''),
            'created_at' => now('UTC'),
            'updated_at' => now('UTC'),
        ]);
        DB::table('audit_logs')->insert([
            'actor_id' => $actor->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => json_encode($details, JSON_THROW_ON_ERROR),
            'created_at' => now('UTC'),
        ]);
    }
}
