<?php

use App\Models\MasterObject;
use App\Models\User;
use App\Services\ObjectImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('a new object image stays private until the object is verified', function () {
    Storage::fake('local');
    $author = User::factory()->create();
    $moderator = User::factory()->create(['role' => 'moderator']);
    $response = $this->actingAs($author)->post(route('backend.objects.store'), [
        'canonical_name' => 'Camera With Photograph',
        'summary_body' => 'This camera has a distinctive body and a detailed description for the community.',
        'language_code' => 'en',
        'sources' => ['https://example.com/camera'],
        'image' => UploadedFile::fake()->image('camera.jpg', 600, 400),
    ], ['Accept' => 'application/json'])->assertCreated();
    $object = MasterObject::findOrFail($response->json('id'));
    $pendingPath = $object->pending_image_path;
    Storage::disk('local')->assertExists($pendingPath);
    expect($object->image_path)->toBeNull();

    auth()->logout();
    $this->get(route('backend.objects.image', $object).'?pending=1')->assertNotFound();
    $this->get(route('backend.objects.image', $object))->assertNotFound();
    $this->actingAs($author)->get(route('backend.objects.image', $object).'?pending=1')->assertOk();

    $this->actingAs($moderator)->patchJson(route('admin.backend.objects.review', $object), [
        'status' => 'verified', 'reason' => 'The image and object are accurate.',
    ])->assertOk();
    $object->refresh();
    expect($object->image_path)->toBe($pendingPath)
        ->and($object->pending_image_path)->toBeNull();
    auth()->logout();
    $this->get(route('backend.objects.image', $object))->assertOk();
    $this->get(route('community.object', ['locale' => 'id', 'object' => $object->slug]))
        ->assertInertia(fn (Assert $page) => $page->where('object.image_path', $pendingPath)->etc());
});

