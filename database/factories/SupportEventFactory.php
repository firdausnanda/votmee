<?php

namespace Database\Factories;

use App\Models\PointLedger;
use App\Models\RankingEntry;
use App\Models\SupportEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportEvent>
 */
class SupportEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ranking_entry_id' => RankingEntry::factory(),
            'object_id' => fn (array $attributes): string => RankingEntry::findOrFail($attributes['ranking_entry_id'])->object_id,
            'ledger_id' => PointLedger::factory(),
            'user_id' => fn (array $attributes): int => PointLedger::findOrFail($attributes['ledger_id'])->user_id,
            'amount' => 5,
            'status' => 'valid',
            'occurred_at' => now('UTC'),
        ];
    }
}
