<?php

namespace App\Models;

use App\Services\CommentImageProcessor;
use Database\Factories\CommentImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

#[Fillable(['comment_id', 'path', 'width', 'height', 'position'])]
class CommentImage extends Model
{
    /** @use HasFactory<CommentImageFactory> */
    use HasFactory;

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    /**
     * Public representation used by every comment listing.
     *
     * @return array{id: int, url: string, thumb_url: string, width: int, height: int}
     */
    public function toListing(): array
    {
        $url = route('backend.comment-images.show', ['image' => $this->id, 'v' => CommentImageProcessor::version($this->path)], false);

        return [
            'id' => $this->id,
            'url' => $url,
            'thumb_url' => $url.'&size=thumb',
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    /**
     * Attach an ordered `images` list to each comment row with a single query.
     *
     * @template TComment of object
     *
     * @param  Collection<int, TComment>  $comments
     * @return Collection<int, TComment>
     */
    public static function attachToComments(Collection $comments): Collection
    {
        $imagesByComment = self::query()
            ->whereIn('comment_id', $comments->pluck('id'))
            ->orderBy('position')
            ->get()
            ->groupBy('comment_id');

        return $comments->each(function (object $comment) use ($imagesByComment): void {
            $comment->images = ($imagesByComment->get($comment->id) ?? collect())
                ->map(fn (CommentImage $image): array => $image->toListing())
                ->values()
                ->all();
        });
    }
}
