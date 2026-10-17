<?php

namespace App\Models;

use Database\Factories\RankingPostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['creator_id', 'category_id', 'slug', 'title', 'criteria', 'language_code', 'region_code', 'status', 'published_at'])]
class RankingPost extends Model
{
    /** @use HasFactory<RankingPostFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(RankingEntry::class)->orderBy('position');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
