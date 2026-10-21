<?php

use App\Models\RankingPost;
use App\Models\SavedRanking;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('users can save and unsave a published ranking', function () {
    $user = User::factory()->create();
    $post = RankingPost::factory()->create();

    $this->actingAs($user)->putJson("/backend/rankings/{$post->id}/save")->assertOk()->assertJson(['saved' => true]);
    $this->actingAs($user)->putJson("/backend/rankings/{$post->id}/save")->assertOk();

    expect(SavedRanking::query()->where('user_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)->deleteJson("/backend/rankings/{$post->id}/save")->assertOk()->assertJson(['saved' => false]);

    expect(SavedRanking::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

test('saving requires authentication and a published ranking', function () {
    $post = RankingPost::factory()->create();
    $draft = RankingPost::factory()->create(['status' => 'draft']);

    $this->putJson("/backend/rankings/{$post->id}/save")->assertUnauthorized();
    $this->actingAs(User::factory()->create())->putJson("/backend/rankings/{$draft->id}/save")->assertNotFound();
});

test('sync imports locally saved rankings once and skips unknown or unpublished ids', function () {
    $user = User::factory()->create();
    $posts = RankingPost::factory()->count(2)->create();
    $draft = RankingPost::factory()->create(['status' => 'draft']);
    SavedRanking::factory()->create(['user_id' => $user->id, 'ranking_post_id' => $posts[0]->id]);

    $this->actingAs($user)->postJson('/backend/saved-rankings/sync', [
        'ranking_post_ids' => [$posts[0]->id, strtoupper($posts[1]->id), $draft->id, 'missing-id'],
    ])->assertOk()->assertJson(['imported' => 1, 'saved_count' => 2]);

    $this->actingAs($user)->postJson('/backend/saved-rankings/sync', [
        'ranking_post_ids' => range(1, 201),
    ])->assertUnprocessable()->assertJsonValidationErrors('ranking_post_ids');
});

test('saved page lists only the viewer\'s saved rankings, newest save first', function () {
    $user = User::factory()->create();
    [$first, $second, $other] = RankingPost::factory()->count(3)->create();
    SavedRanking::factory()->create(['user_id' => $user->id, 'ranking_post_id' => $first->id, 'created_at' => now()->subDay()]);
    SavedRanking::factory()->create(['user_id' => $user->id, 'ranking_post_id' => $second->id, 'created_at' => now()]);
    SavedRanking::factory()->create(['ranking_post_id' => $other->id]);

    $this->actingAs($user)->get('/id/saved')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Community/Saved')
        ->where('savedCount', 2)
        ->has('feed.data', 2)
        ->where('feed.data.0.id', $second->id)
        ->where('feed.data.0.is_saved', true)
        ->where('feed.data.1.id', $first->id)
        ->etc());
});

test('saved page requires login and feeds mark saved rankings for the viewer', function () {
    $user = User::factory()->create();
    $post = RankingPost::factory()->create();
    SavedRanking::factory()->create(['user_id' => $user->id, 'ranking_post_id' => $post->id]);

    $this->get('/id/saved')->assertRedirect();

    $this->actingAs($user)->get('/id')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('feed.data.0.id', $post->id)
        ->where('feed.data.0.is_saved', true)
        ->etc());

    $this->actingAs($user)->get("/id/ranking/{$post->slug}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('isSaved', true)
        ->etc());
});
