<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\ObjectContentRevision;
use App\Models\RankingEntry;
use App\Models\RankingPost;
use App\Models\User;
use App\Services\ModerationService;
use Inertia\Testing\AssertableInertia as Assert;

test('new objects require a sourced summary and publish it with object verification', function () {
    $author = User::factory()->create();
    $moderator = User::factory()->create(['role' => 'moderator']);
    $payload = [
        'canonical_name' => 'Example Camera 2026',
        'summary_body' => 'This camera is a compact device designed for everyday photography.',
        'language_code' => 'en',
        'sources' => ['https://example.com/camera'],
    ];

    $this->actingAs($author)->postJson(route('backend.objects.store'), ['canonical_name' => $payload['canonical_name']])
        ->assertUnprocessable()->assertJsonValidationErrors(['summary_body', 'language_code', 'sources']);
    $objectId = $this->actingAs($author)->postJson(route('backend.objects.store'), $payload)
        ->assertCreated()->json('id');
    $object = MasterObject::findOrFail($objectId);
    $this->get('/en/object/'.$object->slug)->assertOk()->assertInertia(fn (Assert $page) => $page->where('summary', null)->etc());

    $this->actingAs($moderator)->patchJson(route('admin.backend.objects.review', $object), [
        'status' => 'verified', 'reason' => 'Identity and source checked.',
    ])->assertOk();
    $this->assertDatabaseHas('object_content_revisions', ['object_id' => $object->id, 'status' => 'published']);
    $this->get('/id/object/'.$object->slug)->assertStatus(301)->assertRedirect('/en/object/'.$object->slug);
    $this->get('/en/object/'.$object->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('summary.body', $payload['summary_body'])
        ->where('summary.language_code', 'en')
        ->etc());
});

test('object author sees review feedback and can resubmit the object and summary', function () {
    $author = User::factory()->create();
    $moderator = User::factory()->create(['role' => 'moderator']);
    $object = MasterObject::factory()->pending()->create(['submitted_by' => $author->id]);
    $object->contentRevisions()->create([
        'author_id' => $author->id,
        'body' => 'The first proposed description of this example object for review.',
        'sources' => ['https://example.com/first'],
        'language_code' => 'id',
        'status' => 'pending',
    ]);
    $this->actingAs($moderator)->patchJson(route('admin.backend.objects.review', $object), [
        'status' => 'needs_revision', 'reason' => 'Please use a more specific source.',
    ])->assertOk();

    $this->actingAs($author)->get('/id/object/'.$object->slug)->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('reviewFeedback', fn ($value) => str_contains($value, 'specific source'))->etc());
    $this->actingAs(User::factory()->create())->patchJson(route('backend.objects.resubmit', $object), [
        'canonical_name' => $object->canonical_name,
        'summary_body' => 'A revised summary with clearer factual details and a better source.',
        'sources' => ['https://example.com/better'],
        'language_code' => 'en',
    ])->assertForbidden();
    $this->actingAs($author)->patchJson(route('backend.objects.resubmit', $object), [
        'canonical_name' => $object->canonical_name,
        'summary_body' => 'A revised summary with clearer factual details and a better source.',
        'sources' => ['https://example.com/better'],
        'language_code' => 'en',
    ])->assertOk()->assertJsonPath('status', 'pending');
    expect($object->contentRevisions()->where('status', 'pending')->count())->toBe(1);
});

test('summary revisions keep the published text visible until a moderator approves the replacement', function () {
    $object = MasterObject::factory()->create();
    $author = User::factory()->create();
    $moderator = User::factory()->create(['role' => 'moderator']);
    $old = $object->contentRevisions()->create([
        'author_id' => $author->id, 'body' => 'The existing public description of this object remains visible.',
        'sources' => ['https://example.com/old'], 'language_code' => 'en', 'status' => 'published', 'published_at' => now()->subDay(),
    ]);

    $revisionId = $this->actingAs($author)->postJson(route('backend.objects.summary.store', $object->slug), [
        'body' => 'A newly proposed description with updated information and a source.',
        'sources' => ['https://example.com/new'], 'language_code' => 'id',
    ])->assertCreated()->json('id');
    $this->get('/en/object/'.$object->slug)->assertInertia(fn (Assert $page) => $page->where('summary.id', $old->id)->etc());

    $this->actingAs($author)->patchJson(route('admin.backend.content-revisions.review', $revisionId), [
        'status' => 'published', 'reason' => 'Self approval is not allowed.',
    ])->assertForbidden();

    $this->actingAs($moderator)->patchJson(route('admin.backend.content-revisions.review', $revisionId), [
        'status' => 'published', 'reason' => 'Updated source checked.',
    ])->assertOk();
    $this->actingAs($moderator)->patchJson(route('admin.backend.content-revisions.review', $revisionId), [
        'status' => 'published', 'reason' => 'Duplicate review is not allowed.',
    ])->assertUnprocessable();
    $this->get('/en/object/'.$object->slug)->assertStatus(301)->assertRedirect('/id/object/'.$object->slug);
    $this->get('/id/object/'.$object->slug)->assertInertia(fn (Assert $page) => $page->where('summary.id', $revisionId)->etc());
});

