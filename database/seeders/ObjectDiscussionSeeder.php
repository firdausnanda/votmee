<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Seeders\Content\ObjectContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ObjectDiscussionSeeder extends Seeder
{
    /**
     * Open the discussions on object pages and published articles. An object's discussion
     * starts once it was verified, an article's once its first revision was published.
     */
    public function run(): void
    {
        $users = User::pluck('id', 'username');
        $articles = collect(ObjectContent::articles())->keyBy('ref');

        foreach (ObjectContent::discussions() as $discussion) {
            [$target, $openedAt] = isset($discussion['article'])
                ? $this->article($articles[$discussion['article']] ?? null)
                : $this->object($discussion['on']);

            if ($target === null) {
                continue;
            }

            $column = $target instanceof ObjectArticle ? 'article_id' : 'object_id';

            if (Comment::where($column, $target->id)->exists()) {
                continue;
            }

            foreach ($discussion['comments'] as $comment) {
                $top = $this->write($column, $target->id, $users, $comment, $openedAt, null);

                foreach ($comment['replies'] ?? [] as $reply) {
                    $this->write($column, $target->id, $users, $reply, $openedAt, $top);
                }
            }
        }
    }

    /** @return array{0: MasterObject|null, 1: CarbonInterface|null} */
    private function object(string $name): array
    {
        $object = MasterObject::where('canonical_name', $name)->where('status', 'verified')->first();

        return [$object, $object?->verified_at ?? $object?->created_at];
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @return array{0: ObjectArticle|null, 1: CarbonInterface|null}
     */
    private function article(?array $data): array
    {
        $article = $data ? ObjectArticleSeeder::find($data) : null;
        $firstPublished = $article?->revisions()->where('status', 'published')->oldest('published_at')->first();

        return $firstPublished ? [$article, $firstPublished->published_at] : [null, null];
    }

    /**
     * @param  Collection<string, int>  $users
     * @param  array<string, mixed>  $data
     */
    private function write(string $column, string $targetId, Collection $users, array $data, CarbonInterface $openedAt, ?Comment $parent): ?Comment
    {
        $userId = $users[$data['by']] ?? null;

        if ($userId === null) {
            return null;
        }

        $createdAt = $openedAt->copy()->addMinutes($data['at']);
        $createdAt = $createdAt->isFuture() ? now('UTC')->subMinutes(mt_rand(5, 90)) : $createdAt;

        $comment = new Comment;
        $comment->forceFill([
            $column => $targetId,
            'user_id' => $userId,
            'created_by' => $userId,
            'updated_by' => $userId,
            'parent_id' => $parent?->id,
            'body' => $data['body'],
            'status' => 'visible',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $comment;
    }
}
