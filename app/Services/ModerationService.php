<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\Message;
use App\Models\ObjectArticle;
use App\Models\ObjectContentRevision;
use App\Models\RankingPost;
use App\Models\Report;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModerationService
{
    public function __construct(private AchievementService $achievements, private MissionService $missions) {}

    public function reviewObject(User $actor, MasterObject $object, string $status, string $reason): void
    {
        if (! in_array($status, ['verified', 'needs_revision', 'rejected'], true) || $object->status === 'merged'
            || ($object->status === 'verified' && $status === 'verified')) {
            throw ValidationException::withMessages(['status' => __('votmee.review_invalid')]);
        }

        $previousImagePath = $object->image_path;
        $discardedImagePath = null;
        $publishedSummaryAuthorId = null;
        $publishedSummaryCreatedAt = null;
        DB::transaction(function () use ($actor, $object, $status, $reason, &$discardedImagePath, &$publishedSummaryAuthorId, &$publishedSummaryCreatedAt): void {
            $pendingSummary = $object->contentRevisions()->whereNull('article_id')->where('status', 'pending')->latest()->first();
            if ($pendingSummary && ($object->status !== 'verified' || $status !== 'verified')) {
                $pendingSummary->update([
                    'status' => $status === 'verified' ? 'published' : $status,
                    'reviewed_by' => $actor->id,
                    'review_reason' => $reason,
                    'published_at' => $status === 'verified' ? now('UTC') : null,
                ]);
                $publishedSummaryAuthorId = $status === 'verified' ? $pendingSummary->author_id : null;
                $publishedSummaryCreatedAt = $pendingSummary->created_at;
            }
            $updates = ['status' => $status, 'verified_by' => $status === 'verified' ? $actor->id : null, 'verified_at' => $status === 'verified' ? now('UTC') : null];
            if ($status === 'verified' && $object->pending_image_path) {
                $updates['image_path'] = $object->pending_image_path;
                $updates['pending_image_path'] = null;
                $updates['pending_image_by'] = null;
            }
            if ($status === 'rejected' && $object->pending_image_path) {
                $discardedImagePath = $object->pending_image_path;
                $updates['pending_image_path'] = null;
                $updates['pending_image_by'] = null;
            }
            $object->update($updates);
            $this->audit($actor, 'object.review', 'object', $object->id, ['status' => $status, 'reason' => $reason]);
        });

        $images = app(ObjectImageProcessor::class);
        if ($status === 'verified' && $previousImagePath && $previousImagePath !== $object->image_path) {
            $images->delete($previousImagePath);
        }
        $images->delete($discardedImagePath);

        app(NotificationService::class)->objectReviewed($actor, $object, $status, $reason);

        if ($status === 'verified') {
            $this->evaluateFor($object->submitted_by, 'curator');
            $this->evaluateFor($publishedSummaryAuthorId, 'writer');
            $this->recordMissionFor($object->submitted_by, 'submit_object', $object->created_at);
            $this->recordMissionFor($publishedSummaryAuthorId, 'improve_summary', $publishedSummaryCreatedAt);
        }
    }

    public function reviewImage(User $actor, MasterObject $object, string $status, string $reason): void
    {
        if (! in_array($status, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['status' => __('votmee.review_invalid')]);
        }

        [$previousImagePath, $pendingImagePath] = DB::transaction(function () use ($actor, $object, $status, $reason): array {
            $lockedObject = MasterObject::query()->lockForUpdate()->findOrFail($object->id);
            if ($lockedObject->status !== 'verified' || ! $lockedObject->pending_image_path) {
                throw ValidationException::withMessages(['status' => __('votmee.review_invalid')]);
            }
            $previousImagePath = $lockedObject->image_path;
            $pendingImagePath = $lockedObject->pending_image_path;
            $lockedObject->update([
                'image_path' => $status === 'approved' ? $pendingImagePath : $previousImagePath,
                'pending_image_path' => null,
                'pending_image_by' => null,
            ]);
            $this->audit($actor, 'object.image.review', 'object', $object->id, ['status' => $status, 'reason' => $reason]);

            return [$previousImagePath, $pendingImagePath];
        });

        $unusedPath = $status === 'approved' ? $previousImagePath : $pendingImagePath;
        app(ObjectImageProcessor::class)->delete($unusedPath);
    }

    public function reviewContentRevision(User $actor, ObjectContentRevision $revision, string $status, string $reason): void
    {
        if ($revision->status !== 'pending' || ! in_array($status, ['published', 'needs_revision', 'rejected'], true)
            || $revision->object_id !== ($revision->article?->object_id ?? $revision->object_id)
            || MasterObject::query()->whereKey($revision->object_id)->value('status') !== 'verified') {
            throw ValidationException::withMessages(['status' => __('votmee.review_invalid')]);
        }

        DB::transaction(function () use ($actor, $revision, $status, $reason): void {
            $revision->update([
                'status' => $status,
                'reviewed_by' => $actor->id,
                'review_reason' => $reason,
                'published_at' => $status === 'published' ? now('UTC') : null,
            ]);
            if ($status === 'published' && $revision->article_id !== null) {
                $revision->article->update(['published_revision_id' => $revision->id]);
            }
            $this->audit($actor, 'content.review', $revision->article_id ? 'object_article' : 'object_summary', $revision->id, [
                'status' => $status, 'reason' => $reason,
            ]);
        });

        app(NotificationService::class)->contentReviewed($actor, $revision, $status, $reason);

        if ($status === 'published') {
            $this->evaluateFor($revision->author_id, 'writer');

            if ($revision->article_id === null) {
                $this->recordMissionFor($revision->author_id, 'improve_summary', $revision->created_at);
            } elseif ($revision->article->author_id === $revision->author_id) {
                $this->recordMissionFor($revision->author_id, 'write_article', $revision->article->created_at);
            }
        }
    }

    /** Publish a trusted contributor's new revision at once, leaving others in the review queue. */
    public function publishTrustedRevision(User $author, ObjectContentRevision $revision): ObjectContentRevision
    {
        if ($author->isTrustedContributor() && $revision->status === 'pending') {
            $this->reviewContentRevision($author, $revision, 'published', 'Published automatically for a trusted contributor.');
        }

        return $revision->refresh();
    }

    /** Grant or revoke trusted contributor status. A revocation also stops it being granted automatically again. */
    public function setTrust(User $actor, User $user, bool $trusted, string $reason): void
    {
        DB::transaction(function () use ($actor, $user, $trusted, $reason): void {
            $user->forceFill($trusted
                ? ['trusted_at' => now('UTC'), 'trust_revoked_at' => null]
                : ['trusted_at' => null, 'trust_revoked_at' => now('UTC')])->save();
            $this->audit($actor, $trusted ? 'user.trust.grant' : 'user.trust.revoke', 'user', (string) $user->id, ['reason' => $reason]);
        });

        if ($trusted) {
            app(NotificationService::class)->trustGranted($actor, $user);
        }
    }

    public function hideArticle(User $actor, ObjectArticle $article, string $reason): void
    {
        DB::transaction(function () use ($actor, $article, $reason): void {
            $article->update(['published_revision_id' => null]);
            $this->audit($actor, 'article.hide', 'object_article', $article->id, ['reason' => $reason]);
        });
    }

    public function mergeObject(User $actor, MasterObject $source, MasterObject $target, string $reason): void
    {
        if ($source->id === $target->id || $target->status !== 'verified' || $source->status === 'merged') {
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

    /** Publish or reject a ranking generated by the official account. */
    public function reviewGeneratedPost(User $actor, RankingPost $post, string $status, string $reason): void
    {
        if ($post->status !== 'pending_review') {
            throw ValidationException::withMessages(['status' => __('votmee.ranking_not_pending')]);
        }

        DB::transaction(function () use ($actor, $post, $status, $reason): void {
            $post->update($status === 'published'
                ? ['status' => 'published', 'published_at' => now('UTC')]
                : ['status' => 'rejected']);
            $this->audit($actor, "post.generated.{$status}", 'ranking_post', $post->id, ['reason' => $reason]);
        });
    }

    public function hideComment(User $actor, Comment $comment, string $reason): void
    {
        DB::transaction(function () use ($actor, $comment, $reason): void {
            $comment->update(['status' => 'hidden']);
            $this->audit($actor, 'comment.hide', 'comment', (string) $comment->id, ['reason' => $reason]);
        });
    }

    public function hideMessage(User $actor, Message $message, string $reason): void
    {
        DB::transaction(function () use ($actor, $message, $reason): void {
            $message->update(['status' => 'hidden']);
            $this->audit($actor, 'message.hide', 'message', (string) $message->id, ['reason' => $reason]);
        });
    }

    /**
     * Moderators read a private message only once it is reported, so the report carries its text.
     *
     * @param  LengthAwarePaginator<int, Report>  $reports
     * @return LengthAwarePaginator<int, Report>
     */
    public function withReportedMessages(LengthAwarePaginator $reports): LengthAwarePaginator
    {
        $messages = Message::query()->with('user:id,name,username')
            ->whereIn('id', $reports->getCollection()->where('target_type', 'message')->pluck('target_id'))
            ->get()->keyBy('id');

        return $reports->through(fn (Report $report): Report => $report->target_type === 'message'
            ? $report->setAttribute('message', $messages->get((int) $report->target_id)?->only('id', 'body', 'status', 'user', 'created_at'))
            : $report);
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

        app(NotificationService::class)->reportResolved($actor, $report, $status);

        if ($status === 'resolved') {
            $this->evaluateFor($report->reporter_id, 'guardian');
        }
    }

    /** Unlock a contributor's badges once a moderation decision credits their work. */
    private function evaluateFor(?int $userId, string $category): void
    {
        $user = $userId ? User::query()->find($userId) : null;

        if ($user !== null) {
            $this->achievements->evaluate($user, $category);
        }
    }

    /** Re-check a contributor's content mission once a moderation decision publishes their work. */
    private function recordMissionFor(?int $userId, string $mission, ?CarbonInterface $contentCreatedAt): void
    {
        $user = $userId ? User::query()->find($userId) : null;

        if ($user !== null && $contentCreatedAt !== null) {
            $this->missions->record($user, $mission, $contentCreatedAt);
        }
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
