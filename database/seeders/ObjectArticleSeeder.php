<?php

namespace Database\Seeders;

use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\ObjectContentRevision;
use App\Models\User;
use Database\Seeders\Content\ObjectContent;
use Illuminate\Database\Seeder;

class ObjectArticleSeeder extends Seeder
{
    /**
     * Publish the members' experience and informative articles, keeping each article's
     * latest published revision as the one readers see.
     */
    public function run(): void
    {
        $users = User::pluck('id', 'username');
        $reviewerId = ObjectContentRevisionSeeder::reviewerId();

        foreach (ObjectContent::articles() as $data) {
            $object = MasterObject::where('canonical_name', $data['object'])->first();
            $authorId = $users[$data['by']] ?? null;

            if ($object === null || $authorId === null || self::find($data) !== null) {
                continue;
            }

            $article = new ObjectArticle;
            $article->forceFill(['object_id' => $object->id, 'author_id' => $authorId, 'type' => $data['type']])->save();
            $published = null;

            foreach ($data['revisions'] as $revisionData) {
                $revision = ObjectContentRevisionSeeder::record($object, $article, $authorId, $reviewerId, $revisionData);
                $published = $revision->status === 'published' ? $revision : $published;
            }

            $firstRevision = $article->revisions()->oldest()->first();
            $article->forceFill([
                'published_revision_id' => $published?->id,
                'created_at' => $firstRevision->created_at,
                'updated_at' => $published?->published_at ?? $firstRevision->created_at,
            ])->save();
        }
    }

    /**
     * The seeded article for a content entry, found by its first title.
     *
     * @param  array<string, mixed>  $data
     */
    public static function find(array $data): ?ObjectArticle
    {
        return ObjectContentRevision::query()
            ->whereNotNull('article_id')
            ->where('title', $data['revisions'][0]['title'])
            ->whereHas('object', fn ($object) => $object->where('canonical_name', $data['object']))
            ->first()
            ?->article;
    }
}
