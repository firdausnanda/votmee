<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileImageProcessor
{
    use ProcessesImages;

    /**
     * Fixed output sizes per profile image type; each image is center-cropped to its aspect ratio.
     *
     * @var array<string, array{width: int, height: int, variants: array<string, array{0: int, 1: int}>}>
     */
    public const TYPES = [
        'avatar' => ['width' => 400, 'height' => 400, 'variants' => ['small' => [96, 96]]],
        'cover' => ['width' => 1600, 'height' => 400, 'variants' => ['small' => [800, 200]]],
    ];

    private const WEBP_QUALITY = 82;

    private const JPEG_QUALITY = 85;

    /**
     * @return array<int, mixed>
     */
    public static function rules(): array
    {
        return self::imageRules(required: true);
    }

    /**
     * The user column that stores the given image type.
     */
    public static function column(string $type): string
    {
        return $type.'_path';
    }

    /**
     * Versioned, immutable-cacheable URL of a member's profile image, or null when none is set.
     */
    public static function url(int $userId, string $type, ?string $path): ?string
    {
        return $path ? route('backend.users.image', ['user' => $userId, 'type' => $type, 'v' => self::version($path)], false) : null;
    }

    /**
     * Re-encode and crop the upload for the given type, store it with its
     * variants and return the full-size path.
     */
    public function store(UploadedFile $file, string $type, string $directory): string
    {
        $size = self::TYPES[$type];
        $image = $this->crop($this->load($file), $size['width'], $size['height']);
        $path = $this->newImagePath($directory);

        $disk = Storage::disk('local');
        $disk->put($path, $this->encode($image, self::WEBP_QUALITY, self::JPEG_QUALITY));

        foreach ($size['variants'] as $variant => [$width, $height]) {
            $disk->put(self::variantPath($path, $variant), $this->encode($this->crop($image, $width, $height), self::WEBP_QUALITY, self::JPEG_QUALITY));
        }

        return $path;
    }

    /**
     * Delete a profile image together with its variants.
     */
    public function delete(string $type, ?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk('local')->delete([$path, ...array_map(fn (string $variant): string => self::variantPath($path, $variant), array_keys(self::TYPES[$type]['variants']))]);
    }
}
