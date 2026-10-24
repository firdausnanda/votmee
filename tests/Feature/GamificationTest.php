<?php

use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\MissionCompletion;
use App\Models\ObjectArticle;
use App\Models\PointLedger;
use App\Models\SupportEvent;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GamificationService;
use App\Services\MissionService;
use App\Services\SupportService;
use Inertia\Testing\AssertableInertia as Assert;

function balanceOf(User $user): int
{
    return (int) Wallet::query()->where('user_id', $user->id)->value('balance');
}

test('daily claims build a streak that pays its bonus once per cycle and resets after a missed day', function () {
    $user = User::factory()->create();
    $support = app(SupportService::class);
    $this->travelTo(now('Asia/Jakarta')->setTime(9, 0));

    foreach (range(1, 7) as $day) {
        $support->claimDaily($user);
        $support->claimDaily($user);
        $this->travel(1)->day();
    }

    expect(balanceOf($user))->toBe(7 * 5 + 10 + 5)
        ->and(PointLedger::query()->where('type', 'streak_bonus')->count())->toBe(1);

    $this->travel(1)->day();
    $support->claimDaily($user);
    $streak = app(GamificationService::class)->streak($user);

    expect($streak['days'])->toBe(1)
        ->and($streak['longest'])->toBe(7)
        ->and($streak['cycle_progress'])->toBe(1);
});

test('a streak reads as zero once a day is missed', function () {
    $user = User::factory()->create();
    app(SupportService::class)->claimDaily($user);
    $this->travel(2)->days();

    expect(app(GamificationService::class)->streak($user)['days'])->toBe(0);
});

test('missions wait for a claim, complete once per day, and reserve points under the daily cap', function () {
    config([
        'gamification.missions.daily.always' => ['write_comments', 'article_comment', 'mention_friend'],
        'gamification.missions.daily.slots' => 3,
        'gamification.missions.daily.cap' => 5,
    ]);
    $this->travelTo(now('Asia/Jakarta')->setTime(9, 0));
    $user = User::factory()->create();
    $missions = app(MissionService::class);

    Comment::factory()->create(['user_id' => $user->id]);
    expect($missions->record($user, 'write_comments'))->toBeNull();

    Comment::factory()->create(['user_id' => $user->id]);
    expect($missions->record($user, 'write_comments')->amount)->toBe(4)
        ->and($missions->record($user, 'write_comments'))->toBeNull();

    $articleComment = Comment::factory()->create(['user_id' => $user->id, 'ranking_post_id' => null, 'article_id' => ObjectArticle::factory()]);
    CommentMention::factory()->create(['comment_id' => $articleComment->id]);

    expect($missions->record($user, 'article_comment')->amount)->toBe(1)
        ->and($missions->record($user, 'mention_friend'))->toBeNull()
        ->and(balanceOf($user))->toBe(0)
        ->and(PointLedger::query()->where('type', 'mission_reward')->count())->toBe(0);

    $firstCompletion = MissionCompletion::query()->where('user_id', $user->id)->where('mission', 'write_comments')->sole();
    $this->actingAs($user)->postJson(route('backend.wallet.missions.claim', $firstCompletion->id))
        ->assertOk()->assertJsonPath('claimed', true)->assertJsonPath('amount', 4)->assertJsonPath('balance', 4);
    $this->postJson(route('backend.wallet.missions.claim', $firstCompletion->id))
        ->assertOk()->assertJsonPath('claimed', false)->assertJsonPath('balance', 4);

    expect(PointLedger::query()->where('type', 'mission_reward')->count())->toBe(1);

    $this->travel(1)->day();
    Comment::factory()->count(2)->create(['user_id' => $user->id]);

    expect($missions->record($user, 'write_comments')->amount)->toBe(4)
        ->and(balanceOf($user))->toBe(4)
        ->and($missions->overview($user)['pending'])->toHaveCount(1);

    $olderCompletion = MissionCompletion::query()->where('user_id', $user->id)->where('mission', 'article_comment')->sole();
    $this->postJson(route('backend.wallet.missions.claim', $olderCompletion->id))
        ->assertOk()->assertJsonPath('amount', 1)->assertJsonPath('balance', 5);
});

test('members cannot claim another member’s mission or an unfinished mission', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $completion = MissionCompletion::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)->postJson(route('backend.wallet.missions.claim', $completion->id))->assertNotFound();
    $this->actingAs($owner)->postJson(route('backend.wallet.missions.claim', 999999))->assertNotFound();

    expect(balanceOf($owner))->toBe(0)
        ->and(balanceOf($other))->toBe(0);
});

