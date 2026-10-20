<?php

use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\ObjectContentRevision;
use App\Models\RankingPost;
use App\Models\User;
use App\Services\CommentImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('members can attach images to ranking comments and readers see them in order', function () {
    Storage::fake('local');
    $post = RankingPost::factory()->create();
    $member = User::factory()->create();
    $polyglot = UploadedFile::fake()->createWithContent('second.png', UploadedFile::fake()->image('second.png', 900, 600)->getContent().'<?php echo "pwned"; ?>');

    $response = $this->actingAs($member)->post(route('backend.comments.store', $post), [
        'body' => 'Here are my photos',
        'images' => [UploadedFile::fake()->image('first.jpg', 3200, 2400), $polyglot],
    ], ['Accept' => 'application/json'])->assertCreated()->assertJsonCount(2, 'images');

    $comment = Comment::findOrFail($response->json('id'));
    [$first, $second] = $comment->images->all();
    $disk = Storage::disk('local');
    expect([$first->width, $first->height])->toBe([1600, 1200])
        ->and(getimagesizefromstring($disk->get(CommentImageProcessor::variantPath($first->path, 'thumb')))[0])->toBe(480)
        ->and($disk->get($second->path))->not->toContain('<?php')
        ->and($response->json('images.0.url'))->not->toContain($first->path);

    auth()->logout();
    $this->getJson(route('backend.comments.index', $post))->assertOk()
        ->assertJsonPath('comments.0.images.0.id', $first->id)
        ->assertJsonPath('comments.0.images.1.id', $second->id);
    $this->get(route('community.ranking', ['locale' => 'id', 'post' => $post->slug]))
        ->assertInertia(fn (Assert $page) => $page->has('comments.0.images', 2)->etc());

    $image = $this->get($response->json('images.0.thumb_url'))->assertOk();
    expect(getimagesizefromstring($image->streamedContent())[0])->toBe(480)
        ->and($image->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($image->headers->get('Cache-Control'))->toContain('max-age=86400')->toContain('s-maxage=600')->not->toContain('immutable');
});

test('object and article discussions accept comment images too', function () {
    Storage::fake('local');
    $member = User::factory()->create();
    $object = MasterObject::factory()->create(['status' => 'verified']);
    $article = ObjectArticle::factory()->create(['object_id' => $object->id]);
    $revision = ObjectContentRevision::factory()->create([
        'object_id' => $object->id, 'article_id' => $article->id, 'author_id' => $article->author_id,
        'title' => 'A story with photos', 'status' => 'published', 'published_at' => now(),
    ]);
    $article->update(['published_revision_id' => $revision->id]);

    $this->actingAs($member)->post(route('backend.objects.comments.store', $object), [
        'body' => 'Object photo', 'images' => [UploadedFile::fake()->image('object.jpg', 400, 300)],
    ], ['Accept' => 'application/json'])->assertCreated();
    $articleImageUrl = $this->post(route('backend.articles.comments.store', $article), [
        'body' => 'Article photo', 'images' => [UploadedFile::fake()->image('article.jpg', 400, 300)],
    ], ['Accept' => 'application/json'])->assertCreated()->json('images.0.url');

    $this->getJson(route('backend.objects.comments.index', $object))->assertJsonCount(1, 'comments.0.images');
    $this->getJson(route('backend.articles.comments.index', $article))->assertJsonCount(1, 'comments.0.images');
    $this->get($articleImageUrl)->assertOk();

    $article->publishedRevision->update(['status' => 'hidden']);
    $this->get($articleImageUrl)->assertNotFound();
});

test('invalid comment image submissions are rejected without storing anything', function () {
    Storage::fake('local');
    $post = RankingPost::factory()->create();
    $member = User::factory()->create();
    $root = Comment::factory()->create(['ranking_post_id' => $post->id]);
    $image = fn (): UploadedFile => UploadedFile::fake()->image('photo.jpg', 300, 300);
    $store = fn (array $payload) => $this->actingAs($member)->post(route('backend.comments.store', $post), ['body' => 'With images', ...$payload], ['Accept' => 'application/json']);

    $store(['parent_id' => $root->id, 'images' => [$image()]])->assertUnprocessable()->assertJsonValidationErrors('images');
    $store(['images' => array_map(fn () => $image(), range(1, CommentImageProcessor::MAX_IMAGES + 1))])->assertUnprocessable()->assertJsonValidationErrors('images');
    $store(['images' => [UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf')]])->assertUnprocessable()->assertJsonValidationErrors('images.0');

    expect(Comment::query()->count())->toBe(1);
    Storage::disk('local')->assertDirectoryEmpty('/');
});

test('comment images disappear when the comment is hidden or the ranking is unpublished', function () {
    Storage::fake('local');
    $post = RankingPost::factory()->create();
    $moderator = User::factory()->create(['role' => 'moderator']);
    $upload = fn (): string => $this->actingAs(User::factory()->create())->post(route('backend.comments.store', $post), [
        'body' => 'Photo comment', 'images' => [UploadedFile::fake()->image('photo.jpg', 300, 300)],
    ], ['Accept' => 'application/json'])->assertCreated()->json('images.0.url');
    $hiddenUrl = $upload();
    $unpublishedUrl = $upload();

    $commentId = Comment::query()->oldest('id')->value('id');
    $this->actingAs($moderator)->patchJson(route('admin.backend.comments.hide', $commentId), ['reason' => 'Inappropriate photo'])->assertOk();
    auth()->logout();
    $this->get($hiddenUrl)->assertNotFound();
    $this->get($unpublishedUrl)->assertOk();

    $post->update(['status' => 'draft']);
    $this->get($unpublishedUrl)->assertNotFound();
});

test('only comments with images count against the upload rate limit', function () {
    Storage::fake('local');
    $post = RankingPost::factory()->create();
    $member = User::factory()->create();
    $comment = fn (bool $withImage) => $this->actingAs($member)->post(route('backend.comments.store', $post), [
        'body' => 'Rate limited comment',
        ...($withImage ? ['images' => [UploadedFile::fake()->image('photo.jpg', 200, 200)]] : []),
    ], ['Accept' => 'application/json']);

    foreach (range(1, 5) as $attempt) {
        $comment(true)->assertCreated();
    }

    $comment(true)->assertTooManyRequests();
    $comment(false)->assertCreated();
});
