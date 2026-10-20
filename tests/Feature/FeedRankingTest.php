<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\PointLedger;
use App\Models\RankingEntry;
use App\Models\RankingPost;
use App\Models\SupportEvent;
use App\Models\User;
use App\Models\Wallet;
use App\Services\LeaderboardService;
use Inertia\Testing\AssertableInertia as Assert;

function supportPost(RankingPost $post, ?User $supporter = null, int $amount = 5, ?DateTimeInterface $occurredAt = null): void
{
    $supporter ??= User::factory()->create();
    $wallet = Wallet::query()->firstOrCreate(['user_id' => $supporter->id], ['balance' => 0]);
    $entry = RankingEntry::query()->where('ranking_post_id', $post->id)->first()
        ?? RankingEntry::factory()->create(['ranking_post_id' => $post->id]);

    SupportEvent::factory()->create([
        'ranking_entry_id' => $entry->id,
        'ledger_id' => PointLedger::factory()->create(['wallet_id' => $wallet->id])->id,
        'amount' => $amount,
        'occurred_at' => $occurredAt ?? now('UTC'),
    ]);
}

/** @return list<string> */
function feedIds(Assert $page): array
{
    return collect($page->toArray()['props']['feed']['data'])->pluck('id')->all();
}

test('popular ranks by unique supporters inside the period instead of all-time points', function () {
    $evergreen = RankingPost::factory()->create(['published_at' => now('UTC')->subDays(40)]);
    $trending = RankingPost::factory()->create(['published_at' => now('UTC')->subDays(2)]);
    foreach (range(1, 3) as $supporter) {
        supportPost($evergreen, amount: 50, occurredAt: now('UTC')->subDays(10));
    }
    supportPost($trending, amount: 1);
    supportPost($trending, amount: 1);

    $this->get('/id?sort=popular')->assertOk()->assertInertia(fn (Assert $page) => expect(feedIds($page))
        ->toBe([$trending->id, $evergreen->id]));

    $this->get('/id?sort=popular&period=all')->assertOk()->assertInertia(fn (Assert $page) => expect(feedIds($page))
        ->toBe([$evergreen->id, $trending->id]));
});

test('popular prefers more supporters over one large supporter', function () {
    $whale = RankingPost::factory()->create();
    $crowd = RankingPost::factory()->create();
    supportPost($whale, amount: 100);
    supportPost($crowd, amount: 1);
    supportPost($crowd, amount: 1);

    $this->get('/id?sort=popular&period=today')->assertOk()->assertInertia(fn (Assert $page) => expect(feedIds($page))
        ->toBe([$crowd->id, $whale->id]));
});

test('popular rejects an unknown period', function () {
    $this->get('/id?sort=popular&period=decade')->assertSessionHasErrors('period');
});

test('for you favours the viewer categories and moves engaged posts to the end', function () {
    $viewer = User::factory()->create();
    $favourite = Category::factory()->create();
    $supported = RankingPost::factory()->create(['category_id' => $favourite->id, 'published_at' => now('UTC')->subHour()]);
    supportPost($supported, $viewer);
    $own = RankingPost::factory()->create(['creator_id' => $viewer->id, 'published_at' => now('UTC')->subHour()]);
    $matching = RankingPost::factory()->create(['category_id' => $favourite->id, 'published_at' => now('UTC')->subDays(3)]);
    $other = RankingPost::factory()->create(['published_at' => now('UTC')->subDays(2)]);

    $this->actingAs($viewer)->get('/id')->assertOk()->assertInertia(function (Assert $page) use ($matching, $other, $supported, $own) {
        $ids = feedIds($page);

        expect(array_slice($ids, 0, 2))->toBe([$matching->id, $other->id])
            ->and(array_slice($ids, 2))->toEqualCanonicalizing([$supported->id, $own->id]);
    });
});

test('for you puts fresh posts in the viewer language ahead of older ones', function () {
    $foreign = RankingPost::factory()->create(['language_code' => 'en', 'published_at' => now('UTC')->subHour()]);
    $stale = RankingPost::factory()->create(['published_at' => now('UTC')->subDays(60)]);
    $fresh = RankingPost::factory()->create(['published_at' => now('UTC')->subHours(2)]);

    $this->get('/id')->assertOk()->assertInertia(fn (Assert $page) => expect(feedIds($page))
        ->toBe([$fresh->id, $foreign->id, $stale->id]));
});

test('feed cards expose the creator avatar url without leaking the stored path', function () {
    $creator = User::factory()->create(['avatar_path' => 'profiles/avatars/1/photo.jpg']);
    $post = RankingPost::factory()->create(['creator_id' => $creator->id]);

    $this->get('/id')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('feed.data.0.id', $post->id)
        ->where('feed.data.0.creator.avatar_url', $creator->refresh()->avatar_url)
        ->missing('feed.data.0.creator.avatar_path')
        ->etc());
});

