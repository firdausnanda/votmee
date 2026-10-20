<?php

namespace App\Services;

use Closure;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Safe GD pipeline shared by every user-uploaded image: uploads are decoded and
 * re-encoded (dropping EXIF and any appended data) before they are stored.
 */
trait ProcessesImages
{
    /**
     * Upload limits: phone photos (e.g. 4032×3024) fit, decompression bombs do not.
     */
    public const MAX_INPUT_EDGE = 8000;

    public const MAX_INPUT_PIXELS = 24_000_000;

    /**
     * @return array<int, string|Closure>
     */
    public static function imageRules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096', self::dimensionsRule()];
    }

    public static function variantPath(string $path, string $variant): string
    {
        return Str::beforeLast($path, '.').'_'.$variant.'.'.pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * The cache-busting version of an image URL; every stored image gets a unique ULID name.
     */
    public static function version(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
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

    private function newImagePath(string $directory): string
    {
        return trim($directory, '/').'/'.Str::lower((string) Str::ulid()).'.'.$this->extension();
    }

    /**
     * Decode the upload and apply its EXIF orientation.
     */
    private function load(UploadedFile $file): GdImage
    {
        return $this->orient($this->decode($file), $file);
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

    /**
     * Scale the image down so its longest edge is at most $maxEdge.
     */
    private function fit(GdImage $image, int $maxEdge): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxEdge / max($width, $height));

        return $this->resample($image, 0, 0, $width, $height, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
    }

    /**
     * Center-crop the image to the target aspect ratio and scale it to (at most) the target size.
     */
    private function crop(GdImage $image, int $targetWidth, int $targetHeight): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = $targetWidth / $targetHeight;

        $sourceWidth = min($width, (int) round($height * $ratio));
        $sourceHeight = min($height, (int) round($width / $ratio));
        $scale = min(1, $targetWidth / $sourceWidth);

        return $this->resample(
            $image,
            intdiv($width - $sourceWidth, 2),
            intdiv($height - $sourceHeight, 2),
            $sourceWidth,
            $sourceHeight,
            max(1, (int) round($sourceWidth * $scale)),
            max(1, (int) round($sourceHeight * $scale)),
        );
    }

    private function resample(GdImage $image, int $sourceX, int $sourceY, int $sourceWidth, int $sourceHeight, int $targetWidth, int $targetHeight): GdImage
    {
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($this->supportsWebp()) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, (int) imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        } else {
            imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        }
        imagecopyresampled($canvas, $image, 0, 0, $sourceX, $sourceY, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

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
