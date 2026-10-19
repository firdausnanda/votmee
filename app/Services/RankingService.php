<?php

namespace App\Services;

use App\Models\MasterObject;
use App\Models\RankingPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RankingService
{
    /** @param array{title: string, criteria: string, category_id: int, language_code: string, region_code: string, object_ids: array<int, string>} $data */
    public function publish(User $creator, array $data): RankingPost
    {
        $objectIds = $data['object_ids'];
        $objects = MasterObject::query()
            ->whereIn('id', $objectIds)
            ->whereIn('status', ['pending', 'verified'])
            ->get();

        if (count($objectIds) < 2 || count($objectIds) !== count(array_unique($objectIds)) || $objects->count() !== count($objectIds)) {
            throw ValidationException::withMessages(['object_ids' => __('votmee.objects_distinct')]);
        }

        return DB::transaction(function () use ($creator, $data, $objectIds): RankingPost {
            $post = RankingPost::create([
                'creator_id' => $creator->id,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
                'category_id' => $data['category_id'],
                'slug' => Str::slug($data['title']).'-'.Str::lower((string) Str::ulid()),
                'title' => $data['title'],
                'criteria' => $data['criteria'],
                'language_code' => $data['language_code'],
                'region_code' => $data['region_code'],
                'status' => 'published',
                'published_at' => now('UTC'),
            ]);

            foreach ($objectIds as $position => $objectId) {
                $post->entries()->create(['object_id' => $objectId, 'position' => $position + 1]);
            }

            return $post->load('entries.object');
        });
    }
}