test('a replacement image keeps the current image until moderation and can be rejected', function () {
    Storage::fake('local');
    $object = MasterObject::factory()->create();
    $oldPath = 'objects/'.$object->id.'/old.jpg';
    Storage::disk('local')->put($oldPath, UploadedFile::fake()->image('old.jpg')->getContent());
    $object->update(['image_path' => $oldPath]);
    $member = User::factory()->create();
    $moderator = User::factory()->create(['role' => 'moderator']);

    $this->actingAs($member)->post(route('backend.objects.image.propose', $object), [
        'image' => UploadedFile::fake()->create('not-an-image.txt', 2, 'text/plain'),
    ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('image');
    $this->post(route('backend.objects.image.propose', $object), [
        'image' => UploadedFile::fake()->image('replacement.png'),
    ], ['Accept' => 'application/json'])->assertCreated();
    $pendingPath = $object->fresh()->pending_image_path;
    expect($object->fresh()->image_path)->toBe($oldPath);
    Storage::disk('local')->assertExists($pendingPath);
    $this->post(route('backend.objects.image.propose', $object), [
        'image' => UploadedFile::fake()->image('another.jpg'),
    ], ['Accept' => 'application/json'])->assertConflict();
    auth()->logout();
    $this->get(route('backend.objects.image', $object))->assertOk();
    $this->get(route('backend.objects.image', $object).'?pending=1')->assertNotFound();

    $this->actingAs($moderator)->get(route('community.admin'))->assertInertia(fn (Assert $page) => $page
        ->where('objects.total', 1)->where('objects.data.0.id', $object->id)->etc());
    $this->patchJson(route('admin.backend.objects.image.review', $object), [
        'status' => 'approved', 'reason' => 'The new image shows the object clearly.',
    ])->assertOk();
    expect($object->fresh()->image_path)->toBe($pendingPath);
    Storage::disk('local')->assertMissing($oldPath);

    $this->actingAs($member)->post(route('backend.objects.image.propose', $object), [
        'image' => UploadedFile::fake()->image('bad-replacement.jpg'),
    ], ['Accept' => 'application/json'])->assertCreated();
    $rejectedPath = $object->fresh()->pending_image_path;
    $this->actingAs($moderator)->patchJson(route('admin.backend.objects.image.review', $object), [
        'status' => 'rejected', 'reason' => 'This image is not sufficiently clear.',
    ])->assertOk();
    expect($object->fresh()->image_path)->toBe($pendingPath)
        ->and($object->fresh()->pending_image_path)->toBeNull();
    Storage::disk('local')->assertMissing($rejectedPath);
});

test('uploaded images are resized, re-encoded without appended data and get a thumbnail', function () {
    Storage::fake('local');
    $author = User::factory()->create();
    $payload = [
        'canonical_name' => 'Large Photograph Object',
        'summary_body' => 'This object has a very large photograph and a detailed description for the community.',
        'language_code' => 'en',
        'sources' => ['https://example.com/large'],
    ];

    $response = $this->actingAs($author)->post(route('backend.objects.store'), $payload + [
        'image' => UploadedFile::fake()->image('large.png', 3200, 2400),
    ], ['Accept' => 'application/json'])->assertCreated();
    $path = MasterObject::findOrFail($response->json('id'))->pending_image_path;
    $thumbnailPath = ObjectImageProcessor::variantPath($path, 'thumb');
    $smallPath = ObjectImageProcessor::variantPath($path, 'small');

    Storage::disk('local')->assertExists([$path, $thumbnailPath, $smallPath]);
    expect(getimagesizefromstring(Storage::disk('local')->get($path))[0])->toBe(1600)
        ->and(getimagesizefromstring(Storage::disk('local')->get($thumbnailPath))[0])->toBe(400)
        ->and(getimagesizefromstring(Storage::disk('local')->get($smallPath))[0])->toBe(160);

    $polyglot = UploadedFile::fake()->createWithContent('payload.png', UploadedFile::fake()->image('a.png')->getContent().'<?php echo "pwned"; ?>');
    $second = $this->post(route('backend.objects.store'), array_merge($payload, [
        'canonical_name' => 'Polyglot Photograph Object',
        'image' => $polyglot,
    ]), ['Accept' => 'application/json'])->assertCreated();
    $storedPath = MasterObject::findOrFail($second->json('id'))->pending_image_path;
    expect(Storage::disk('local')->get($storedPath))->not->toContain('<?php');
});

test('verified object images are cacheable and can be served as thumbnails', function () {
    Storage::fake('local');
    $object = MasterObject::factory()->create();
    $path = app(ObjectImageProcessor::class)->store(UploadedFile::fake()->image('a.jpg', 800, 600), 'objects/'.$object->id);
    $object->update(['image_path' => $path]);

    $full = $this->get(route('backend.objects.image', $object))->assertOk();
    expect($full->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=300')
        ->and($full->headers->get('X-Content-Type-Options'))->toBe('nosniff');

    $thumbnail = $this->get(route('backend.objects.image', $object).'?size=thumb')->assertOk();
    expect(getimagesizefromstring($thumbnail->streamedContent())[0])->toBe(400);
    $small = $this->get(route('backend.objects.image', $object).'?size=small')->assertOk();
    expect(getimagesizefromstring($small->streamedContent())[0])->toBe(160);

    $versioned = $this->get(route('backend.objects.image', [$object, 'size' => 'small', 'v' => ObjectImageProcessor::version($path)]))->assertOk();
    expect($versioned->headers->get('Cache-Control'))->toContain('max-age=31536000')->toContain('immutable');
    $stale = $this->get(route('backend.objects.image', [$object, 'v' => 'outdated']))->assertOk();
    expect($stale->headers->get('Cache-Control'))->toContain('max-age=300')->not->toContain('immutable');

    $cachedHeaders = [
        'If-Modified-Since' => $full->headers->get('Last-Modified'),
        'If-None-Match' => $full->headers->get('ETag'),
    ];
    $this->get(route('backend.objects.image', $object), $cachedHeaders)->assertStatus(304);

    $legacyPath = 'objects/'.$object->id.'/legacy.jpg';
    Storage::disk('local')->put($legacyPath, UploadedFile::fake()->image('legacy.jpg', 800, 600)->getContent());
    $object->update(['image_path' => $legacyPath]);
    $this->get(route('backend.objects.image', $object), $cachedHeaders)->assertOk();
    $this->get(route('backend.objects.image', $object).'?size=thumb')->assertOk();
});

test('rejecting an object discards its pending image and thumbnail', function () {
    Storage::fake('local');
    $author = User::factory()->create();
    $moderator = User::factory()->create(['role' => 'moderator']);
    $response = $this->actingAs($author)->post(route('backend.objects.store'), [
        'canonical_name' => 'Rejected Photograph Object',
        'summary_body' => 'This object will be rejected and its photograph should not be kept on disk.',
        'language_code' => 'en',
        'sources' => ['https://example.com/rejected'],
        'image' => UploadedFile::fake()->image('rejected.jpg', 600, 400),
    ], ['Accept' => 'application/json'])->assertCreated();
    $object = MasterObject::findOrFail($response->json('id'));
    $path = $object->pending_image_path;

    $this->actingAs($moderator)->patchJson(route('admin.backend.objects.review', $object), [
        'status' => 'rejected', 'reason' => 'The submission is not accurate enough.',
    ])->assertOk();

    Storage::disk('local')->assertMissing([$path, ObjectImageProcessor::variantPath($path, 'thumb'), ObjectImageProcessor::variantPath($path, 'small')]);
    expect($object->fresh()->pending_image_path)->toBeNull()
        ->and($object->fresh()->pending_image_by)->toBeNull();
});

test('object image uploads are rate limited per member', function () {
    Storage::fake('local');
    $object = MasterObject::factory()->create();
    $member = User::factory()->create();

    foreach (range(1, 6) as $attempt) {
        $this->actingAs($member)->post(route('backend.objects.image.propose', $object), [
            'image' => UploadedFile::fake()->image("attempt-{$attempt}.jpg"),
        ], ['Accept' => 'application/json']);
    }

    $this->post(route('backend.objects.image.propose', $object), [
        'image' => UploadedFile::fake()->image('too-many.jpg'),
    ], ['Accept' => 'application/json'])->assertTooManyRequests();
});

test('full-resolution phone photos are accepted while oversized images are rejected', function () {
    Storage::fake('local');
    $object = MasterObject::factory()->create(['status' => 'verified']);
    $member = User::factory()->create();

    $this->actingAs($member)->post(route('backend.objects.image.propose', $object), [
        'image' => UploadedFile::fake()->image('wide.jpg', ObjectImageProcessor::MAX_INPUT_EDGE + 1, 100),
    ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors(['image' => __('votmee.image_too_large')]);

    $this->actingAs($member)->post(route('backend.objects.image.propose', $object), [
        'image' => UploadedFile::fake()->image('phone.jpg', 4032, 3024),
    ], ['Accept' => 'application/json'])->assertCreated();
    expect(getimagesizefromstring(Storage::disk('local')->get($object->fresh()->pending_image_path))[0])->toBe(ObjectImageProcessor::MAX_EDGE);
});

test('exif orientation is applied for rotated and mirrored photos', function (int $orientation, int $expectedWidth, bool $expectRedOnLeft) {
    Storage::fake('local');
    $object = MasterObject::factory()->create(['status' => 'verified']);

    $canvas = imagecreatetruecolor(300, 100);
    imagefilledrectangle($canvas, 0, 0, 149, 99, (int) imagecolorallocate($canvas, 255, 0, 0));
    imagefilledrectangle($canvas, 150, 0, 299, 99, (int) imagecolorallocate($canvas, 0, 0, 255));
    ob_start();
    imagejpeg($canvas, null, 95);
    $jpeg = (string) ob_get_clean();
    $tiff = "II*\0".pack('V', 8).pack('v', 1).pack('vvVvv', 0x0112, 3, 1, $orientation, 0).pack('V', 0);
    $exif = "Exif\0\0".$tiff;
    $photo = substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2);

    $this->actingAs(User::factory()->create())->post(route('backend.objects.image.propose', $object), [
        'image' => UploadedFile::fake()->createWithContent('photo.jpg', $photo),
    ], ['Accept' => 'application/json'])->assertCreated();

    $stored = imagecreatefromstring(Storage::disk('local')->get($object->fresh()->pending_image_path));
    $leftRed = (imagecolorat($stored, 5, 5) >> 16) & 0xFF;
    expect(imagesx($stored))->toBe($expectedWidth)
        ->and($leftRed > 128)->toBe($expectRedOnLeft);
})->with([
    'upright' => [1, 300, true],
    'mirrored' => [2, 300, false],
    'rotated 90° clockwise' => [6, 100, true],
]);
