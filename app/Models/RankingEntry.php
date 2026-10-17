<?php

namespace App\Models;

use Database\Factories\RankingEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ranking_post_id', 'object_id', 'position'])]
class RankingEntry extends Model
{
    /** @use HasFactory<RankingEntryFactory> */
    use HasFactory;

    public function post(): BelongsTo
    {
        return $this->belongsTo(RankingPost::class, 'ranking_post_id');
    }

    public function object(): BelongsTo
    {
        return $this->belongsTo(MasterObject::class, 'object_id');
    }
}
