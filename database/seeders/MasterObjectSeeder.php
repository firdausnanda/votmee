<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MasterObject;
use App\Models\User;
use App\Services\ObjectMatchingService;
use Database\Seeders\Content\CommunityContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterObjectSeeder extends Seeder
{
    /**
     * Seed the verified objects that the demo rankings compare, filed under the ranking's category.
     * The ranking's creator submitted them, and a moderator verified them shortly before it was published.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();
        $users = User::pluck('id', 'username');
        $matching = app(ObjectMatchingService::class);
        $categories = Category::pluck('id', 'slug');

        foreach (CommunityContent::posts() as $post) {
            $submitterId = $users[$post['creator']] ?? $admin?->id;
            $verifiedAt = now('UTC')->subDays($post['days_ago'])->subMinutes(crc32($post['title']) % 600)->subHours(2 + crc32($post['creator']) % 6);

            foreach ($post['candidates'] as $index => $candidate) {
                $normalized = $matching->normalize($candidate['name']);
                $object = MasterObject::firstOrCreate(['normalized_name' => $normalized], [
                    'id' => (string) Str::ulid(),
                    'category_id' => $categories[$post['category']] ?? null,
                    'canonical_name' => $candidate['name'],
                    'slug' => Str::slug($candidate['name']),
                    'status' => 'verified',
                    'submitted_by' => $submitterId,
                    'verified_by' => $admin?->id,
                    'verified_at' => $verifiedAt,
                    'created_by' => $submitterId,
                    'updated_by' => $admin?->id,
                ]);

                if ($object->wasRecentlyCreated) {
                    $submittedAt = $verifiedAt->copy()->subHours(3)->subMinutes($index * 7);
                    $object->forceFill(['created_at' => $submittedAt, 'updated_at' => $verifiedAt])->saveQuietly();
                }
            }
        }
    }
}
