<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\RankingPost;
use App\Models\User;
use Database\Seeders\Content\CommunityContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CommentSeeder extends Seeder
{
    /**
     * Create the written discussion for every demo ranking. Each comment is posted after the
     * ranking was published, by a member who actually has something to say about its topic.
     */
    public function run(): void
    {
        $users = User::pluck('id', 'username');

        foreach (CommunityContent::posts() as $data) {
            $post = RankingPost::where('title', $data['title'])->first();

            if ($post === null || Comment::where('ranking_post_id', $post->id)->exists()) {
                continue;
            }

            foreach ($data['comments'] as $comment) {
                $top = $this->write($post, $users, $comment, null);

                foreach ($comment['replies'] ?? [] as $reply) {
                    $this->write($post, $users, $reply, $top);
                }
            }
        }
    }

    /**
     * @param  Collection<string, int>  $users
     * @param  array<string, mixed>  $data
     */
    private function write(RankingPost $post, $users, array $data, ?Comment $parent): ?Comment
    {
        $userId = $users[$data['by']] ?? null;

        if ($userId === null) {
            return null;
        }

        $createdAt = $post->published_at->copy()->addMinutes($data['at']);
        $createdAt = $createdAt->isFuture() ? now('UTC')->subMinutes(mt_rand(5, 90)) : $createdAt;

        $comment = new Comment;
        $comment->forceFill([
            'ranking_post_id' => $post->id,
            'user_id' => $userId,
            'created_by' => $userId,
            'updated_by' => $userId,
            'parent_id' => $parent?->id,
            'body' => $data['body'],
            'status' => ($data['flag'] ?? null) === 'spam' ? 'hidden' : 'visible',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $comment;
    }
}
