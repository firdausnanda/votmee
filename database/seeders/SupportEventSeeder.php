<?php

namespace Database\Seeders;

use App\Models\PointLedger;
use App\Models\RankingPost;
use App\Models\SupportEvent;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SupportEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNull('restricted_until')->whereNotNull('email_verified_at')->get();

        if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::whereNull('restricted_until')->whereNotNull('email_verified_at')->get();
        }

        $posts = RankingPost::with(['entries.object'])->get();

        if ($posts->isEmpty()) {
            $this->call([
                CategorySeeder::class,
                MasterObjectSeeder::class,
                RankingPostSeeder::class,
            ]);
            $posts = RankingPost::with(['entries.object'])->get();
        }

        foreach ($posts as $post) {
            $entries = $post->entries;

            if ($entries->isEmpty()) {
                continue;
            }

            // Generate votes for each candidate in this ranking post
            foreach ($entries as $entryIdx => $entry) {
                // Skew vote count so candidate #1 has more votes, creating realistic leaderboards
                $voteCount = max(3, 14 - ($entryIdx * 3) + rand(-2, 3));
                $voters = $users->random(min($voteCount, $users->count()));

                foreach ($voters as $voter) {
                    $wallet = Wallet::firstOrCreate(
                        ['user_id' => $voter->id],
                        ['balance' => 50]
                    );

                    $amount = fake()->randomElement([1, 1, 2, 3, 5, 10]);

                    // Distribute timestamp: 30% today, 40% this week, 30% older
                    $randTime = rand(1, 100);
                    if ($randTime <= 30) {
                        $occurredAt = now('UTC')->subHours(rand(1, 18));
                    } elseif ($randTime <= 70) {
                        $occurredAt = now('UTC')->subDays(rand(1, 6))->subHours(rand(1, 20));
                    } else {
                        $occurredAt = now('UTC')->subDays(rand(7, 25))->subHours(rand(1, 20));
                    }

                    $idempotencyKey = 'support:'.$voter->id.':'.Str::ulid();

                    $ledger = PointLedger::create([
                        'wallet_id' => $wallet->id,
                        'user_id' => $voter->id,
                        'amount' => -$amount,
                        'type' => 'support',
                        'idempotency_key' => $idempotencyKey,
                        'created_at' => $occurredAt,
                        'updated_at' => $occurredAt,
                    ]);

                    $isReversed = rand(1, 100) <= 4; // 4% chance of being a reversed/cancelled vote
                    $status = $isReversed ? 'reversed' : 'valid';

                    $event = SupportEvent::create([
                        'user_id' => $voter->id,
                        'ranking_entry_id' => $entry->id,
                        'object_id' => $entry->object_id,
                        'ledger_id' => $ledger->id,
                        'amount' => $amount,
                        'status' => $status,
                        'occurred_at' => $occurredAt,
                        'created_at' => $occurredAt,
                        'updated_at' => $occurredAt,
                    ]);

                    if ($isReversed) {
                        $reversalTime = (clone $occurredAt)->addHours(rand(1, 12));
                        PointLedger::create([
                            'wallet_id' => $wallet->id,
                            'user_id' => $voter->id,
                            'amount' => $amount,
                            'type' => 'reversal',
                            'idempotency_key' => 'reversal:'.$event->id,
                            'reverses_ledger_id' => $ledger->id,
                            'created_at' => $reversalTime,
                            'updated_at' => $reversalTime,
                        ]);
                    }
                }
            }
        }

        // Recalculate wallet balances to match point ledger totals perfectly
        foreach ($users as $user) {
            $totalPoints = PointLedger::where('user_id', $user->id)->sum('amount');
            Wallet::updateOrCreate(
                ['user_id' => $user->id],
                ['balance' => max(0, $totalPoints)]
            );
        }
    }
}
