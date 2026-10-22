<?php

namespace App\Services;

use App\Events\NotificationRefreshed;
use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\ObjectContentRevision;
use App\Models\Report;
use App\Models\User;
use App\Notifications\AchievementUnlocked;
use App\Notifications\CommentLiked;
use App\Notifications\CommentReplied;
use App\Notifications\ContentRevisionReviewed;
use App\Notifications\InAppNotification;
use App\Notifications\ObjectReviewed;
use App\Notifications\RankingCommented;
use App\Notifications\ReportResolved;
use App\Notifications\StreakReminder;
use App\Notifications\TrustGranted;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * Single entry point for in-app notifications. URLs are stored without a locale
 * prefix; the client adds the viewer's locale when rendering.
 */
class NotificationService
{
    /** Tell the replied-to author, or the ranking creator for a top-level ranking comment. */
    public function commentCreated(Comment $comment, ?Comment $replyingTo = null): void
    {
        $author = $comment->user;

        if ($author === null) {
            return;
        }

        $payload = [
            'actor' => $this->actor($author),
            'comment_id' => $comment->id,
            'url' => $this->commentUrl($comment),
            'excerpt' => Str::limit($comment->body, 120),
        ];

        if ($replyingTo !== null) {
            $this->send($replyingTo->user, $author, new CommentReplied($payload));

            return;
        }

        if ($comment->ranking_post_id !== null) {
            $post = $comment->rankingPost;
            $this->send($post?->creator, $author, new RankingCommented([...$payload, 'title' => $post?->title]));
        }
    }

    /** One unread notification per comment; further likes update its counter instead of adding rows. */
    public function commentLiked(Comment $comment, User $liker): void
    {
        $recipient = $comment->user;

        if ($recipient === null || $recipient->id === $liker->id || ! $recipient->wantsNotification(CommentLiked::TYPE)) {
            return;
        }

        $count = $comment->likes()->where('user_id', '!=', $comment->user_id)->count();
        $payload = [
            'actor' => $this->actor($liker),
            'comment_id' => $comment->id,
            'count' => $count,
            'url' => $this->commentUrl($comment),
            'excerpt' => Str::limit($comment->body, 120),
        ];

        $existing = $recipient->unreadNotifications()
            ->where('type', CommentLiked::class)
            ->get()
            ->first(fn ($notification): bool => ($notification->data['comment_id'] ?? null) === $comment->id);

        if ($existing !== null) {
            $existing->forceFill(['data' => ['type' => CommentLiked::TYPE, ...$payload], 'created_at' => now()])->save();
            NotificationRefreshed::dispatch($recipient, $existing);

            return;
        }

        $this->send($recipient, $liker, new CommentLiked($payload));
    }

    public function achievementUnlocked(User $user, string $key): void
    {
        $this->send($user, null, new AchievementUnlocked([
            'badge_key' => $key,
            'url' => "/profile/{$user->username}",
        ]));
    }

    public function contentReviewed(User $actor, ObjectContentRevision $revision, string $status, string $reason): void
    {
        $revision->loadMissing(['object', 'article']);

        $this->send($revision->author, $actor, new ContentRevisionReviewed([
            'status' => $status,
            'reason' => $reason,
            'title' => $revision->title ?? $revision->object?->canonical_name,
            'url' => $revision->article_id !== null ? "/article/{$revision->article_id}" : "/object/{$revision->object?->slug}",
        ]));
    }

    public function objectReviewed(User $actor, MasterObject $object, string $status, string $reason): void
    {
        $this->send($object->submitter, $actor, new ObjectReviewed([
            'status' => $status,
            'reason' => $reason,
            'title' => $object->canonical_name,
            'url' => "/object/{$object->slug}",
        ]));
    }

    public function reportResolved(User $actor, Report $report, string $status): void
    {
        $this->send($report->reporter, $actor, new ReportResolved(['status' => $status]));
    }

    public function trustGranted(User $actor, User $user): void
    {
        $this->send($user, $actor, new TrustGranted(['url' => "/profile/{$user->username}"]));
    }

    public function streakReminder(User $user, int $days): void
    {
        $this->send($user, null, new StreakReminder(['days' => $days, 'url' => '/wallet']));
    }

    /**
     * @return array{id: string, type: string, data: array<string, mixed>, read: bool, created_at: string|null}
     */
    public function listing(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->data['type'] ?? '',
            'data' => $notification->data,
            'read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    /** Skips missing or deleted recipients and never notifies someone about their own action. */
    private function send(?User $recipient, ?User $actor, InAppNotification $notification): void
    {
        if ($recipient === null || $recipient->id === $actor?->id) {
            return;
        }

        $recipient->notify($notification);
    }

    /**
     * @return array{id: int, name: string, username: string|null, avatar_url: string|null}
     */
    private function actor(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'avatar_url' => $user->avatar_url,
        ];
    }

    private function commentUrl(Comment $comment): string
    {
        return match (true) {
            $comment->ranking_post_id !== null => "/ranking/{$comment->rankingPost?->slug}",
            $comment->article_id !== null => "/article/{$comment->article_id}",
            default => "/object/{$comment->object?->slug}",
        };
    }
}
