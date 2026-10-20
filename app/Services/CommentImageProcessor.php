<?php

namespace App\Services;

use App\Models\Comment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CommentImageProcessor
{
    use ProcessesImages;

    public const MAX_IMAGES = 4;

    public const MAX_EDGE = 1600;

    /**
     * Downscaled variants stored next to every image, keyed by the `size` query value.
     *
     * @var array<string, int>
     */
    public const VARIANTS = ['thumb' => 480];

    private const WEBP_QUALITY = 80;

    private const VARIANT_WEBP_QUALITY = 75;

    private const JPEG_QUALITY = 85;

    private const VARIANT_JPEG_QUALITY = 80;

    /**
     * Validation rules for the optional `images` field of a comment. Images are
     * only allowed on top-level comments, not on replies.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'images' => ['nullable', 'array', 'max:'.self::MAX_IMAGES, 'prohibits:parent_id'],
            'images.*' => self::imageRules(required: true),
        ];
    }

    /**
     * Store every uploaded image for the comment. Files already written are
     * removed again when a later image or database write fails.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function attach(Comment $comment, array $files): void
    {
        $storedPaths = [];

        try {
            foreach (array_values($files) as $position => $file) {
                [$path, $width, $height] = $this->store($file, 'comments/'.$comment->id);
                $storedPaths[] = $path;

                $comment->images()->create(['path' => $path, 'width' => $width, 'height' => $height, 'position' => $position]);
            }
        } catch (Throwable $exception) {
            array_walk($storedPaths, fn (string $path) => $this->delete($path));

            throw $exception;
        }
    }

    /**
     * Re-encode and resize the upload, then store it with its variants.
     *
     * @return array{0: string, 1: int, 2: int}
     */
    public function store(UploadedFile $file, string $directory): array
    {
        $image = $this->fit($this->load($file), self::MAX_EDGE);
        $width = imagesx($image);
        $height = imagesy($image);
        $path = $this->newImagePath($directory);

        $disk = Storage::disk('local');
        $disk->put($path, $this->encode($image, self::WEBP_QUALITY, self::JPEG_QUALITY));

        foreach (self::VARIANTS as $variant => $edge) {
            $image = $this->fit($image, $edge);
            $disk->put(self::variantPath($path, $variant), $this->encode($image, self::VARIANT_WEBP_QUALITY, self::VARIANT_JPEG_QUALITY));
        }

        return [$path, $width, $height];
    }

    /**
     * Delete an image together with its variants.
     */
    public function delete(string $path): void
    {
        Storage::disk('local')->delete([$path, ...array_map(fn (string $variant): string => self::variantPath($path, $variant), array_keys(self::VARIANTS))]);
    }
}
