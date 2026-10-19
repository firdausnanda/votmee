<?php

namespace App\Models;

use Database\Factories\ObjectContentRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['object_id', 'article_id', 'author_id', 'reviewed_by', 'title', 'body', 'body_format', 'sources', 'language_code', 'status', 'review_reason', 'published_at'])]
class ObjectContentRevision extends Model
{
    /** @use HasFactory<ObjectContentRevisionFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return ['sources' => 'array', 'published_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function object(): BelongsTo
    {
        return $this->belongsTo(MasterObject::class, 'object_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(ObjectArticle::class, 'article_id');
    }
}
