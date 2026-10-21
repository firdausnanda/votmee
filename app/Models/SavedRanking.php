<?php

namespace App\Models;

use Database\Factories\SavedRankingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'ranking_post_id'])]
class SavedRanking extends Model
{
    /** @use HasFactory<SavedRankingFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rankingPost(): BelongsTo
    {
        return $this->belongsTo(RankingPost::class);
    }
}
