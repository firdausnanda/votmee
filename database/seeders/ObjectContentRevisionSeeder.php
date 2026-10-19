<?php

namespace Database\Seeders;

use App\Models\MasterObject;
use App\Models\ObjectContentRevision;
use App\Models\User;
use Illuminate\Database\Seeder;

class ObjectContentRevisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $object = MasterObject::factory()->create(['canonical_name' => 'Example Reference Object']);
        $author = User::factory()->create();

        ObjectContentRevision::factory()->create([
            'object_id' => $object->id,
            'author_id' => $author->id,
            'body' => 'This is a sample encyclopedia summary for a fictional reference object.',
            'sources' => ['https://example.com/reference'],
            'status' => 'published',
            'published_at' => now('UTC'),
        ]);
    }
}
