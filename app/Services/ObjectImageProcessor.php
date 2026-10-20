<?php

namespace App\Services;

use Closure;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ObjectImageProcessor
{
    public const MAX_EDGE = 1600;

    /**
     * Downscaled variants stored next to every image, keyed by the `size` query value.
     *
     * @var array<string, int>
     */
    public const VARIANTS = ['thumb' => 400, 'small' => 160];

    /**
     * Upload limits: phone photos (e.g. 4032×3024) fit, decompression bombs do not.
     */
    public const MAX_INPUT_EDGE = 8000;

    public const MAX_INPUT_PIXELS = 24_000_000;

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
        return [$required ? 'required' : 'nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096', self::dimensionsRule()];
    }

    public static function variantPath(string $path, string $variant): string
    {
        return Str::beforeLast($path, '.').'_'.$variant.'.'.pathinfo($path, PATHINFO_EXTENSION);
    }

    public static function thumbnailPath(string $path): string
    {
        return self::variantPath($path, 'thumb');
    }

    /**
     * The cache-busting version of an image URL; every stored image gets a unique ULID name.
     */
    public static function version(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Re-encode the upload (dropping EXIF and any appended data), resize it and
     * store the full-size version plus every variant. Returns the full-size path.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $image = $this->fit($this->orient($this->decode($file), $file), self::MAX_EDGE);
        $path = trim($directory, '/').'/'.Str::lower((string) Str::ulid()).'.'.$this->extension();

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

    private static function dimensionsRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $size = $value instanceof UploadedFile ? @getimagesize($value->getRealPath()) : false;

            if ($size !== false && (max($size[0], $size[1]) > self::MAX_INPUT_EDGE || $size[0] * $size[1] > self::MAX_INPUT_PIXELS)) {
                $fail(__('votmee.image_too_large'));
            }
        };
    }

    private function decode(UploadedFile $file): GdImage
    {
        $contents = file_get_contents($file->getRealPath());
        $image = $contents === false ? false : @imagecreatefromstring($contents);

        if (! $image instanceof GdImage) {
            throw ValidationException::withMessages(['image' => __('validation.image', ['attribute' => 'image'])]);
        }

        return $image;
    }

    /**
     * Apply every EXIF orientation (rotations and mirrored variants) before the metadata is dropped.
     */
    private function orient(GdImage $image, UploadedFile $file): GdImage
    {
        if ($file->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        [$angle, $mirror] = match ((int) ($exif['Orientation'] ?? 1)) {
            2 => [0, true],
            3 => [180, false],
            4 => [180, true],
            5 => [270, true],
            6 => [270, false],
            7 => [90, true],
            8 => [90, false],
            default => [0, false],
        };

        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);
            if ($rotated instanceof GdImage) {
                $image = $rotated;
            }
        }

        if ($mirror) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        return $image;
    }

    private function fit(GdImage $image, int $maxEdge): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxEdge / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($this->supportsWebp()) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, (int) imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        } else {
            imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        }
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $canvas;
    }

    private function encode(GdImage $image, int $webpQuality, int $jpegQuality): string
    {
        ob_start();
        $this->supportsWebp()
            ? imagewebp($image, null, $webpQuality)
            : imagejpeg($image, null, $jpegQuality);

        return (string) ob_get_clean();
    }

    private function extension(): string
    {
        return $this->supportsWebp() ? 'webp' : 'jpg';
    }

    private function supportsWebp(): bool
    {
        return function_exists('imagewebp');
    }
}
