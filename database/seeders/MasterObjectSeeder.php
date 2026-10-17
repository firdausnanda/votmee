<?php

namespace Database\Seeders;

use App\Models\MasterObject;
use Illuminate\Database\Seeder;

class MasterObjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MasterObject::factory()->count(8)->create();
    }
}
