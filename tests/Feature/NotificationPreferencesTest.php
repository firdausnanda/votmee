<?php

use App\Models\Comment;
use App\Models\RankingPost;
use App\Models\Report;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AchievementService;
use App\Services\GamificationService;
use App\Services\ModerationService;
use Inertia\Testing\AssertableInertia as Assert;

function streakWallet(User $user, int $days = 5, ?string $lastClaimedOn = null): Wallet
{
    return Wallet::factory()->create([
        'user_id' => $user->id,
        'streak_days' => $days,
        'last_claimed_on' => $lastClaimedOn ?? app(GamificationService::class)->today(),
    ]);
}

function yesterdayInJakarta(): string
{
    return now('Asia/Jakarta')->subDay()->toDateString();
}

test('members receive every notification type by default', function () {
    $user = User::factory()->create();

    foreach (array_keys(config('notification_preferences.types')) as $type) {
        expect($user->wantsNotification($type))->toBeTrue();
    }
});

test('a switched off group stops its notifications but leaves the others', function () {
    $author = User::factory()->create(['notification_preferences' => ['likes' => false, 'badges' => false, 'reviews' => false]]);
    $post = RankingPost::factory()->create();
    $comment = Comment::factory()->create(['ranking_post_id' => $post->id, 'user_id' => $author->id]);

    $this->actingAs(User::factory()->create())->postJson(route('backend.comments.like', $comment))->assertOk();
    app(AchievementService::class)->unlockHidden($author, 'viral_comment');
    $report = Report::factory()->create(['reporter_id' => $author->id]);
    app(ModerationService::class)->resolveReport(User::factory()->moderator()->create(), $report, 'dismissed', 'Not a violation.');

    expect($author->notifications()->count())->toBe(0);

    $this->actingAs(User::factory()->create())->postJson(route('backend.comments.store', $post), ['body' => 'Still replying', 'parent_id' => $comment->id])->assertCreated();

    expect($author->notifications()->pluck('data')->map(fn (array $data): string => $data['type'])->all())->toBe(['comment_reply']);
});

test('likes do not refresh an existing like notification once the group is off', function () {
    $author = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $author->id]);

    $this->actingAs(User::factory()->create())->postJson(route('backend.comments.like', $comment))->assertOk();
    $author->update(['notification_preferences' => ['likes' => false]]);
    $this->actingAs(User::factory()->create())->postJson(route('backend.comments.like', $comment))->assertOk();

    expect($author->notifications()->count())->toBe(1)
        ->and($author->notifications()->first()->data['count'])->toBe(1);
});

test('members save only their switched off groups and can switch them back on', function () {
    $user = User::factory()->create();
    $all = array_fill_keys(config('notification_preferences.groups'), true);

    $this->patchJson(route('profile.notifications.update'), ['preferences' => $all])->assertUnauthorized();

    $this->actingAs($user)->patch(route('profile.notifications.update'), ['preferences' => [...$all, 'likes' => false, 'streak' => false]])
        ->assertRedirect(route('profile.edit'));
    expect($user->fresh()->notification_preferences)->toBe(['likes' => false, 'streak' => false]);

    $this->get(route('profile.edit'))->assertInertia(fn (Assert $page) => $page
        ->where('notificationPreferences.likes', false)
        ->where('notificationPreferences.replies', true));

    $this->patch(route('profile.notifications.update'), ['preferences' => $all])->assertRedirect();
    expect($user->fresh()->notification_preferences)->toBeNull();
});

test('unknown groups and non boolean values are rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patchJson(route('profile.notifications.update'), ['preferences' => ['spam' => false]])->assertJsonValidationErrors('preferences');
    $this->patchJson(route('profile.notifications.update'), ['preferences' => ['likes' => 'maybe']])->assertJsonValidationErrors('preferences.likes');
    $this->patchJson(route('profile.notifications.update'))->assertJsonValidationErrors('preferences');
    expect($user->fresh()->notification_preferences)->toBeNull();
});

test('a live streak that is not claimed today gets one reminder a day', function () {
    $user = User::factory()->create();
    streakWallet($user, 5, yesterdayInJakarta());

    $this->artisan('votmee:streak-reminders')->assertSuccessful();
    $this->artisan('votmee:streak-reminders')->assertSuccessful();

    $notification = $user->notifications()->first();
    expect($user->notifications()->count())->toBe(1)
        ->and($notification->data)->toMatchArray(['type' => 'streak_reminder', 'days' => 5, 'url' => '/wallet']);
});

test('streak reminders skip members who should not be reminded', function () {
    streakWallet($claimedToday = User::factory()->create(), 5);
    streakWallet($shortStreak = User::factory()->create(), 2, yesterdayInJakarta());
    streakWallet($lapsed = User::factory()->create(), 5, now('Asia/Jakarta')->subDays(2)->toDateString());
    streakWallet($optedOut = User::factory()->create(['notification_preferences' => ['streak' => false]]), 5, yesterdayInJakarta());
    streakWallet($unverified = User::factory()->unverified()->create(), 5, yesterdayInJakarta());
    streakWallet($deleted = User::factory()->create(), 5, yesterdayInJakarta());
    $deleted->delete();

    $this->artisan('votmee:streak-reminders')->assertSuccessful();

    foreach ([$claimedToday, $shortStreak, $lapsed, $optedOut, $unverified] as $user) {
        expect($user->notifications()->count())->toBe(0);
    }
    expect($deleted->notifications()->count())->toBe(0);
});

test('a dry run counts reminders without sending them', function () {
    $user = User::factory()->create();
    streakWallet($user, 5, yesterdayInJakarta());

    $this->artisan('votmee:streak-reminders --dry-run')->expectsOutputToContain('Would send 1')->assertSuccessful();

    expect($user->notifications()->count())->toBe(0);
});

test('claiming the daily bonus marks the streak reminder as read', function () {
    $user = User::factory()->create();
    streakWallet($user, 5, yesterdayInJakarta());
    $this->artisan('votmee:streak-reminders')->assertSuccessful();
    expect($user->unreadNotifications()->count())->toBe(1);

    $this->actingAs($user)->postJson(route('backend.wallet.claim'))->assertOk();

    expect($user->unreadNotifications()->count())->toBe(0);
});
