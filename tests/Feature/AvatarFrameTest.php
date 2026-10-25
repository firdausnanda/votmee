<?php

use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\RankingPost;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\AvatarFrameService;
use App\Services\MessagingService;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

/** @param list<string> $keys */
function grantBadges(User $user, array $keys): void
{
    foreach ($keys as $key) {
        UserAchievement::factory()->create(['user_id' => $user->id, 'achievement_key' => $key]);
    }
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00', 'Asia/Jakarta'));
});

test('members show the rarest frame they hold, and the champion frame lasts one month', function () {
    $frames = app(AvatarFrameService::class);
    [$plain, $gold, $legend, $veteran, $champion] = User::factory()->count(5)->create()->all();
    grantBadges($gold, ['streak_gold', 'curator_silver']);
    grantBadges($legend, ['streak_gold', 'curator_gold', 'writer_gold']);
    grantBadges($veteran, ['champion_2026_05_curator', 'champion_2026_06_curator', 'champion_2026_08_voice', 'streak_gold']);
    grantBadges($champion, ['champion_2026_09_ranker', 'champion_2026_09_voice']);

    expect($frames->framesFor([$plain->id, $gold->id, $legend->id, $veteran->id, $champion->id]))->toBe([
        $plain->id => null,
        $gold->id => 'gold',
        $legend->id => 'legend',
        $veteran->id => 'veteran-3',
        $champion->id => 'champion-voice',
    ]);

    $this->travelTo(CarbonImmutable::parse('2026-11-02 12:00', 'Asia/Jakarta'));

    expect($frames->frameFor($champion))->toBeNull();
});

test('members pick a frame they hold, hide it, or return to the rarest one', function () {
    $user = User::factory()->create();
    grantBadges($user, ['streak_gold', 'curator_gold', 'writer_gold']);

    $this->actingAs($user)->putJson(route('backend.achievements.frame'), ['frame' => 'gold'])
        ->assertOk()->assertJson(['preferred' => 'gold', 'frame' => 'gold']);
    $this->actingAs($user)->putJson(route('backend.achievements.frame'), ['frame' => 'veteran'])
        ->assertUnprocessable()->assertJsonValidationErrors('frame');
    $this->actingAs($user)->putJson(route('backend.achievements.frame'), ['frame' => 'sparkly'])
        ->assertUnprocessable()->assertJsonValidationErrors('frame');
    $this->actingAs($user)->putJson(route('backend.achievements.frame'), ['frame' => 'none'])
        ->assertOk()->assertJson(['preferred' => 'none', 'frame' => null]);
    $this->actingAs($user)->putJson(route('backend.achievements.frame'), ['frame' => null])
        ->assertOk()->assertJson(['preferred' => null, 'frame' => 'legend']);

    $this->putJson(route('backend.achievements.frame'), ['frame' => 'gold']);
    UserAchievement::query()->where('user_id', $user->id)->delete();

    expect(app(AvatarFrameService::class)->frameFor($user->refresh()))->toBeNull();
});

test('frames and name flair reach comments, the hall of fame and the profile, with the picker only for its owner', function () {
    $author = User::factory()->create();
    grantBadges($author, ['champion_2026_09_curator']);
    $post = RankingPost::factory()->create();
    Comment::factory()->create(['user_id' => $author->id, 'ranking_post_id' => $post->id]);
    MasterObject::factory()->create(['submitted_by' => $author->id]);
    $author->forceFill(['preferred_avatar_frame' => 'champion', 'avatar_path' => 'avatars/author.webp'])->save();

    $this->getJson(route('backend.comments.index', $post))->assertOk()
        ->assertJsonPath('comments.0.avatar_url', $author->refresh()->avatar_url)
        ->assertJsonPath('comments.0.avatar_frame', 'champion-curator')
        ->assertJsonPath('comments.0.name_flair', 'champion-curator');

    $this->get('/id/hall-of-fame?month=2026-10')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('board.curator.0.user.avatar_frame', 'champion-curator')
        ->where('board.curator.0.user.name_flair', 'champion-curator')
        ->where('recentComments.0.user.avatar_frame', 'champion-curator')
        ->where('recentComments.0.user.name_flair', 'champion-curator'));

    $this->get("/id/profile/{$author->username}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('profile.avatar_frame', 'champion-curator')
        ->where('profile.name_flair', 'champion-curator')
        ->where('avatarFrameOptions', null));

    $this->actingAs($author)->get("/id/profile/{$author->username}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('avatarFrameOptions.preferred', 'champion')
        ->where('avatarFrameOptions.held', ['champion'])
        ->where('avatarFrameOptions.board', 'curator'));
});

test('name flair crowns the reigning champion even with the frame hidden, then marks trusted contributors', function () {
    $frames = app(AvatarFrameService::class);
    [$champion, $trusted, $restricted, $plain] = User::factory()->count(4)->create()->all();
    grantBadges($champion, ['champion_2026_09_writer']);
    $champion->forceFill(['preferred_avatar_frame' => 'none', 'trusted_at' => now()])->save();
    $trusted->forceFill(['trusted_at' => now()])->save();
    $restricted->forceFill(['trusted_at' => now(), 'restricted_until' => now()->addWeek()])->save();

    expect($frames->flairFor([$champion->id, $trusted->id, $restricted->id, $plain->id]))->toBe([
        $champion->id => ['avatar_frame' => null, 'name_flair' => 'champion-writer'],
        $trusted->id => ['avatar_frame' => null, 'name_flair' => 'trusted'],
        $restricted->id => ['avatar_frame' => null, 'name_flair' => null],
        $plain->id => ['avatar_frame' => null, 'name_flair' => null],
    ]);

    $this->travelTo(CarbonImmutable::parse('2026-11-02 12:00', 'Asia/Jakarta'));

    expect($frames->flairForUser($champion->refresh())['name_flair'])->toBe('trusted');
});

test('frames follow members onto feeds, ranking pages, search, messages, notifications and the header', function () {
    $champion = User::factory()->create();
    grantBadges($champion, ['champion_2026_09_voice']);
    $viewer = User::factory()->create();
    $post = RankingPost::factory()->create(['creator_id' => $champion->id]);
    app(MessagingService::class)->start($champion, $viewer, 'Halo, salam kenal!');

    $this->getJson(route('backend.rankings.index'))->assertOk()
        ->assertJsonPath('data.0.creator.avatar_frame', 'champion-voice');
    $this->get("/id/ranking/{$post->slug}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('post.creator.avatar_frame', 'champion-voice'));
    $this->getJson('/backend/search?q=@'.$champion->username)->assertOk()
        ->assertJsonPath('users.0.avatar_frame', 'champion-voice');

    $this->actingAs($viewer)->getJson(route('backend.messages.index', ['box' => 'requests']))->assertOk()
        ->assertJsonPath('conversations.data.0.other.avatar_frame', 'champion-voice');
    $this->actingAs($viewer)->getJson(route('backend.notifications.index'))->assertOk()
        ->assertJsonPath('notifications.0.data.actor.avatar_frame', 'champion-voice');
    $this->actingAs($champion)->get('/id/hall-of-fame')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('auth.user.avatar_frame', 'champion-voice'));
});
