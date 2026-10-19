<?php

namespace Database\Seeders;

use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\User;
use Illuminate\Database\Seeder;

class ObjectArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $object = MasterObject::factory()->create(['canonical_name' => 'Example Experience Object']);
        $author = User::factory()->create();
        $article = ObjectArticle::factory()->create([
            'object_id' => $object->id,
            'author_id' => $author->id,
        ]);
        $revision = $article->revisions()->create([
            'object_id' => $object->id,
            'author_id' => $author->id,
            'title' => 'A sample personal experience',
            'body' => 'I used this fictional example object for a month and found it easy to use.',
            'sources' => [],
            'language_code' => 'en',
            'status' => 'published',
            'published_at' => now('UTC'),
        ]);
        $article->update(['published_revision_id' => $revision->id]);
    }
}
