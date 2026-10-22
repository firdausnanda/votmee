<?php

use App\Events\NotificationRefreshed;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentLiked;
use App\Notifications\CommentReplied;
use App\Services\AchievementService;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Support\Facades\Event;

function unlockBadgeFor(User $user, string $name = 'viral_comment'): void
{
    app(AchievementService::class)->unlockHidden($user, $name);
}

test('notifications are broadcast unless their group is switched off', function () {
    $user = User::factory()->create();
    $quiet = User::factory()->create(['notification_preferences' => ['replies' => false]]);

    expect((new CommentReplied)->via($user))->toBe(['database', 'broadcast'])
        ->and((new CommentReplied)->via($quiet))->toBe([])
        ->and((new CommentLiked)->via($quiet))->toBe(['database', 'broadcast']);
});

test('the broadcast carries the stored notification and the unread count', function () {
    Event::fake([BroadcastNotificationCreated::class]);
    $user = User::factory()->create();

    unlockBadgeFor($user, 'viral_comment');
    unlockBadgeFor($user, 'night_owl');

    $stored = $user->notifications()->oldest()->get();
    Event::assertDispatchedTimes(BroadcastNotificationCreated::class, 2);
    Event::assertDispatched(BroadcastNotificationCreated::class, fn ($event): bool => $event->data['notification']['id'] === $stored->last()->id
        && $event->data['notification']['type'] === 'achievement_unlocked'
        && $event->data['notification']['read'] === false
        && $event->data['notification']['data']['badge_key'] === 'hidden_night_owl'
        && $event->data['unread_count'] === 2);
});

test('a switched off group is not broadcast', function () {
    Event::fake([BroadcastNotificationCreated::class]);
    $user = User::factory()->create(['notification_preferences' => ['badges' => false]]);

    unlockBadgeFor($user);

    Event::assertNotDispatched(BroadcastNotificationCreated::class);
});

test('another like refreshes the existing notification instead of adding one', function () {
    $author = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $author->id]);
    $this->actingAs(User::factory()->create())->postJson(route('backend.comments.like', $comment))->assertOk();

    Event::fake([NotificationRefreshed::class]);
    $this->actingAs(User::factory()->create())->postJson(route('backend.comments.like', $comment))->assertOk();

    Event::assertDispatched(NotificationRefreshed::class, fn (NotificationRefreshed $event): bool => $event->user->is($author)
        && $event->notification->is($author->notifications()->first()));
    expect($author->notifications()->count())->toBe(1);
});

test('the refresh event carries the updated notification on the private user channel', function () {
    $author = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $author->id]);
    $this->actingAs(User::factory()->create())->postJson(route('backend.comments.like', $comment))->assertOk();
    $event = new NotificationRefreshed($author, $author->notifications()->first());

    expect($event->broadcastAs())->toBe('notification.refreshed')
        ->and($event->broadcastOn()[0]->name)->toBe("private-App.Models.User.{$author->id}")
        ->and($event->broadcastWith())->toMatchArray(['unread_count' => 1])
        ->and($event->broadcastWith()['notification'])->toMatchArray(['id' => $author->notifications()->first()->id, 'type' => 'comment_liked']);
});

test('members can only join their own notification channel', function () {
    config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'key', 'broadcasting.connections.reverb.secret' => 'secret', 'broadcasting.connections.reverb.app_id' => '1']);
    require base_path('routes/channels.php');
    $user = User::factory()->create();
    $other = User::factory()->create();
    $join = fn (User $channelOwner) => $this->post('/broadcasting/auth', ['channel_name' => "private-App.Models.User.{$channelOwner->id}", 'socket_id' => '123.456']);

    $join($user)->assertForbidden();
    $this->actingAs($user);
    $join($user)->assertOk()->assertJsonStructure(['auth']);
    $join($other)->assertForbidden();
});

test('members delete a single notification of their own', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    unlockBadgeFor($user);
    unlockBadgeFor($other);
    $id = $user->notifications()->first()->id;
    $otherId = $other->notifications()->first()->id;

    $this->deleteJson(route('backend.notifications.destroy', $id))->assertUnauthorized();
    $this->actingAs($user)->deleteJson(route('backend.notifications.destroy', $otherId))->assertNotFound();
    $this->deleteJson(route('backend.notifications.destroy', $id))->assertOk()->assertJsonPath('unread_count', 0);

    expect($user->notifications()->count())->toBe(0)
        ->and($other->notifications()->count())->toBe(1);
});

test('clearing read notifications keeps unread ones and other members notifications', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    unlockBadgeFor($user, 'viral_comment');
    unlockBadgeFor($user, 'night_owl');
    unlockBadgeFor($other);
    $user->notifications()->oldest()->first()->markAsRead();
    $other->notifications()->first()->markAsRead();

    $this->deleteJson(route('backend.notifications.clear-read', ['scope' => 'read']))->assertUnauthorized();
    $this->actingAs($user)->deleteJson(route('backend.notifications.clear-read', ['scope' => 'all']))->assertJsonValidationErrors('scope');
    $this->deleteJson(route('backend.notifications.clear-read'))->assertJsonValidationErrors('scope');
    $this->deleteJson(route('backend.notifications.clear-read', ['scope' => 'read']))->assertOk()->assertJsonPath('deleted', 1)->assertJsonPath('unread_count', 1);

    expect($user->notifications()->count())->toBe(1)
        ->and($user->unreadNotifications()->count())->toBe(1)
        ->and($other->notifications()->count())->toBe(1);
});
