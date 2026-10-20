<?php

use App\Models\ContentImage;
use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\User;
use App\Services\ContentImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function uploadContentImage(User $user, int $width = 2400, int $height = 1600): ContentImage
{
    $id = test()->actingAs($user)->post(route('backend.content-images.store'), [
        'image' => UploadedFile::fake()->image('photo.jpg', $width, $height),
    ], ['Accept' => 'application/json'])->assertCreated()->json('id');

    return ContentImage::findOrFail($id);
}

function markdownWithImage(ContentImage ...$images): string
{
    $markdown = 'This summary explains the object in enough detail for the community to review.';
    foreach ($images as $image) {
        $markdown .= "\n\n![Photo]({$image->url()})";
    }

    return $markdown;
}

test('uploaded content images are resized and stay private until a revision using them is published', function () {
    Storage::fake('local');
    $author = User::factory()->create();
    $moderator = User::factory()->create(['role' => 'moderator']);
    $object = MasterObject::factory()->create(['status' => 'verified']);

    $image = uploadContentImage($author);
    $disk = Storage::disk('local');
    expect([$image->width, $image->height])->toBe([1600, 1067])
        ->and(getimagesizefromstring($disk->get(ContentImageService::variantPath($image->path, 'medium')))[0])->toBe(800);

    $revisionId = $this->actingAs($author)->postJson(route('backend.objects.summary.store', $object), [
        'body' => markdownWithImage($image), 'body_format' => 'markdown', 'language_code' => 'en', 'sources' => ['https://example.com/source'],
    ])->assertCreated()->json('id');
    expect($image->revisions()->pluck('object_content_revisions.id')->all())->toBe([$revisionId]);

    $ownView = $this->get($image->url())->assertOk();
    expect($ownView->headers->get('Cache-Control'))->toContain('private');
    $this->actingAs($moderator)->get($image->url().'&size=medium')->assertOk();
    $this->actingAs(User::factory()->create())->get($image->url())->assertNotFound();
    auth()->logout();
    $this->get($image->url())->assertNotFound();

    $this->actingAs($moderator)->patchJson(route('admin.backend.content-revisions.review', $revisionId), [
        'status' => 'published', 'reason' => 'Accurate and well sourced.',
    ])->assertOk();
    auth()->logout();

    $published = $this->get($image->url())->assertOk();
    expect($published->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=86400')->toContain('s-maxage=600')
        ->and($published->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

test('images in a hidden article are no longer public', function () {
    Storage::fake('local');
    $author = User::factory()->create();
    $moderator = User::factory()->create(['role' => 'moderator']);
    $object = MasterObject::factory()->create(['status' => 'verified']);
    $image = uploadContentImage($author, 800, 600);

    $article = ObjectArticle::findOrFail($this->actingAs($author)->postJson(route('backend.objects.articles.store', $object), [
        'type' => 'experience', 'title' => 'My visit with photos', 'body' => markdownWithImage($image),
        'body_format' => 'markdown', 'language_code' => 'en',
    ])->assertCreated()->json('id'));
    $this->actingAs($moderator)->patchJson(route('admin.backend.content-revisions.review', $article->revisions()->value('id')), [
        'status' => 'published', 'reason' => 'A helpful first-hand account.',
    ])->assertOk();
    auth()->logout();
    $this->get($image->url())->assertOk();

    $this->actingAs($moderator)->patchJson(route('admin.backend.articles.hide', $article), ['reason' => 'Contains a misleading photo.'])->assertOk();
    auth()->logout();
    $this->get($image->url())->assertNotFound();
});

test('content may only embed images uploaded by the author or already published', function () {
    Storage::fake('local');
    $author = User::factory()->create();
    $otherMember = User::factory()->create();
    $object = MasterObject::factory()->create(['status' => 'verified']);
    $privateImage = uploadContentImage($otherMember, 400, 300);
    $submit = fn (string $body) => $this->actingAs($author)->postJson(route('backend.objects.summary.store', $object), [
        'body' => $body, 'body_format' => 'markdown', 'language_code' => 'en', 'sources' => ['https://example.com/source'],
    ]);

    $submit(markdownWithImage()."\n\n![Tracker](https://evil.example/pixel.png)")
        ->assertUnprocessable()->assertJsonValidationErrors(['body' => __('votmee.content_image_external')]);
    $submit(markdownWithImage($privateImage))
        ->assertUnprocessable()->assertJsonValidationErrors(['body' => __('votmee.content_image_invalid')]);
    $ownImages = collect(range(1, ContentImageService::MAX_IMAGES_PER_BODY + 1))
        ->map(fn () => ContentImage::factory()->create(['uploader_id' => $author->id]));
    $submit(markdownWithImage(...$ownImages->all()))
        ->assertUnprocessable()->assertJsonValidationErrors(['body' => __('votmee.content_image_limit', ['max' => ContentImageService::MAX_IMAGES_PER_BODY])]);

    expect($object->contentRevisions()->count())->toBe(0);
});

test('new object submissions link the images used in their summary', function () {
    Storage::fake('local');
    $author = User::factory()->create();
    $image = uploadContentImage($author, 600, 400);

    $objectId = $this->actingAs($author)->postJson(route('backend.objects.store'), [
        'canonical_name' => 'Illustrated Object',
        'summary_body' => markdownWithImage($image),
        'summary_body_format' => 'markdown',
        'language_code' => 'en',
        'sources' => ['https://example.com/illustrated'],
    ])->assertCreated()->json('id');

    expect($image->revisions()->value('object_content_revisions.object_id'))->toBe($objectId);
});

test('content image uploads require an eligible member', function () {
    Storage::fake('local');
    $image = fn () => ['image' => UploadedFile::fake()->image('photo.jpg', 300, 300)];

    $this->post(route('backend.content-images.store'), $image(), ['Accept' => 'application/json'])->assertUnauthorized();
    $this->actingAs(User::factory()->create(['restricted_until' => now()->addDay()]))
        ->post(route('backend.content-images.store'), $image(), ['Accept' => 'application/json'])->assertForbidden();

    Storage::disk('local')->assertDirectoryEmpty('/');
});
