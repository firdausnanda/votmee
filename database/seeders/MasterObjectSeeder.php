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
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first() ?? User::first();
        $matching = app(ObjectMatchingService::class);
        $categories = Category::pluck('id', 'slug');

        foreach (CommunityContent::posts() as $post) {
            foreach ($post['candidates'] as $candidate) {
                $normalized = $matching->normalize($candidate['name']);

                MasterObject::firstOrCreate(['normalized_name' => $normalized], [
                    'id' => (string) Str::ulid(),
                    'category_id' => $categories[$post['category']] ?? null,
                    'canonical_name' => $candidate['name'],
                    'slug' => Str::slug($candidate['name']),
                    'status' => 'verified',
                    'submitted_by' => $admin?->id,
                    'verified_by' => $admin?->id,
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                ]);
            }
        }
    }
}