test('informative articles need sources and revisions remain private until approved', function () {
    $object = MasterObject::factory()->create();
    $author = User::factory()->create();
    $other = User::factory()->create();
    $moderator = User::factory()->create(['role' => 'moderator']);
    $payload = [
        'type' => 'informative', 'title' => 'How this object works',
        'body' => 'This article explains the mechanism and the context of this object.',
        'language_code' => 'en', 'sources' => ['https://example.com/guide'],
    ];
    $this->actingAs($author)->postJson(route('backend.objects.articles.store', $object->slug), [
        ...$payload, 'sources' => [],
    ])->assertUnprocessable()->assertJsonValidationErrors('sources');
    $articleId = $this->actingAs($author)->postJson(route('backend.objects.articles.store', $object->slug), $payload)
        ->assertCreated()->json('id');
    $article = ObjectArticle::findOrFail($articleId);
    $initial = $article->revisions()->firstOrFail();
    $this->actingAs($other)->get('/en/object/'.$object->slug)->assertInertia(fn (Assert $page) => $page->where('articles.total', 0)->where('myRevisions', [])->etc());
    $this->actingAs($moderator)->patchJson(route('admin.backend.content-revisions.review', $initial), [
        'status' => 'published', 'reason' => 'Information and source checked.',
    ])->assertOk();

    $this->actingAs($other)->patchJson(route('backend.articles.revise', $article), [
        'title' => 'A different title', 'body' => $payload['body'], 'language_code' => 'en', 'sources' => $payload['sources'],
    ])->assertForbidden();
    $this->actingAs($author)->patchJson(route('backend.articles.revise', $article), [
        'title' => 'Newly revised article', 'body' => 'A changed explanation with many more details than the published version.',
        'language_code' => 'id', 'sources' => ['https://example.com/revision'],
    ])->assertCreated();
    $this->actingAs($other)->get('/id/object/'.$object->slug)->assertInertia(fn (Assert $page) => $page
        ->where('articles.data.0.published_revision.title', $payload['title'])
        ->where('articles.data.0.published_revision.excerpt', $payload['body'])
        ->missing('articles.data.0.published_revision.body')
        ->where('myRevisions', [])->etc());
});

