<?php

namespace App\Services;

use App\Models\ContentImage;
use App\Models\ObjectContentRevision;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Images embedded in object summaries and community articles: upload processing
 * and validation of the image references inside a markdown body.
 */
class ContentImageService
{
    use ProcessesImages;

    public const MAX_IMAGES_PER_BODY = 10;

    public const MAX_EDGE = 1600;

    /**
     * Downscaled variants stored next to every image, keyed by the `size` query value.
     *
     * @var array<string, int>
     */
    public const VARIANTS = ['medium' => 800];

    /**
     * Markdown image syntax: ![alt](url "optional title").
     */
    private const MARKDOWN_IMAGE = '/!\[[^\]]*\]\(\s*<?([^)\s>]+)>?(?:\s+[^)]*)?\)/';

    private const INTERNAL_URL = '#^/backend/content-images/(\d+)(?:\?[^\s]*)?$#';

    private const WEBP_QUALITY = 80;

    private const VARIANT_WEBP_QUALITY = 78;

    private const JPEG_QUALITY = 85;

    private const VARIANT_JPEG_QUALITY = 82;

    /**
     * @return array<int, mixed>
     */
    public static function rules(): array
    {
        return self::imageRules(required: true);
    }

    /**
     * Re-encode, resize and store an upload for the given member.
     */
    public function store(UploadedFile $file, User $uploader): ContentImage
    {
        $image = $this->fit($this->load($file), self::MAX_EDGE);
        $path = $this->newImagePath('content/'.$uploader->id);

        $disk = Storage::disk('local');
        $disk->put($path, $this->encode($image, self::WEBP_QUALITY, self::JPEG_QUALITY));
        $width = imagesx($image);
        $height = imagesy($image);

        foreach (self::VARIANTS as $variant => $edge) {
            $image = $this->fit($image, $edge);
            $disk->put(self::variantPath($path, $variant), $this->encode($image, self::VARIANT_WEBP_QUALITY, self::VARIANT_JPEG_QUALITY));
        }

        try {
            return ContentImage::create(['uploader_id' => $uploader->id, 'path' => $path, 'width' => $width, 'height' => $height]);
        } catch (Throwable $exception) {
            $this->delete($path);
            throw $exception;
        }
    }

    /**
     * Delete an image file together with its variants.
     */
    public function delete(string $path): void
    {
        Storage::disk('local')->delete([$path, ...array_map(fn (string $variant): string => self::variantPath($path, $variant), array_keys(self::VARIANTS))]);
    }

    /**
     * Validate every image in a markdown body and return the referenced image ids.
     * Images must be uploaded through Votmee, and a member may only use their own
     * uploads or images that are already public.
     *
     * @return array<int, int>
     *
     * @throws ValidationException
     */
    public function referencedIds(string $body, User $author, string $field = 'body'): array
    {
        preg_match_all(self::MARKDOWN_IMAGE, $body, $matches);
        $ids = [];

        foreach ($matches[1] as $url) {
            if (! preg_match(self::INTERNAL_URL, $url, $internal)) {
                throw ValidationException::withMessages([$field => __('votmee.content_image_external')]);
            }
            $ids[] = (int) $internal[1];
        }

        $ids = array_values(array_unique($ids));
        if (count($ids) > self::MAX_IMAGES_PER_BODY) {
            throw ValidationException::withMessages([$field => __('votmee.content_image_limit', ['max' => self::MAX_IMAGES_PER_BODY])]);
        }

        $images = ContentImage::query()->whereKey($ids)->get();
        $usable = $images->filter(fn (ContentImage $image): bool => $image->uploader_id === $author->id || $image->isPublic());
        if ($usable->count() !== count($ids)) {
            throw ValidationException::withMessages([$field => __('votmee.content_image_invalid')]);
        }

        return $ids;
    }

    /**
     * @param  array<int, int>  $imageIds
     */
    public function link(ObjectContentRevision $revision, array $imageIds): void
    {
        if ($imageIds !== []) {
            $revision->images()->sync($imageIds);
        }
    }
}
