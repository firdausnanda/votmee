<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MasterObject;
use App\Models\RankingPost;
use App\Models\User;
use App\Services\ObjectMatchingService;
use App\Services\RankingService;
use Database\Seeders\Content\CommunityContent;
use Illuminate\Database\Seeder;

class RankingPostSeeder extends Seeder
{
    /**
     * Publish the demo rankings through the real publishing flow, then spread their
     * publication dates over the past weeks so the feed has a believable history.
     */
    public function run(): void
    {
        $rankings = app(RankingService::class);
        $matching = app(ObjectMatchingService::class);
        $users = User::pluck('id', 'username');
        $categories = Category::pluck('id', 'slug');

        foreach (CommunityContent::posts() as $data) {
            $creator = User::find($users[$data['creator']] ?? null);
            $categoryId = $categories[$data['category']] ?? null;

            if ($creator === null || $categoryId === null || RankingPost::where('title', $data['title'])->exists()) {
                continue;
            }

            // Keep the declared candidate order: it becomes the initial position of each entry.
            $objects = MasterObject::whereIn('normalized_name', array_map(fn (array $candidate): string => $matching->normalize($candidate['name']), $data['candidates']))
                ->pluck('id', 'normalized_name');
            $objectIds = collect($data['candidates'])
                ->map(fn (array $candidate): ?string => $objects[$matching->normalize($candidate['name'])] ?? null)
                ->filter()
                ->values()
                ->all();

            if (count($objectIds) < 2) {
                continue;
            }

            $post = $rankings->publish($creator, [
                'title' => $data['title'],
                'criteria' => $data['criteria'],
                'category_id' => $categoryId,
                'language_code' => $data['language'],
                'region_code' => $data['region'],
                'object_ids' => $objectIds,
            ]);

            $publishedAt = now('UTC')->subDays($data['days_ago'])->subMinutes(crc32($data['title']) % 600);
            $post->forceFill(['published_at' => $publishedAt, 'created_at' => $publishedAt, 'updated_at' => $publishedAt])->saveQuietly();
            $post->entries()->update(['created_at' => $publishedAt, 'updated_at' => $publishedAt]);
        }
    }
}
