<?php

use App\Models\Comment;
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

    expect(balanceOf($user))->toBe(7 * 5 + 10)
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

test('missions pay once per day and the daily cap trims the payout', function () {
    config(['gamification.daily_mission_cap' => 5]);
    $user = User::factory()->create();
    $gamification = app(GamificationService::class);

    expect($gamification->completeMission($user, 'quick_vote')->amount)->toBe(3)
        ->and($gamification->completeMission($user, 'quick_vote'))->toBeNull()
        ->and($gamification->completeMission($user, 'support')->amount)->toBe(2)
        ->and($gamification->completeMission($user, 'comment_liked'))->toBeNull()
        ->and(balanceOf($user))->toBe(5);

    $this->travel(1)->day();

    expect($gamification->completeMission($user, 'quick_vote')->amount)->toBe(3)
        ->and(balanceOf($user))->toBe(8);
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

    expect(balanceOf($author))->toBe(4)
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
        ->where('missions.1.earned', 3));

    $this->postJson(route('backend.wallet.claim'))->assertOk()
        ->assertJsonPath('claimed', false)
        ->assertJsonPath('streak_bonus', 0)
        ->assertJsonPath('streak.days', 1);
});
