<?php

use App\Models\Comment;
use App\Models\MissionCompletion;
use App\Models\PointLedger;
use App\Models\User;
use App\Models\Wallet;
use App\Services\GamificationService;
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
    config(['gamification.daily_mission_cap' => 5]);
    $user = User::factory()->create();
    $gamification = app(GamificationService::class);

    expect($gamification->completeMission($user, 'quick_vote')->amount)->toBe(3)
        ->and($gamification->completeMission($user, 'quick_vote'))->toBeNull()
        ->and($gamification->completeMission($user, 'support')->amount)->toBe(2)
        ->and($gamification->completeMission($user, 'comment_liked'))->toBeNull()
        ->and(balanceOf($user))->toBe(0)
        ->and(PointLedger::query()->where('type', 'mission_reward')->count())->toBe(0);

    $firstCompletion = MissionCompletion::query()->where('user_id', $user->id)->where('mission', 'quick_vote')->sole();
    $this->actingAs($user)->postJson(route('backend.wallet.missions.claim', $firstCompletion->id))
        ->assertOk()->assertJsonPath('claimed', true)->assertJsonPath('amount', 3)->assertJsonPath('balance', 3);
    $this->postJson(route('backend.wallet.missions.claim', $firstCompletion->id))
        ->assertOk()->assertJsonPath('claimed', false)->assertJsonPath('balance', 3);

    expect(PointLedger::query()->where('type', 'mission_reward')->count())->toBe(1);

    $this->travel(1)->day();

    expect($gamification->completeMission($user, 'quick_vote')->amount)->toBe(3)
        ->and(balanceOf($user))->toBe(3)
        ->and($gamification->pendingMissions($user))->toHaveCount(1);

    $olderCompletion = MissionCompletion::query()->where('user_id', $user->id)->where('mission', 'support')->sole();
    $this->postJson(route('backend.wallet.missions.claim', $olderCompletion->id))
        ->assertOk()->assertJsonPath('amount', 2)->assertJsonPath('balance', 5);
});

test('members cannot claim another member’s mission or an unfinished mission', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $completion = app(GamificationService::class)->completeMission($owner, 'quick_vote');

    $this->actingAs($other)->postJson(route('backend.wallet.missions.claim', $completion->id))->assertNotFound();
    $this->actingAs($owner)->postJson(route('backend.wallet.missions.claim', 999999))->assertNotFound();

    expect(balanceOf($owner))->toBe(0)
        ->and(balanceOf($other))->toBe(0);
});

test('mission rewards paid before the claim change remain claimed and cannot pay again', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 3]);
    PointLedger::factory()->create([
        'wallet_id' => $wallet->id,
        'user_id' => $user->id,
        'type' => 'mission_reward',
        'amount' => 3,
        'idempotency_key' => "mission:{$user->id}:quick_vote:".app(GamificationService::class)->today(),
        'claimed_on' => null,
    ]);

    $gamification = app(GamificationService::class);

    expect($gamification->completeMission($user, 'quick_vote'))->toBeNull()
        ->and($gamification->missions($user)[0]['claimed'])->toBeTrue()
        ->and(MissionCompletion::query()->count())->toBe(0)
        ->and(balanceOf($user))->toBe(3);
});

test('ineligible accounts earn no mission points', function () {
    $gamification = app(GamificationService::class);

    expect($gamification->completeMission(User::factory()->unverified()->create(), 'support'))->toBeNull()
        ->and($gamification->completeMission(User::factory()->restricted()->create(), 'support'))->toBeNull()
        ->and(PointLedger::query()->count())->toBe(0);
});

test('a like from another member completes the comment mission for the author only', function () {
    $author = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $author->id]);

    $this->actingAs($author)->postJson(route('backend.comments.like', $comment))->assertOk();
    expect(PointLedger::query()->count())->toBe(0);

    $liker = User::factory()->create();
    $this->actingAs($liker)->postJson(route('backend.comments.like', $comment))->assertOk();
    $this->actingAs($liker)->postJson(route('backend.comments.like', $comment))->assertOk();
    $this->actingAs($liker)->postJson(route('backend.comments.like', $comment))->assertOk();

    expect(balanceOf($author))->toBe(0)
        ->and(MissionCompletion::query()->where('user_id', $author->id)->where('mission', 'comment_liked')->count())->toBe(1)
        ->and(PointLedger::query()->where('user_id', $liker->id)->exists())->toBeFalse();
});

test('the wallet page shows the streak and today missions', function () {
    $user = User::factory()->create();
    app(SupportService::class)->claimDaily($user);
    app(GamificationService::class)->completeMission($user, 'support');

    $this->actingAs($user)->get('/id/wallet')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('streak.days', 1)
        ->where('streak.cycle_progress', 1)
        ->where('streak.claimed_today', true)
        ->where('missions.0.key', 'quick_vote')
        ->where('missions.0.earned', null)
        ->where('missions.1.key', 'support')
        ->where('missions.1.earned', 3)
        ->where('missions.1.claimed', false)
        ->where('summary', ['earned' => 5, 'spent' => 0, 'earned_this_month' => 5]));

    $this->postJson(route('backend.wallet.claim'))->assertOk()
        ->assertJsonPath('claimed', false)
        ->assertJsonPath('streak_bonus', 0)
        ->assertJsonPath('streak.days', 1);
});
