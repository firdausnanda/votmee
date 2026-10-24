<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class CommentLikeSeeder extends Seeder
{
    /**
     * Like the visible comments the way readers do: thoughtful top-level comments collect the
     * most, quick replies a few, and nobody likes their own comment or one that is hidden.
     */
    public function run(): void
    {
        $likers = User::whereNull('restricted_until')->whereNotNull('email_verified_at')->pluck('id');

        Comment::where('status', 'visible')->whereDoesntHave('likes')->orderBy('id')->each(function (Comment $comment) use ($likers): void {
            $count = mt_rand(0, 3)
                + ($comment->parent_id === null ? mt_rand(1, 7) : 0)
                + (mb_strlen($comment->body) > 120 ? mt_rand(0, 6) : 0);
            $created = CarbonImmutable::parse($comment->created_at, 'UTC');
            $minutes = max(5, (int) $created->diffInMinutes(now('UTC')));

            foreach ($likers->reject(fn (int $id): bool => $id === $comment->user_id)->shuffle()->take($count) as $userId) {
                // Most likes arrive while the comment is still near the top of the discussion.
                $likedAt = $created->addMinutes(max(1, (int) ($minutes * (mt_rand() / mt_getrandmax()) ** 2.5)));

                (new CommentLike)->forceFill([
                    'comment_id' => $comment->id,
                    'user_id' => $userId,
                    'created_at' => $likedAt,
                    'updated_at' => $likedAt,
                ])->save();
            }
        });
    }
}
