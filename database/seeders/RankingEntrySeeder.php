<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RankingEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(RankingPostSeeder::class);
    }
}
