<?php

use App\Models\CommentImage;
use App\Models\ContentImage;
use App\Models\MasterObject;
use App\Models\ObjectContentRevision;
use App\Models\User;
use App\Services\CommentImageProcessor;
use App\Services\ContentImageService;
use App\Services\ObjectImageProcessor;
use App\Services\ProfileImageProcessor;
use Illuminate\Support\Facades\Storage;

/**
 * Write a placeholder file and backdate it so it falls outside the grace period.
 */
function storedImage(string $path, int $ageInHours = 48): string
{
    $disk = Storage::disk('local');
    $disk->put($path, 'image');
    touch($disk->path($path), now()->subHours($ageInHours)->getTimestamp());

    return $path;
}

test('unused editor uploads and unreferenced files are removed while images in use are kept', function () {
    Storage::fake('local');
    $disk = Storage::disk('local');

    $object = MasterObject::factory()->create(['image_path' => storedImage('objects/a/photo.webp')]);
    storedImage(ObjectImageProcessor::variantPath($object->image_path, 'thumb'));
    storedImage(ObjectImageProcessor::variantPath($object->image_path, 'small'));
    $user = User::factory()->create(['avatar_path' => storedImage('users/1/avatar.webp')]);
    storedImage(ProfileImageProcessor::variantPath($user->avatar_path, 'small'));
    $commentImage = CommentImage::factory()->create(['path' => storedImage('comments/1/photo.webp')]);
    storedImage(CommentImageProcessor::variantPath($commentImage->path, 'thumb'));

    $usedContentImage = ContentImage::factory()->create(['path' => storedImage('content/1/used.webp'), 'created_at' => now()->subDays(3)]);
    $usedContentImage->revisions()->attach(ObjectContentRevision::factory()->create());
    $unusedContentImage = ContentImage::factory()->create(['path' => storedImage('content/1/unused.webp'), 'created_at' => now()->subDays(3)]);
    storedImage(ContentImageService::variantPath($unusedContentImage->path, 'medium'));
    $freshContentImage = ContentImage::factory()->create(['path' => storedImage('content/1/fresh.webp', 1)]);

    $leftovers = [storedImage('objects/b/failed.webp'), storedImage('comments/2/deleted-comment.webp')];
    $recentLeftover = storedImage('users/2/uploading.webp', 1);

    $this->artisan('votmee:prune-images')
        ->expectsOutputToContain('Removed 1 unused editor image(s) and 2 orphan file(s)')
        ->assertSuccessful();

    expect(ContentImage::query()->pluck('id')->all())->toEqualCanonicalizing([$usedContentImage->id, $freshContentImage->id]);
    $disk->assertMissing([$unusedContentImage->path, ContentImageService::variantPath($unusedContentImage->path, 'medium'), ...$leftovers]);
    $disk->assertExists([
        $object->image_path, ObjectImageProcessor::variantPath($object->image_path, 'small'),
        $user->avatar_path, ProfileImageProcessor::variantPath($user->avatar_path, 'small'),
        $commentImage->path, CommentImageProcessor::variantPath($commentImage->path, 'thumb'),
        $usedContentImage->path, $freshContentImage->path, $recentLeftover,
    ]);
    expect($disk->directories('objects'))->toBe(['objects/a'])
        ->and($disk->directories('comments'))->toBe(['comments/1']);
});

test('a dry run only reports what would be removed', function () {
    Storage::fake('local');
    $unused = ContentImage::factory()->create(['path' => storedImage('content/1/unused.webp'), 'created_at' => now()->subDays(3)]);
    $leftover = storedImage('objects/b/failed.webp');

    $this->artisan('votmee:prune-images', ['--dry-run' => true])
        ->expectsOutputToContain('Would remove 1 unused editor image(s) and 1 orphan file(s)')
        ->assertSuccessful();

    expect(ContentImage::query()->whereKey($unused->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists([$unused->path, $leftover]);
});
