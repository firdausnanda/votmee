<?php

namespace App\Models;

use Database\Factories\QuickDuelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['duel_date', 'ranking_post_id', 'entry_a_id', 'entry_b_id'])]
class QuickDuel extends Model
{
    /** @use HasFactory<QuickDuelFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['duel_date' => 'date:Y-m-d'];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(RankingPost::class, 'ranking_post_id');
    }

    public function entryA(): BelongsTo
    {
        return $this->belongsTo(RankingEntry::class, 'entry_a_id');
    }

    public function entryB(): BelongsTo
    {
        return $this->belongsTo(RankingEntry::class, 'entry_b_id');
    }
}