test('mission rewards paid before the claim change remain claimed and cannot pay again', function () {
    $user = User::factory()->create();
    $today = app(GamificationService::class)->today();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 3]);
    PointLedger::factory()->create([
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
        'type' => 'mission_reward',
        'amount' => 3,
        'idempotency_key' => "mission:{$user->id}:quick_vote:{$today}",
        'claimed_on' => null,
    ]);
    SupportEvent::factory()->create(['user_id' => $user->id, 'ledger_id' => null, 'source' => 'quick_vote', 'vote_date' => $today, 'amount' => 1]);

    $missions = app(MissionService::class);

    expect($missions->record($user, 'quick_vote'))->toBeNull()
        ->and($missions->overview($user)['daily'][0])->toMatchArray(['key' => 'quick_vote', 'earned' => 3, 'claimed' => true])
        ->and(MissionCompletion::query()->count())->toBe(0)
        ->and(balanceOf($user))->toBe(3);
});

test('ineligible accounts earn no mission points', function () {
    $missions = app(MissionService::class);

    foreach ([User::factory()->unverified()->create(), User::factory()->restricted()->create()] as $user) {
        SupportEvent::factory()->create(['user_id' => $user->id, 'ledger_id' => null, 'source' => 'quick_vote', 'vote_date' => now('Asia/Jakarta')->toDateString(), 'amount' => 1]);

        expect($missions->record($user, 'quick_vote'))->toBeNull();
    }

    expect(MissionCompletion::query()->count())->toBe(0);
});

test('a like from another member advances the author’s comment challenge and the liker’s mission only', function () {
    config([
        'gamification.missions.daily.always' => ['like_comments'],
        'gamification.missions.daily.slots' => 1,
        'gamification.missions.daily.pool.like_comments.target' => 1,
        'gamification.missions.challenges.pool.liked_comment.target' => 1,
        'gamification.missions.challenges.min_account_age_days' => 0,
    ]);
    $author = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $author->id]);

    $this->actingAs($author)->postJson(route('backend.comments.like', $comment))->assertOk();
    expect(MissionCompletion::query()->count())->toBe(0);

    $liker = User::factory()->create();
    $this->actingAs($liker)->postJson(route('backend.comments.like', $comment))->assertOk();
    $this->actingAs($liker)->postJson(route('backend.comments.like', $comment))->assertOk();
    $this->actingAs($liker)->postJson(route('backend.comments.like', $comment))->assertOk();

    expect(balanceOf($author))->toBe(0)
        ->and(MissionCompletion::query()->where('user_id', $author->id)->pluck('mission')->all())->toBe(['liked_comment'])
        ->and(MissionCompletion::query()->where('user_id', $liker->id)->pluck('mission')->all())->toBe(['like_comments', 'perfect_day'])
        ->and(PointLedger::query()->where('user_id', $liker->id)->where('type', 'mission_reward')->exists())->toBeFalse();
});

test('the wallet page shows the streak, today missions, weekly missions and challenges', function () {
    config(['gamification.missions.daily.always' => ['quick_vote', 'write_comments'], 'gamification.missions.daily.slots' => 2]);
    $user = User::factory()->create();
    app(SupportService::class)->claimDaily($user);
    Comment::factory()->count(2)->create(['user_id' => $user->id]);
    app(MissionService::class)->record($user, 'write_comments');

    $this->actingAs($user)->get('/id/wallet')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('streak.days', 1)
        ->where('streak.cycle_progress', 1)
        ->where('streak.claimed_today', true)
        ->where('missions.daily.0.key', 'quick_vote')
        ->where('missions.daily.0.earned', null)
        ->where('missions.daily.1.key', 'write_comments')
        ->where('missions.daily.1.progress', 2)
        ->where('missions.daily.1.earned', 4)
        ->where('missions.daily.1.claimed', false)
        ->where('missions.perfect_day.progress', 1)
        ->has('missions.weekly', 5)
        ->has('missions.challenges', 4)
        ->where('summary', ['earned' => 5, 'spent' => 0, 'earned_this_month' => 5]));

    $this->postJson(route('backend.wallet.claim'))->assertOk()
        ->assertJsonPath('claimed', false)
        ->assertJsonPath('streak_bonus', 0)
        ->assertJsonPath('streak.days', 1);
});
