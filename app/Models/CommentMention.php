<?php

namespace App\Models;

use Database\Factories\CommentMentionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/** A member named with @username in a comment; the client links only these names. */
#[Fillable(['comment_id', 'user_id'])]
class CommentMention extends Model
{
    /** @use HasFactory<CommentMentionFactory> */
    use HasFactory;

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Collection<int, object>  $comments
     * @return Collection<int, object>
     */
    public static function attachToComments(Collection $comments): Collection
    {
        $mentionsByComment = self::query()
            ->join('users', 'users.id', '=', 'comment_mentions.user_id')
            ->whereIn('comment_mentions.comment_id', $comments->pluck('id'))
            ->whereNull('users.deleted_at')
            ->get(['comment_mentions.comment_id', 'users.id', 'users.username', 'users.name'])
            ->groupBy('comment_id');

        return $comments->each(function (object $comment) use ($mentionsByComment): void {
            $comment->mentions = ($mentionsByComment->get($comment->id) ?? collect())
                ->map(fn (CommentMention $mention): array => ['id' => $mention->id, 'username' => $mention->username, 'name' => $mention->name])
                ->values()
                ->all();
        });
    }
}
