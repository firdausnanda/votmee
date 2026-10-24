<?php

namespace Database\Seeders;

use App\Models\MissionCompletion;
use App\Models\PointLedger;
use App\Models\User;
use Illuminate\Database\Seeder;

class MissionCompletionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->whereNotNull('email_verified_at')->each(function (User $user): void {
            $day = now('Asia/Jakarta')->toDateString();

            if (PointLedger::query()->where('idempotency_key', "mission:{$user->id}:quick_vote:{$day}")->exists()) {
                return;
            }

            MissionCompletion::query()->firstOrCreate(
                ['user_id' => $user->id, 'mission' => 'quick_vote', 'completed_on' => $day],
                ['period' => 'daily', 'amount' => (int) config('gamification.missions.daily.pool.quick_vote.points')],
            );
        });
    }
}
