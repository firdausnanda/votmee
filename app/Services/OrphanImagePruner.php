<?php

namespace App\Services;

use App\Models\CommentImage;
use App\Models\ContentImage;
use App\Models\MasterObject;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Removes uploaded images nothing points to any more: editor uploads that were
 * never used in a submitted text, and files on disk without a database record
 * (left behind by failed requests or deleted comments).
 */
class OrphanImagePruner
{
    /**
     * Storage directories that only hold user-uploaded images.
     */
    private const DIRECTORIES = ['objects', 'users', 'comments', 'content'];

    public function __construct(private ContentImageService $contentImages) {}

    /**
     * Only uploads older than $olderThan are touched, so in-flight uploads are kept.
     *
     * @return array{unused_content_images: int, orphan_files: int}
     */
    public function prune(CarbonInterface $olderThan, bool $dryRun = false): array
    {
        $unusedContentImages = ContentImage::query()
            ->whereDoesntHave('revisions')
            ->where('created_at', '<', $olderThan)
            ->get();

        if (! $dryRun) {
            $unusedContentImages->each(function (ContentImage $image): void {
                $this->contentImages->delete($image->path);
                $image->delete();
            });
        }

        $disk = Storage::disk('local');
        $referenced = $this->referencedPaths();
        $orphanFiles = collect(self::DIRECTORIES)
            ->flatMap(fn (string $directory): array => $disk->allFiles($directory))
            ->reject(fn (string $path): bool => isset($referenced[$path]))
            ->filter(fn (string $path): bool => $disk->lastModified($path) < $olderThan->getTimestamp())
            ->values();

        if (! $dryRun) {
            $disk->delete($orphanFiles->all());
            $this->deleteEmptyDirectories();
        }

        return ['unused_content_images' => $unusedContentImages->count(), 'orphan_files' => $orphanFiles->count()];
    }

    /**
     * Every stored image path still in use, including its size variants.
     *
     * @return array<string, true>
     */
    private function referencedPaths(): array
    {
        $paths = [];
        $add = function (?string $path, array $variants, callable $variantPath) use (&$paths): void {
            if ($path === null || $path === '') {
                return;
            }
            $paths[$path] = true;
            foreach ($variants as $variant) {
                $paths[$variantPath($path, $variant)] = true;
            }
        };

        $objectVariants = array_keys(ObjectImageProcessor::VARIANTS);
        foreach (MasterObject::query()->toBase()->get(['image_path', 'pending_image_path']) as $object) {
            $add($object->image_path, $objectVariants, ObjectImageProcessor::variantPath(...));
            $add($object->pending_image_path, $objectVariants, ObjectImageProcessor::variantPath(...));
        }

        foreach (User::withTrashed()->toBase()->get(['avatar_path', 'cover_path']) as $user) {
            $add($user->avatar_path, array_keys(ProfileImageProcessor::TYPES['avatar']['variants']), ProfileImageProcessor::variantPath(...));
            $add($user->cover_path, array_keys(ProfileImageProcessor::TYPES['cover']['variants']), ProfileImageProcessor::variantPath(...));
        }

        foreach (CommentImage::query()->toBase()->pluck('path') as $path) {
            $add($path, array_keys(CommentImageProcessor::VARIANTS), CommentImageProcessor::variantPath(...));
        }

        foreach (ContentImage::query()->toBase()->pluck('path') as $path) {
            $add($path, array_keys(ContentImageService::VARIANTS), ContentImageService::variantPath(...));
        }

        return $paths;
    }

    private function deleteEmptyDirectories(): void
    {
        $disk = Storage::disk('local');

        foreach (self::DIRECTORIES as $root) {
            $directories = collect($disk->allDirectories($root))->sortByDesc(fn (string $directory): int => substr_count($directory, '/'));

            foreach ($directories as $directory) {
                if ($disk->files($directory) === [] && $disk->directories($directory) === []) {
                    $disk->deleteDirectory($directory);
                }
            }
        }
    }
}
