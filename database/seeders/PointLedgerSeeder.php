<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\SupportService;
use Illuminate\Database\Seeder;

class PointLedgerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(SupportService::class)->claimDaily(User::factory()->create());
    }
}