test('feed cards carry the leading candidates with their approved images for the cover', function () {
    $post = RankingPost::factory()->create();
    $leader = RankingEntry::factory()->create(['ranking_post_id' => $post->id, 'position' => 1]);
    $runnerUp = RankingEntry::factory()->create(['ranking_post_id' => $post->id, 'position' => 2]);
    $third = RankingEntry::factory()->create(['ranking_post_id' => $post->id, 'position' => 3]);
    $leader->object->update(['image_path' => 'objects/leader.jpg']);
    $runnerUp->object->update(['image_path' => 'objects/runner-up.jpg']);
    supportPost($post, amount: 9);
    SupportEvent::factory()->create(['ranking_entry_id' => $third->id, 'amount' => 50]);

    $this->get('/id')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('feed.data.0.entries', 2)
        ->where('feed.data.0.entries.0.id', $third->id)
        ->where('feed.data.0.entries.0.object.image_path', null)
        ->where('feed.data.0.entries.0.object.status', 'verified')
        ->where('feed.data.0.entries.1.object.image_path', fn ($path) => in_array($path, ['objects/leader.jpg', 'objects/runner-up.jpg'], true))
        ->etc());
});

test('leaderboards carry the object image and the contributor avatar', function () {
    $creator = User::factory()->create(['avatar_path' => 'profiles/avatars/1/photo.jpg']);
    $post = RankingPost::factory()->create(['creator_id' => $creator->id]);
    $entry = RankingEntry::factory()->create(['ranking_post_id' => $post->id]);
    $entry->object->update(['image_path' => 'objects/photo.jpg']);
    supportPost($post, amount: 5);

    $objects = $this->getJson(route('backend.leaderboard', ['board' => 'objects', 'period' => 'all_time', 'rank_type' => 'support']))
        ->assertOk()->json('leaders.0');
    $creators = $this->getJson(route('backend.leaderboard', ['board' => 'creators', 'period' => 'all_time']))
        ->assertOk()->json('leaders.0');

    expect($objects['id'])->toBe($entry->object_id)
        ->and($objects['image_path'])->toBe('objects/photo.jpg')
        ->and($creators['avatar_url'])->toBe($creator->refresh()->avatar_url);
});

test('recent community comments carry the commenter avatar without the stored path', function () {
    $commenter = User::factory()->create(['avatar_path' => 'profiles/avatars/9/photo.jpg']);
    Comment::factory()->create(['user_id' => $commenter->id]);

    $this->get('/id')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('recentComments.0.user.avatar_url', $commenter->refresh()->avatar_url)
        ->missing('recentComments.0.user.avatar_path')
        ->etc());
});

test('explore spotlights the busiest ranking of the week and lists the rest by recency', function () {
    $old = RankingPost::factory()->create(['published_at' => now('UTC')->subDays(30)]);
    $recent = RankingPost::factory()->create(['published_at' => now('UTC')->subHour()]);
    $busy = RankingPost::factory()->create(['published_at' => now('UTC')->subDays(5)]);
    supportPost($busy, amount: 5);
    supportPost($busy, amount: 5);

    $this->get('/id/explore')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Community/Explore')
        ->where('spotlight.id', $busy->id)
        ->has('categories.0.ranking_posts_count')
        ->where('feed.data.0.id', $recent->id)
        ->where('feed.data.2.id', $old->id)
        ->etc());

    $this->get('/id/explore?q=zzzz')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('spotlight', null)
        ->where('rising', [])
        ->etc());
});

test('rising objects list only positive weekly gains, largest first', function () {
    $post = RankingPost::factory()->create();
    $gaining = RankingEntry::factory()->create(['ranking_post_id' => $post->id, 'position' => 1]);
    $steady = RankingEntry::factory()->create(['ranking_post_id' => $post->id, 'position' => 2]);
    SupportEvent::factory()->create(['ranking_entry_id' => $gaining->id, 'amount' => 20, 'occurred_at' => now('UTC')->subDay()]);
    SupportEvent::factory()->create(['ranking_entry_id' => $gaining->id, 'amount' => 3, 'occurred_at' => now('UTC')->subDays(10)]);
    SupportEvent::factory()->create(['ranking_entry_id' => $steady->id, 'amount' => 10, 'occurred_at' => now('UTC')->subDay()]);
    SupportEvent::factory()->create(['ranking_entry_id' => $steady->id, 'amount' => 30, 'occurred_at' => now('UTC')->subDays(9)]);
    SupportEvent::factory()->create(['ranking_entry_id' => $steady->id, 'amount' => 50, 'occurred_at' => now('UTC')->subDays(20)]);

    $rising = app(LeaderboardService::class)->rising();

    expect($rising)->toHaveCount(1)
        ->and($rising[0]['object_id'])->toBe($gaining->object_id)
        ->and($rising[0]['delta'])->toBe(17);
});

test('explore filters by category slug and still accepts the legacy category id', function () {
    $tech = Category::factory()->create(['slug' => 'teknologi-baru']);
    $food = Category::factory()->create(['slug' => 'kuliner-baru']);
    $techPost = RankingPost::factory()->create(['category_id' => $tech->id]);
    RankingPost::factory()->create(['category_id' => $food->id]);

    $this->get('/id/explore?category=teknologi-baru')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('filters.category_id', $tech->id)
        ->has('feed.data', 1)
        ->where('feed.data.0.id', $techPost->id)
        ->etc());

    $this->get('/id/explore?category_id='.$tech->id)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('feed.data', 1)
        ->etc());

    $this->get('/id/explore?category=tidak-ada')->assertSessionHasErrors('category');
});
