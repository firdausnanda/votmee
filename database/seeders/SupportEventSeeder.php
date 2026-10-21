<?php

namespace Database\Seeders;

use App\Models\PointLedger;
use App\Models\RankingPost;
use App\Models\SupportEvent;
use App\Models\User;
use App\Models\Wallet;
use Carbon\CarbonImmutable;
use Database\Seeders\Content\CommunityContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SupportEventSeeder extends Seeder
{
    /**
     * Cast votes that follow each ranking's own profile: how many members took part and which
     * candidates attracted them, always after the ranking was published and never beyond
     * what a member's wallet could afford.
     */
    public function run(): void
    {
        $users = User::whereNull('restricted_until')->whereNotNull('email_verified_at')->get();

        if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::whereNull('restricted_until')->whereNotNull('email_verified_at')->get();
        }

        $profiles = collect(CommunityContent::posts())->keyBy('title');
        $posts = RankingPost::with('entries')->orderBy('published_at')->get();

        foreach ($posts as $post) {
            $profile = $profiles[$post->title] ?? null;

            if ($post->entries->isEmpty() || SupportEvent::whereIn('ranking_entry_id', $post->entries->pluck('id'))->exists()) {
                continue;
            }

            $weights = $post->entries->values()->map(fn ($entry, int $index): int => $profile['candidates'][$index]['weight'] ?? 5);
            $supporters = (int) round(($profile['popularity'] ?? 1.0) * 16);

            // Share the supporters out by weight so the intended favourites really lead,
            // while the voters themselves and their amounts stay random.
            $positions = collect($this->shares($weights, $supporters))->shuffle();

            foreach ($users->shuffle()->take($positions->count()) as $index => $voter) {
                $this->support($voter, $post, $post->entries->values()[$positions->values()[$index]]);
            }
        }

        $this->seedQuickVotes($users, $posts, $profiles);
        PointLedgerSeeder::syncBalances();
    }

    private function support(User $voter, RankingPost $post, $entry): void
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $voter->id], ['balance' => 0]);
        $amount = min((int) $wallet->fresh()->balance, $this->randomAmount());

        if ($amount < 1) {
            return;
        }

        $published = CarbonImmutable::parse($post->published_at, 'UTC');
        $minutes = max(10, (int) $published->diffInMinutes(now('UTC')));
        // Skew towards recent activity: a ranking collects most of its votes while it is still fresh.
        $occurredAt = $published->addMinutes((int) ($minutes * (mt_rand() / mt_getrandmax()) ** 0.6));

        $ledger = (new PointLedger)->forceFill([
            'wallet_id' => $wallet->id,
            'user_id' => $voter->id,
            'amount' => -$amount,
            'type' => 'support',
            'idempotency_key' => 'support:'.$voter->id.':'.Str::ulid(),
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
        $ledger->save();
        $wallet->decrement('balance', $amount);

        $reversed = mt_rand(1, 100) <= 3;
        $event = (new SupportEvent)->forceFill([
            'user_id' => $voter->id,
            'ranking_entry_id' => $entry->id,
            'object_id' => $entry->object_id,
            'ledger_id' => $ledger->id,
            'amount' => $amount,
            'source' => 'support',
            'status' => $reversed ? 'reversed' : 'valid',
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
        $event->save();

        if ($reversed) {
            $reversedAt = $occurredAt->addHours(mt_rand(1, 12));
            (new PointLedger)->forceFill([
                'wallet_id' => $wallet->id,
                'user_id' => $voter->id,
                'amount' => $amount,
                'type' => 'reversal',
                'idempotency_key' => 'reversal:'.$event->id,
                'reverses_ledger_id' => $ledger->id,
                'created_at' => $reversedAt,
                'updated_at' => $reversedAt,
            ])->save();
            $wallet->increment('balance', $amount);
        }
    }

    /**
     * Free daily quick votes from the last few days (never today, so members can still vote).
     *
     * @param  Collection<int, User>  $users
     * @param  Collection<int, RankingPost>  $posts
     * @param  Collection<string, array<string, mixed>>  $profiles
     */
    private function seedQuickVotes(Collection $users, Collection $posts, Collection $profiles): void
    {
        if (SupportEvent::where('source', 'quick_vote')->exists()) {
            return;
        }

        foreach ($users as $user) {
            for ($daysAgo = 1; $daysAgo <= 5; $daysAgo++) {
                if (mt_rand(1, 100) > 35) {
                    continue;
                }

                $day = now('Asia/Jakarta')->subDays($daysAgo);
                $at = $day->copy()->startOfDay()->addHours(mt_rand(7, 22))->addMinutes(mt_rand(0, 59))->utc();
                $open = $posts->filter(fn (RankingPost $post): bool => $post->published_at <= $at && $post->entries->count() >= 2);

                if ($open->isEmpty()) {
                    continue;
                }

                $post = $open->random();
                $entry = $post->entries->values()->get($this->weightedPick($post->entries->values()->map(fn ($e, int $i): int => $profiles[$post->title]['candidates'][$i]['weight'] ?? 5)));

                (new SupportEvent)->forceFill([
                    'user_id' => $user->id,
                    'ranking_entry_id' => $entry->id,
                    'object_id' => $entry->object_id,
                    'ledger_id' => null,
                    'amount' => 1,
                    'source' => 'quick_vote',
                    'vote_date' => $day->toDateString(),
                    'status' => 'valid',
                    'occurred_at' => $at,
                    'created_at' => $at,
                    'updated_at' => $at,
                ])->save();
            }
        }
    }

    /**
     * Split `$total` voters over the candidates in proportion to their weights (largest remainder).
     *
     * @param  Collection<int, int>  $weights
     * @return list<int> candidate positions, one per voter
     */
    private function shares(Collection $weights, int $total): array
    {
        $sum = max(1, (int) $weights->sum());
        $exact = $weights->map(fn (int $weight): float => $weight / $sum * $total);
        $counts = $exact->map(fn (float $value): int => (int) floor($value))->all();
        $leftover = $total - array_sum($counts);

        foreach ($exact->map(fn (float $value, int $position): float => $value - floor($value))->sortDesc()->keys()->take($leftover) as $position) {
            $counts[$position]++;
        }

        $positions = [];

        foreach ($counts as $position => $count) {
            array_push($positions, ...array_fill(0, $count, $position));
        }

        return $positions;
    }

    /** @param Collection<int, int> $weights */
    private function weightedPick(Collection $weights): int
    {
        $roll = mt_rand(1, max(1, (int) $weights->sum()));

        foreach ($weights as $position => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $position;
            }
        }

        return $weights->count() - 1;
    }

    private function randomAmount(): int
    {
        $roll = mt_rand(1, 100);

        return match (true) {
            $roll <= 25 => 1,
            $roll <= 75 => 2,
            $roll <= 95 => 3,
            default => 4,
        };
    }
}
