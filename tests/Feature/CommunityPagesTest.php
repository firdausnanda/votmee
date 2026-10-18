<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\PointLedger;
use App\Models\RankingPost;
use App\Models\User;
use App\Models\Wallet;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

test('localized feed shows the same published content in Indonesian and English', function () {
    $post = RankingPost::factory()->create(['title' => 'Perbandingan dua pilihan', 'language_code' => 'id']);

    foreach (['id', 'en'] as $locale) {
        $this->get('/'.$locale)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Community/Feed')
            ->where('locale', $locale)
            ->where('feed.data.0.id', $post->id)
            ->etc());
    }
});

test('explore filters ranking titles and returns matching objects', function () {
    $category = Category::factory()->create();
    $object = MasterObject::factory()->create(['category_id' => $category->id, 'canonical_name' => 'Galaxy A26', 'normalized_name' => 'galaxy a26']);
    RankingPost::factory()->create(['category_id' => $category->id, 'title' => 'Galaxy A26 versus others']);
    RankingPost::factory()->create(['category_id' => $category->id, 'title' => 'Different topic']);

    $this->get('/id/explore?q=Galaxy')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Community/Explore')
        ->has('feed.data', 1)
        ->where('feed.data.0.title', 'Galaxy A26 versus others')
        ->where('objects.0.id', $object->id)
        ->etc());
});

test('wallet shows claim status and filters transactions across the full ledger', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/id/wallet')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Community/Wallet')
        ->where('claimedToday', false)
        ->where('flow', 'all')
        ->etc());

    $this->postJson(route('backend.wallet.claim'))->assertOk()
        ->assertJsonPath('claimed', true)
        ->assertJsonPath('balance', 5);
    $this->postJson(route('backend.wallet.claim'))->assertOk()
        ->assertJsonPath('claimed', false)
        ->assertJsonPath('balance', 5);

    $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
    PointLedger::factory()->create([
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
        'amount' => -2,
        'type' => 'support',
        'claimed_on' => null,
    ]);

    $this->get('/id/wallet?flow=incoming')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('claimedToday', true)
        ->where('flow', 'incoming')
        ->where('ledger.total', 1)
        ->where('ledger.data.0.amount', 5)
        ->etc());
    $this->get('/id/wallet?flow=outgoing')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('flow', 'outgoing')
        ->where('ledger.total', 1)
        ->where('ledger.data.0.amount', -2)
        ->etc());
    $this->get('/id/wallet?flow=invalid')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('flow', 'all')
        ->where('ledger.total', 2)
        ->etc());
});

test('published ranking detail loads candidates while hidden rankings are not accessible', function () {
    $post = RankingPost::factory()->create();
    $object = MasterObject::factory()->create(['category_id' => $post->category_id]);
    $post->entries()->create(['object_id' => $object->id, 'position' => 1]);

    $this->get('/en/ranking/'.$post->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Community/Ranking')
        ->where('post.id', $post->id)
        ->where('post.entries.0.object.id', $object->id)
        ->etc());

    $post->update(['status' => 'hidden']);
    $this->get('/en/ranking/'.$post->slug)->assertNotFound();
});

test('wallet and moderation pages enforce authentication and roles', function () {
    $this->get('/id/wallet')->assertRedirect('/login');
    $this->get('/admin')->assertRedirect('/login');
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

test('community menu permissions match guest, member, and administrator access', function () {
    $this->get('/id')->assertInertia(fn (Assert $page) => $page
        ->where('auth.can.createRanking', false)
        ->where('auth.can.viewWallet', false)
        ->where('auth.can.moderate', false)
        ->etc());

    $member = User::factory()->create();
    $this->actingAs($member)->get('/id')->assertInertia(fn (Assert $page) => $page
        ->where('auth.can.createRanking', true)
        ->where('auth.can.viewWallet', true)
        ->where('auth.can.moderate', false)
        ->etc());

    $member->update(['role' => 'moderator']);
    $this->actingAs($member)->get('/id')->assertInertia(fn (Assert $page) => $page
        ->where('auth.can.moderate', true)
        ->etc());

    $member->update(['role' => 'user']);
    $member->assignRole(Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']));
    $this->actingAs($member)->get('/id')->assertInertia(fn (Assert $page) => $page
        ->where('auth.can.moderate', true)
        ->etc());
});

test('merged object URL redirects to its current master', function () {
    $target = MasterObject::factory()->create();
    $source = MasterObject::factory()->create([
        'category_id' => $target->category_id,
        'status' => 'merged',
        'merged_into_id' => $target->id,
    ]);

    $this->get('/id/object/'.$source->slug)->assertRedirect('/id/object/'.$target->slug);
});

test('moderator can open the review queue', function () {
    $moderator = User::factory()->create();
    $moderator->role = 'moderator';
    $moderator->save();

    $this->actingAs($moderator)->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Community/Admin')
        ->where('leaders', [])
        ->where('isAdmin', false)
        ->etc());
});

test('user can like and unlike a comment, and reply to it', function () {
    $user = User::factory()->create();
    $post = RankingPost::factory()->create();
    $comment = Comment::factory()->create([
        'ranking_post_id' => $post->id,
        'user_id' => $user->id,
        'body' => 'Komentar pengujian untuk like dan balas',
        'status' => 'visible',
    ]);

    // Like comment
    $response = $this->actingAs($user)->postJson('/backend/comments/'.$comment->id.'/like');
    $response->assertOk()
        ->assertJson([
            'liked' => true,
            'likes_count' => 1,
        ]);

    // Unlike comment
    $response2 = $this->actingAs($user)->postJson('/backend/comments/'.$comment->id.'/like');
    $response2->assertOk()
        ->assertJson([
            'liked' => false,
            'likes_count' => 0,
        ]);

    // Reply to comment
    $replyResponse = $this->actingAs($user)->postJson('/backend/rankings/'.$post->slug.'/comments', [
        'body' => 'Ini adalah balasan komentar',
        'parent_id' => $comment->id,
    ]);
    $replyResponse->assertCreated()
        ->assertJson([
            'parent_id' => $comment->id,
            'body' => 'Ini adalah balasan komentar',
        ]);

    // Ranking page loads comments with parent_id and likes count
    $this->get('/id/ranking/'.$post->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Community/Ranking')
        ->has('comments', 2)
        ->where('comments.0.parent_id', $comment->id)
        ->etc());
});
