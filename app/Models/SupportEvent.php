<?php

namespace App\Models;

use Database\Factories\SupportEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'ranking_entry_id', 'object_id', 'ledger_id', 'amount', 'status', 'occurred_at'])]
class SupportEvent extends Model
{
    /** @use HasFactory<SupportEventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }
}
