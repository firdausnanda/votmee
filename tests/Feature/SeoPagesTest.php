<?php

use App\Models\MasterObject;
use App\Models\ObjectArticle;
use App\Models\ObjectContentRevision;
use App\Models\RankingEntry;
use App\Models\RankingPost;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Inertia\Ssr\Gateway;
use Inertia\Ssr\HasHealthCheck;
use Inertia\Testing\AssertableInertia as Assert;

test('home and filtered pages expose their indexing policy', function () {
    $this->get('/')->assertRedirect('/id')->assertStatus(301);

    $this->get('/id')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('seo.robots', 'index, follow')
        ->where('seo.canonical', app('url')->to('/id'))
        ->where('seo.alternates', [
            'id' => app('url')->to('/id'),
            'en' => app('url')->to('/en'),
            'x-default' => app('url')->to('/id'),
        ])
        ->etc());

    $this->get('/en/explore')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('seo.alternates.en', app('url')->to('/en/explore'))
        ->where('seo.alternates.x-default', app('url')->to('/id/explore'))
        ->etc());

    $this->get('/en/explore?q=camera')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('seo.robots', 'noindex, follow')
        ->where('seo.alternates', [])
        ->etc());

    $this->get('/id/login')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('seo.robots', 'noindex, follow')
        ->etc());
});

test('ranking and article URLs use the language of their published content', function () {
    $post = RankingPost::factory()->create(['language_code' => 'en']);
    $object = MasterObject::factory()->create();
    $article = ObjectArticle::factory()->create(['object_id' => $object->id]);
    $revision = ObjectContentRevision::factory()->create([
        'object_id' => $object->id,
        'article_id' => $article->id,
        'author_id' => $article->author_id,
        'title' => 'A useful English article',
        'body' => 'A published explanation with enough detail for readers.',
        'language_code' => 'en',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $article->update(['published_revision_id' => $revision->id]);

    $this->get('/id/ranking/'.$post->slug)->assertStatus(301)->assertRedirect('/en/ranking/'.$post->slug);
    $this->get('/id/article/'.$article->id)->assertStatus(301)->assertRedirect('/en/article/'.$article->id);
    $this->get('/en/article/'.$article->id)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('seo.robots', 'index, follow')
        ->where('seo.structuredData.0.@type', 'Article')
        ->etc());

    $post->update(['status' => 'hidden']);
    $this->get('/en/ranking/'.$post->slug)->assertNotFound();
});

test('ranking pages describe their ordered entries as an item list', function () {
    $post = RankingPost::factory()->create(['language_code' => 'en']);
    $verified = MasterObject::factory()->create(['canonical_name' => 'Leica Q3']);
    $pending = MasterObject::factory()->pending()->create(['canonical_name' => 'Unreviewed Camera']);
    RankingEntry::factory()->create(['ranking_post_id' => $post->id, 'object_id' => $pending->id, 'position' => 2]);
    RankingEntry::factory()->create(['ranking_post_id' => $post->id, 'object_id' => $verified->id, 'position' => 1]);

    $this->get('/en/ranking/'.$post->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('seo.structuredData.1.@type', 'ItemList')
        ->where('seo.structuredData.1.numberOfItems', 2)
        ->where('seo.structuredData.1.itemListElement', [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Leica Q3', 'url' => app('url')->to('/en/object/'.$verified->slug)],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Unreviewed Camera'],
        ])
        ->etc());
});

test('indexable information pages are server rendered while private pages are not', function () {
    config(['inertia.ssr.enabled' => true, 'inertia.ssr.ensure_bundle_exists' => false]);
    Http::fake(['*/render' => Http::response(['head' => [], 'body' => '<div id="app">Rendered on the server</div>'])]);

    $this->get('/en/privacy')->assertOk()->assertSee('Rendered on the server');
    $this->get('/en/hall-of-fame')->assertOk()->assertDontSee('Rendered on the server');

    Http::assertSentCount(1);
    Http::assertSent(fn (HttpRequest $request): bool => $request['component'] === 'Community/Privacy');
});

