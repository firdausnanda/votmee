<?php

namespace App\Models;

use App\Services\ContentImageService;
use Database\Factories\ContentImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['uploader_id', 'path', 'width', 'height'])]
class ContentImage extends Model
{
    /** @use HasFactory<ContentImageFactory> */
    use HasFactory;

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function revisions(): BelongsToMany
    {
        return $this->belongsToMany(ObjectContentRevision::class, 'content_image_revision', 'content_image_id', 'revision_id');
    }

    /**
     * Public once a moderator has published a revision that uses the image,
     * while that summary or article is still shown on a public object.
     */
    public function isPublic(): bool
    {
        return $this->revisions()
            ->where('object_content_revisions.status', 'published')
            ->whereHas('object', fn (Builder $object) => $object->whereIn('status', ['verified', 'merged']))
            ->where(fn (Builder $revision) => $revision->whereNull('object_content_revisions.article_id')
                ->orWhereHas('article', fn (Builder $article) => $article->whereNotNull('published_revision_id')))
            ->exists();
    }

    public function url(): string
    {
        return route('backend.content-images.show', ['image' => $this->id, 'v' => ContentImageService::version($this->path)], false);
    }
}
