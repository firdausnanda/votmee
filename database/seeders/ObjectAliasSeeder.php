<?php

namespace Database\Seeders;

use App\Models\MasterObject;
use App\Models\ObjectAlias;
use Illuminate\Database\Seeder;

class ObjectAliasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $object = MasterObject::factory()->create();
        ObjectAlias::factory()->create(['object_id' => $object->id, 'category_id' => $object->category_id]);
    }
}