test('only verified objects with a published summary are indexable', function () {
    $object = MasterObject::factory()->create();

    $this->get('/id/object/'.$object->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('seo.robots', 'noindex, follow')
        ->etc());

    ObjectContentRevision::factory()->create([
        'object_id' => $object->id,
        'body' => 'An approved object summary that gives readers useful context.',
        'language_code' => 'en',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this->get('/id/object/'.$object->slug)->assertStatus(301)->assertRedirect('/en/object/'.$object->slug);
    $this->get('/en/object/'.$object->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('seo.robots', 'index, follow')
        ->etc());
});

test('sitemap lists only canonical published URLs and robots advertises it', function () {
    $published = RankingPost::factory()->create(['language_code' => 'en']);
    $hidden = RankingPost::factory()->create(['status' => 'hidden']);
    $object = MasterObject::factory()->create();
    $pendingObject = MasterObject::factory()->pending()->create();
    ObjectContentRevision::factory()->create([
        'object_id' => $object->id,
        'status' => 'published',
        'published_at' => now(),
    ]);
    $article = ObjectArticle::factory()->create(['object_id' => $object->id]);
    $revision = ObjectContentRevision::factory()->create([
        'object_id' => $object->id,
        'article_id' => $article->id,
        'author_id' => $article->author_id,
        'title' => 'An article in English',
        'language_code' => 'en',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $article->update(['published_revision_id' => $revision->id]);

    $this->get('/robots.txt')->assertOk()->assertSee('/sitemap.xml');
    $this->get('/sitemap.xml')->assertOk()->assertSee('/sitemaps/rankings/1.xml')->assertSee('/sitemaps/objects/1.xml')->assertSee('/sitemaps/articles/1.xml');
    $this->get('/sitemaps/static.xml')->assertOk()->assertSee('/id/explore')->assertSee('/en/explore');
    $this->get('/sitemaps/rankings/1.xml')->assertOk()->assertSee('/en/ranking/'.$published->slug)
        ->assertDontSee($hidden->slug);
    $this->get('/sitemaps/objects/1.xml')->assertOk()->assertSee('/id/object/'.$object->slug)
        ->assertDontSee($pendingObject->slug);
    $this->get('/sitemaps/articles/1.xml')->assertOk()->assertSee('/en/article/'.$article->id);
});

test('public HTML contains primary content and metadata when SSR is available', function () {
    $gateway = app(Gateway::class);

    if (! $gateway instanceof HasHealthCheck || ! $gateway->isHealthy()) {
        $this->markTestSkipped('Start the Inertia SSR server to check rendered HTML.');
    }

    $post = RankingPost::factory()->create(['title' => 'Best compact cameras for travel']);
    $object = MasterObject::factory()->create(['canonical_name' => 'Compact Camera']);
    ObjectContentRevision::factory()->create([
        'object_id' => $object->id,
        'body' => 'A compact camera suited to travel and everyday photography.',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $article = ObjectArticle::factory()->create(['object_id' => $object->id]);
    $revision = ObjectContentRevision::factory()->create([
        'object_id' => $object->id,
        'article_id' => $article->id,
        'author_id' => $article->author_id,
        'title' => 'Using a compact camera while traveling',
        'body' => 'A first-hand guide to using this camera on a trip.',
        'status' => 'published',
        'published_at' => now(),
    ]);
    $article->update(['published_revision_id' => $revision->id]);

    $this->get('/id/login')->assertOk()->assertSee('noindex, follow');
    $this->get('/id')->assertOk()->assertSee('<h1', false)->assertSee('rel="canonical"', false)
        ->assertSee('name="description"', false);
    $this->get('/id/ranking/'.$post->slug)->assertOk()->assertSee('<h1', false)->assertSee($post->title)
        ->assertSee('BreadcrumbList');
    $this->get('/id/object/'.$object->slug)->assertOk()->assertSee('<h1', false)->assertSee($object->canonical_name);
    $this->get('/id/article/'.$article->id)->assertOk()->assertSee('<h1', false)->assertSee($revision->title)
        ->assertSee('application/ld+json', false);
});