test('community article previews render markdown as short plain text', function () {
    $object = MasterObject::factory()->create();
    $article = ObjectArticle::factory()->create(['object_id' => $object->id]);
    $revision = ObjectContentRevision::factory()->create([
        'object_id' => $object->id,
        'article_id' => $article->id,
        'author_id' => $article->author_id,
        'title' => 'A useful article',
        'body' => "## First impressions\n\nThis **object** is [easy to use](https://example.com) & reliable. ".str_repeat('More details. ', 20),
        'body_format' => 'markdown',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $article->update(['published_revision_id' => $revision->id]);

    $this->get(route('community.object', ['locale' => 'id', 'object' => $object->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('articles.data.0.published_revision.excerpt', fn (string $excerpt): bool => str_starts_with($excerpt, 'First impressions This object is easy to use & reliable.')
                && mb_strlen($excerpt) <= 183
                && ! str_contains($excerpt, '**'))
            ->missing('articles.data.0.published_revision.body')
            ->etc());
});

test('pending summary and article proposals appear only in their authors object page data', function () {
    $object = MasterObject::factory()->create();
    $author = User::factory()->create();
    $other = User::factory()->create();
    $article = ObjectArticle::factory()->create(['object_id' => $object->id, 'author_id' => $author->id]);
    $articleRevision = ObjectContentRevision::factory()->create([
        'object_id' => $object->id,
        'article_id' => $article->id,
        'author_id' => $author->id,
        'title' => 'A proposed community story',
        'body' => "## Opening\n\nThis **draft** is awaiting review.",
        'body_format' => 'markdown',
        'status' => 'pending',
    ]);
    $summaryRevision = ObjectContentRevision::factory()->create([
        'object_id' => $object->id,
        'author_id' => $author->id,
        'body' => 'A proposed factual summary awaiting review.',
        'status' => 'pending',
    ]);

    $this->actingAs($author)->get('/id/object/'.$object->slug)->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('articles.total', 0)
            ->where('myRevisions', function ($revisions) use ($article, $articleRevision, $summaryRevision): bool {
                $ownRevisions = collect($revisions)->keyBy('id');

                return $ownRevisions->count() === 2
                    && $ownRevisions[$articleRevision->id]['status'] === 'pending'
                    && $ownRevisions[$articleRevision->id]['excerpt'] === 'Opening This draft is awaiting review.'
                    && $ownRevisions[$articleRevision->id]['article']['type'] === $article->type
                    && $ownRevisions[$summaryRevision->id]['excerpt'] === 'A proposed factual summary awaiting review.'
                    && $ownRevisions->every(fn ($revision) => ! array_key_exists('body', $revision)
                        && ! array_key_exists('sources', $revision));
            })->etc());

    $this->actingAs($other)->get('/id/object/'.$object->slug)->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('articles.total', 0)->where('myRevisions', [])->etc());
    auth()->logout();
    $this->get('/id/object/'.$object->slug)->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('myRevisions', [])->etc());
});

test('object articles and rankings paginate independently when both lists grow', function () {
    $object = MasterObject::factory()->create();
    $author = User::factory()->create();
    $category = Category::factory()->create();

    for ($index = 0; $index < 11; $index++) {
        $article = ObjectArticle::factory()->create(['object_id' => $object->id, 'author_id' => $author->id]);
        $revision = ObjectContentRevision::factory()->create([
            'object_id' => $object->id,
            'article_id' => $article->id,
            'author_id' => $author->id,
            'title' => 'Article '.($index + 1),
            'status' => 'published',
            'published_at' => now()->subMinutes($index),
        ]);
        $article->update(['published_revision_id' => $revision->id]);

        $ranking = RankingPost::factory()->create([
            'creator_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Ranking '.($index + 1),
            'published_at' => now()->subMinutes($index),
        ]);
        RankingEntry::factory()->create(['ranking_post_id' => $ranking->id, 'object_id' => $object->id]);
    }

    $this->get(route('community.object', [
        'locale' => 'id', 'object' => $object->slug, 'articles_page' => 2, 'rankings_page' => 2,
    ]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('articles.total', 11)
        ->where('articles.current_page', 2)
        ->has('articles.data', 1)
        ->where('articles.prev_page_url', fn (string $url): bool => str_contains($url, 'rankings_page=2'))
        ->where('rankings.total', 11)
        ->where('rankingTotal', 11)
        ->where('rankings.current_page', 2)
        ->has('rankings.data', 1)
        ->where('rankings.prev_page_url', fn (string $url): bool => str_contains($url, 'articles_page=2'))
        ->etc());
});

test('object ranking search and oldest order include records beyond the first page', function () {
    $object = MasterObject::factory()->create();
    $author = User::factory()->create();
    $category = Category::factory()->create();
    $oldest = RankingPost::factory()->create([
        'creator_id' => $author->id,
        'category_id' => $category->id,
        'title' => 'Hidden history',
        'published_at' => now()->subDays(30),
    ]);
    RankingEntry::factory()->create(['ranking_post_id' => $oldest->id, 'object_id' => $object->id]);

    for ($index = 0; $index < 11; $index++) {
        $ranking = RankingPost::factory()->create([
            'creator_id' => $author->id,
            'category_id' => $category->id,
            'title' => 'Recent ranking '.($index + 1),
            'published_at' => now()->subDays($index),
        ]);
        RankingEntry::factory()->create(['ranking_post_id' => $ranking->id, 'object_id' => $object->id]);
    }

    $this->get(route('community.object', ['locale' => 'id', 'object' => $object->slug]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rankings.total', 12)
            ->has('rankings.data', 10)
            ->where('rankings.data', fn ($rankings): bool => collect($rankings)->doesntContain('id', $oldest->id))
            ->etc());
    $this->get(route('community.object', ['locale' => 'id', 'object' => $object->slug, 'ranking_q' => 'Hidden']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rankings.total', 1)
            ->where('rankingTotal', 12)
            ->where('rankingFilters.search', 'Hidden')
            ->where('rankings.data.0.id', $oldest->id)
            ->etc());
    $this->get(route('community.object', ['locale' => 'id', 'object' => $object->slug, 'ranking_sort' => 'oldest']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rankingFilters.sort', 'oldest')
            ->where('rankings.data.0.id', $oldest->id)
            ->etc());
});

test('experience articles need no source and merged object content follows the current master', function () {
    $source = MasterObject::factory()->create();
    $target = MasterObject::factory()->create();
    $author = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $moderator = User::factory()->create(['role' => 'moderator']);
    $articleId = $this->actingAs($author)->postJson(route('backend.objects.articles.store', $source->slug), [
        'type' => 'experience', 'title' => 'My first month using this',
        'body' => 'I used this object every day for a month and found it reliable.',
        'language_code' => 'en', 'sources' => [],
    ])->assertCreated()->json('id');
    $article = ObjectArticle::findOrFail($articleId);
    $this->actingAs($moderator)->patchJson(route('admin.backend.content-revisions.review', $article->revisions()->firstOrFail()), [
        'status' => 'published', 'reason' => 'Personal experience clearly labeled.',
    ])->assertOk();
    $comment = Comment::create(['object_id' => $source->id, 'user_id' => $author->id, 'body' => 'A comment on the original object.', 'status' => 'visible']);

    app(ModerationService::class)->mergeObject($admin, $source, $target, 'These are the same object.');
    $this->get('/en/object/'.$target->slug)->assertInertia(fn (Assert $page) => $page->where('articles.data.0.id', $article->id)->etc());
    $this->get(route('community.article', ['locale' => 'en', 'article' => $article->id]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->where('object.slug', $target->slug)->etc());
    $this->getJson(route('backend.objects.comments.index', $target->slug))->assertJsonPath('comments.0.id', $comment->id);
    $this->actingAs($author)->postJson(route('backend.objects.comments.store', $target->slug), [
        'body' => 'A reply while the objects are merged.', 'parent_id' => $comment->id,
    ])->assertCreated()->assertJsonPath('object_id', $source->id);
    app(ModerationService::class)->undoMerge($admin, $source, 'Distinct objects after review.');
    $this->get('/en/object/'.$target->slug)->assertInertia(fn (Assert $page) => $page->where('articles.total', 0)->etc());
    $this->getJson(route('backend.objects.comments.index', $target->slug))->assertJsonPath('total', 0);
    $this->getJson(route('backend.objects.comments.index', $source->slug))->assertJsonPath('total', 2);
});

test('object comments support one-level replies, likes, and reject cross-object parents', function () {
    $object = MasterObject::factory()->create();
    $otherObject = MasterObject::factory()->create();
    $author = User::factory()->create();
    $parentId = $this->actingAs($author)->postJson(route('backend.objects.comments.store', $object->slug), [
        'body' => 'The first object comment.',
    ])->assertCreated()->json('id');
    $replyId = $this->actingAs($author)->postJson(route('backend.objects.comments.store', $object->slug), [
        'body' => 'A reply to the first comment.', 'parent_id' => $parentId,
    ])->assertCreated()->assertJsonPath('parent_id', $parentId)->json('id');
    $this->actingAs($author)->postJson(route('backend.objects.comments.store', $object->slug), [
        'body' => 'A nested reply.', 'parent_id' => $replyId,
    ])->assertCreated()->assertJsonPath('parent_id', $parentId);
    $this->actingAs($author)->postJson(route('backend.objects.comments.store', $otherObject->slug), [
        'body' => 'Wrong object.', 'parent_id' => $parentId,
    ])->assertUnprocessable()->assertJsonValidationErrors('parent_id');
    $this->actingAs($author)->postJson(route('backend.comments.like', $parentId))->assertOk()->assertJsonPath('likes_count', 1);
    $this->getJson(route('backend.objects.comments.index', $object->slug))->assertOk()->assertJsonPath('total', 3);
    $pending = MasterObject::factory()->pending()->create();
    $this->actingAs($author)->postJson(route('backend.objects.comments.store', $pending->slug), ['body' => 'Too soon.'])->assertForbidden();
});

test('published articles can be reported and hidden by a moderator', function () {
    $object = MasterObject::factory()->create();
    $author = User::factory()->create();
    $reporter = User::factory()->create();
    $moderator = User::factory()->create(['role' => 'moderator']);
    $article = ObjectArticle::factory()->create(['object_id' => $object->id, 'author_id' => $author->id]);
    $revision = ObjectContentRevision::factory()->create([
        'object_id' => $object->id, 'article_id' => $article->id, 'author_id' => $author->id,
        'title' => 'A personal account', 'sources' => [], 'status' => 'published', 'published_at' => now(),
    ]);
    $article->update(['published_revision_id' => $revision->id]);

    $this->actingAs($reporter)->postJson(route('backend.reports.store'), [
        'target_type' => 'object_article', 'target_id' => $article->id,
        'reason' => 'This article contains misleading claims.',
    ])->assertCreated();
    $this->actingAs($reporter)->patchJson(route('admin.backend.articles.hide', $article), [
        'reason' => 'Claims are not supported.',
    ])->assertForbidden();
    $this->actingAs($moderator)->patchJson(route('admin.backend.articles.hide', $article), [
        'reason' => 'Claims are not supported.',
    ])->assertOk()->assertJsonPath('published_revision_id', null);
    $this->get('/en/object/'.$object->slug)->assertInertia(fn (Assert $page) => $page->where('articles.total', 0)->etc());
});
