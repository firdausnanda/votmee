<?php

namespace App\Models;

use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;

#[Fillable(['ranking_post_id', 'user_id', 'parent_id', 'body', 'status', 'created_by', 'updated_by'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory, Userstamps;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rankingPost(): BelongsTo
    {
        return $this->belongsTo(RankingPost::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->where('status', 'visible');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }
}
