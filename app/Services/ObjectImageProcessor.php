<?php

namespace App\Services;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ObjectImageProcessor
{
    use ProcessesImages;

    public const MAX_EDGE = 1600;

    /**
     * Downscaled variants stored next to every image, keyed by the `size` query value.
     *
     * @var array<string, int>
     */
    public const VARIANTS = ['thumb' => 400, 'small' => 160];

    private const WEBP_QUALITY = 80;

    private const VARIANT_WEBP_QUALITY = 75;

    private const JPEG_QUALITY = 85;

    private const VARIANT_JPEG_QUALITY = 80;

    /**
     * Shared validation rules for every object image upload.
     *
     * @return array<int, string|Closure>
     */
    public static function rules(bool $required = false): array
    {
        return self::imageRules($required);
    }

    public static function thumbnailPath(string $path): string
    {
        return self::variantPath($path, 'thumb');
    }

    /**
     * Re-encode and resize the upload, then store the full-size version plus
     * every variant. Returns the full-size path.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $image = $this->fit($this->load($file), self::MAX_EDGE);
        $path = $this->newImagePath($directory);

        $disk = Storage::disk('local');
        $disk->put($path, $this->encode($image, self::WEBP_QUALITY, self::JPEG_QUALITY));

        foreach (self::VARIANTS as $variant => $edge) {
            $image = $this->fit($image, $edge);
            $disk->put(self::variantPath($path, $variant), $this->encode($image, self::VARIANT_WEBP_QUALITY, self::VARIANT_JPEG_QUALITY));
        }

        return $path;
    }

    /**
     * Delete an image together with its variants.
     */
    public function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk('local')->delete([$path, ...array_map(fn (string $variant): string => self::variantPath($path, $variant), array_keys(self::VARIANTS))]);
    }
}
