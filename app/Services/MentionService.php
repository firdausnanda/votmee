<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Reads @username mentions from a comment body. Every matched member becomes a link;
 * only those who could receive a message from the author are notified.
 */
class MentionService
{
    /** Mentions past this many in one comment stay plain text. */
    public const LIMIT = 5;

    /** Same pattern as resources/js/Pages/Community/Mentions.jsx. */
    public const PATTERN = '/(?<![\w@])@([A-Za-z0-9_-]{2,80})/u';

    /**
     * @return Collection<int, string>
     */
    public function extract(string $body): Collection
    {
        preg_match_all(self::PATTERN, $body, $matches);

        return collect($matches[1])
            ->map(fn (string $handle): string => strtolower(rtrim($handle, '-_')))
            ->filter(fn (string $handle): bool => strlen($handle) >= 2)
            ->unique()
            ->take(self::LIMIT)
            ->values();
    }

    /**
     * Stores the comment's mentions and returns every mentioned member.
     *
     * @return Collection<int, User>
     */
    public function record(Comment $comment): Collection
    {
        $handles = $this->extract($comment->body);

        if ($handles->isEmpty()) {
            return collect();
        }

        $mentioned = User::query()
            ->whereIn('username', $handles)
            ->whereKeyNot($comment->user_id)
            ->get();

        $comment->mentions()->createMany($mentioned->map(fn (User $user): array => ['user_id' => $user->id])->all());

        return $mentioned;
    }

    /**
     * Mentioned members who may be notified: able to participate and without a block either way.
     *
     * @param  Collection<int, User>  $mentioned
     * @return Collection<int, User>
     */
    public function notifiable(Comment $comment, Collection $mentioned): Collection
    {
        $author = $comment->user;

        return $mentioned
            ->filter(fn (User $user): bool => $user->canParticipate() && ($author === null || ! $author->hasBlockedOrIsBlockedBy($user)))
            ->values();
    }
}
