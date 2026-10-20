<?php

namespace App\Models;

use Database\Factories\ObjectArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['object_id', 'author_id', 'type', 'published_revision_id'])]
class ObjectArticle extends Model
{
    /** @use HasFactory<ObjectArticleFactory> */
    use HasFactory, HasUlids;

    public function object(): BelongsTo
    {
        return $this->belongsTo(MasterObject::class, 'object_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ObjectContentRevision::class, 'article_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'article_id');
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(ObjectContentRevision::class, 'published_revision_id');
    }
}
