<?php

namespace App\Models;

use Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'balance', 'streak_days', 'longest_streak', 'last_claimed_on'])]
class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_claimed_on' => 'date',
        ];
    }

    /** Create the wallet when missing, then lock it for the surrounding transaction. */
    public static function lockedFor(User $user): self
    {
        static::query()->insertOrIgnore(['user_id' => $user->id, 'balance' => 0, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);

        return static::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
    }
}
