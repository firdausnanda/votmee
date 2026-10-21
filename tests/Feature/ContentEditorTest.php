<?php

use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\ObjectContentRevision;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('writing and revision pages require the right user and object state', function () {
    $author = User::factory()->create();
    $other = User::factory()->create();
    $object = MasterObject::factory()->create();
    $pending = MasterObject::factory()->pending()->create(['submitted_by' => $author->id]);
    $article = ObjectArticle::factory()->create(['object_id' => $object->id, 'author_id' => $author->id]);

    $this->get(route('community.object.write', ['locale' => 'id', 'object' => $object->slug]))->assertRedirect();
    $this->actingAs($author)->get(route('community.object.write', ['locale' => 'id', 'object' => $object->slug]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Community/ContentEditor')->where('mode', 'article')->etc());
    $this->actingAs($author)->get(route('community.object.summary.edit', ['locale' => 'en', 'object' => $object->slug]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->where('mode', 'summary')->etc());
    $this->actingAs($author)->get(route('community.object.write', ['locale' => 'id', 'object' => $pending->slug]))->assertForbidden();
    $this->actingAs($other)->get(route('community.article.edit', ['locale' => 'id', 'article' => $article->id]))->assertForbidden();
    $this->actingAs($author)->get(route('community.article.edit', ['locale' => 'id', 'article' => $article->id]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->where('mode', 'article_revision')->etc());
    $this->actingAs($other)->get(route('community.object.submission.edit', ['locale' => 'id', 'object' => $pending->slug]))->assertForbidden();
    $this->actingAs($author)->get(route('community.object.submission.edit', ['locale' => 'id', 'object' => $pending->slug]))->assertForbidden();
    $pending->update(['status' => 'needs_revision']);
    $this->actingAs($author)->get(route('community.object.submission.edit', ['locale' => 'id', 'object' => $pending->slug]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->where('mode', 'submission')->etc());
});

test('published community articles have a public reading page', function () {
    $object = MasterObject::factory()->create();
    $article = ObjectArticle::factory()->create(['object_id' => $object->id]);
    $revision = ObjectContentRevision::factory()->create([
        'object_id' => $object->id,
        'article_id' => $article->id,
        'author_id' => $article->author_id,
        'title' => 'A detailed community review',
        'body' => 'The complete article body is available here.',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $article->update(['published_revision_id' => $revision->id]);

    $this->get(route('community.article', ['locale' => 'en', 'article' => $article->id]))
        ->assertStatus(301)->assertRedirect('/id/article/'.$article->id);
    $this->get(route('community.article', ['locale' => 'id', 'article' => $article->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Community/Article')
            ->where('locale', 'id')
            ->where('article.id', $article->id)
            ->where('revision.id', $revision->id)
            ->where('revision.body', 'The complete article body is available here.')
            ->where('object.slug', $object->slug)
            ->etc());
});

test('draft and unpublished community articles cannot be read publicly', function () {
    $object = MasterObject::factory()->create();
    $article = ObjectArticle::factory()->create(['object_id' => $object->id]);
    $revision = ObjectContentRevision::factory()->create([
        'object_id' => $object->id,
        'article_id' => $article->id,
        'author_id' => $article->author_id,
    ]);

    $this->get(route('community.article', ['locale' => 'id', 'article' => $article->id]))->assertNotFound();

    $article->update(['published_revision_id' => $revision->id]);
    $this->get(route('community.article', ['locale' => 'id', 'article' => $article->id]))->assertNotFound();

    $revision->update(['status' => 'published', 'published_at' => now()]);
    $object->update(['status' => 'rejected']);
    $this->get(route('community.article', ['locale' => 'id', 'article' => $article->id]))->assertNotFound();
});

test('rich editor submissions retain markdown while older revisions remain plain text', function () {
    $author = User::factory()->create();
    $object = MasterObject::factory()->create();
    $old = ObjectContentRevision::factory()->create([
        'object_id' => $object->id,
        'article_id' => null,
        'author_id' => $author->id,
        'body' => 'An older plain text summary with sufficient detail to display.',
        'status' => 'published',
        'published_at' => now(),
    ]);
    expect($old->refresh()->body_format)->toBe('plain');

    $this->actingAs($author)->postJson(route('backend.objects.summary.store', $object->slug), [
        'body' => '## Updated summary\n\nThis object has a **reliable** source and useful context.',
        'body_format' => 'markdown',
        'language_code' => 'en',
        'sources' => ['https://example.com/source'],
    ])->assertCreated()->assertJsonPath('body_format', 'markdown');
    $this->get(route('community.object.summary.edit', ['locale' => 'en', 'object' => $object->slug]))
        ->assertInertia(fn (Assert $page) => $page->where('reviewPending', true)->where('initial.body_format', 'markdown')->etc());
    $this->get(route('community.object', ['locale' => 'id', 'object' => $object->slug]))
        ->assertInertia(fn (Assert $page) => $page->where('summary.id', $old->id)->where('summary.body_format', 'plain')->etc());

    $this->actingAs($author)->postJson(route('backend.objects.articles.store', $object->slug), [
        'type' => 'experience',
        'title' => 'A month with this object',
        'body' => 'I used this object for a month. **It worked well** every day.',
        'body_format' => 'markdown',
        'language_code' => 'en',
        'sources' => [],
    ])->assertCreated();
    expect(ObjectContentRevision::query()->whereNotNull('article_id')->firstOrFail()->body_format)->toBe('markdown');
});

test('resubmitted object summaries keep the rich text format', function () {
    $author = User::factory()->create();
    $object = MasterObject::factory()->pending()->create(['submitted_by' => $author->id, 'status' => 'needs_revision']);

    $this->actingAs($author)->patchJson(route('backend.objects.resubmit', $object), [
        'canonical_name' => $object->canonical_name,
        'summary_body' => '## Revised summary\n\nThis entry now has **clear facts** and a source.',
        'summary_body_format' => 'markdown',
        'language_code' => 'id',
        'sources' => ['https://example.com/revised'],
    ])->assertOk();

    expect($object->contentRevisions()->latest()->firstOrFail()->body_format)->toBe('markdown');
});
