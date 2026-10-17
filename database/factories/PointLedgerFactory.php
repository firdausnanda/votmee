<?php

namespace Database\Factories;

use App\Models\PointLedger;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PointLedger>
 */
class PointLedgerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'user_id' => fn (array $attributes): int => Wallet::findOrFail($attributes['wallet_id'])->user_id,
            'amount' => 5,
            'type' => 'daily_bonus',
            'idempotency_key' => 'factory:'.Str::ulid(),
            'claimed_on' => now('Asia/Jakarta')->toDateString(),
        ];
    }
}
