<?php

use App\Models\User;
use App\Services\ProfileImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('members can upload a cropped avatar and cover that appear on their profile', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $avatar = $this->actingAs($user)->post(route('backend.profile.image.update', 'avatar'), [
        'image' => UploadedFile::fake()->image('avatar.jpg', 1200, 800),
    ], ['Accept' => 'application/json'])->assertOk();
    $polyglot = UploadedFile::fake()->createWithContent('cover.png', UploadedFile::fake()->image('cover.png', 3000, 1500)->getContent().'<?php echo "pwned"; ?>');
    $this->post(route('backend.profile.image.update', 'cover'), ['image' => $polyglot], ['Accept' => 'application/json'])->assertOk();

    $user->refresh();
    $disk = Storage::disk('local');
    $sizeOf = fn (string $path): array => array_slice(getimagesizefromstring($disk->get($path)), 0, 2);
    expect($avatar->json('url'))->toBe($user->avatar_url)
        ->and($sizeOf($user->avatar_path))->toBe([400, 400])
        ->and($sizeOf(ProfileImageProcessor::variantPath($user->avatar_path, 'small')))->toBe([96, 96])
        ->and($sizeOf($user->cover_path))->toBe([1600, 400])
        ->and($sizeOf(ProfileImageProcessor::variantPath($user->cover_path, 'small')))->toBe([800, 200])
        ->and($disk->get($user->cover_path))->not->toContain('<?php');

    auth()->logout();
    $this->get('/en/profile/'.$user->username)
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.avatar_url', $user->avatar_url)
            ->where('profile.cover_url', $user->cover_url)
            ->missing('profile.avatar_path')
            ->etc());

    $image = $this->get($user->avatar_url.'&size=small')->assertOk();
    expect(getimagesizefromstring($image->streamedContent())[0])->toBe(96)
        ->and($image->headers->get('Cache-Control'))->toContain('immutable')
        ->and($image->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

test('replacing or removing a profile image deletes the old files', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('backend.profile.image.update', 'avatar'), [
        'image' => UploadedFile::fake()->image('first.jpg', 500, 500),
    ], ['Accept' => 'application/json'])->assertOk();
    $firstPath = $user->fresh()->avatar_path;

    $this->post(route('backend.profile.image.update', 'avatar'), [
        'image' => UploadedFile::fake()->image('second.jpg', 500, 500),
    ], ['Accept' => 'application/json'])->assertOk();
    $secondPath = $user->fresh()->avatar_path;

    Storage::disk('local')->assertMissing([$firstPath, ProfileImageProcessor::variantPath($firstPath, 'small')]);
    Storage::disk('local')->assertExists($secondPath);

    $this->deleteJson(route('backend.profile.image.destroy', 'avatar'))->assertOk()->assertJson(['url' => null]);
    Storage::disk('local')->assertMissing([$secondPath, ProfileImageProcessor::variantPath($secondPath, 'small')]);
    expect($user->fresh()->avatar_path)->toBeNull();
    $this->get(route('backend.users.image', [$user, 'avatar']))->assertNotFound();
});

test('profile image uploads require an eligible member and a valid image', function () {
    Storage::fake('local');
    $image = fn () => ['image' => UploadedFile::fake()->image('avatar.jpg', 300, 300)];

    $this->post(route('backend.profile.image.update', 'avatar'), $image(), ['Accept' => 'application/json'])->assertUnauthorized();
    $this->actingAs(User::factory()->create(['restricted_until' => now()->addDay()]))
        ->post(route('backend.profile.image.update', 'avatar'), $image(), ['Accept' => 'application/json'])->assertForbidden();

    $member = User::factory()->create();
    $this->actingAs($member)->post(route('backend.profile.image.update', 'avatar'), [
        'image' => UploadedFile::fake()->create('avatar.txt', 2, 'text/plain'),
    ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('image');
    $this->post('/backend/profile/banner', $image(), ['Accept' => 'application/json'])->assertClientError();

    expect($member->fresh()->avatar_path)->toBeNull();
    Storage::disk('local')->assertDirectoryEmpty('/');
});

test('profile image uploads are rate limited per member', function () {
    Storage::fake('local');
    $member = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->actingAs($member)->post(route('backend.profile.image.update', 'avatar'), [
            'image' => UploadedFile::fake()->image("avatar-{$attempt}.jpg", 200, 200),
        ], ['Accept' => 'application/json'])->assertOk();
    }

    $this->post(route('backend.profile.image.update', 'avatar'), [
        'image' => UploadedFile::fake()->image('avatar-6.jpg', 200, 200),
    ], ['Accept' => 'application/json'])->assertTooManyRequests();
});
