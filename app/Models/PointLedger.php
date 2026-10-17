<?php

namespace App\Models;

use Database\Factories\PointLedgerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['wallet_id', 'user_id', 'amount', 'type', 'idempotency_key', 'claimed_on', 'reverses_ledger_id'])]
class PointLedger extends Model
{
    /** @use HasFactory<PointLedgerFactory> */
    use HasFactory;

    protected $table = 'point_ledger';
}
