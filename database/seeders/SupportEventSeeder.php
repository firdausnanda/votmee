<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MasterObject;
use App\Models\User;
use App\Services\RankingService;
use App\Services\SupportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SupportEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $objects = MasterObject::factory()->count(2)->create(['category_id' => $category->id]);
        $post = app(RankingService::class)->publish($user, [
            'title' => 'Sample support ranking',
            'criteria' => 'Compare these sample candidates.',
            'category_id' => $category->id,
            'language_code' => 'en',
            'region_code' => 'GLOBAL',
            'object_ids' => $objects->modelKeys(),
        ]);
        $support = app(SupportService::class);
        $support->claimDaily($user);
        $support->support($user, $post->entries->first(), 1, 'seed:'.Str::ulid());
    }
}
