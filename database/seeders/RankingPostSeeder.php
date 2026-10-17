<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MasterObject;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Database\Seeder;

class RankingPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = Category::factory()->create();
        $objects = MasterObject::factory()->count(2)->create(['category_id' => $category->id]);
        app(RankingService::class)->publish(User::factory()->create(), [
            'title' => 'Sample comparison for local development',
            'criteria' => 'Compare these two sample candidates.',
            'category_id' => $category->id,
            'language_code' => 'en',
            'region_code' => 'GLOBAL',
            'object_ids' => $objects->modelKeys(),
        ]);
    }
}
