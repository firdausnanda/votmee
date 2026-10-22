<?php

use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\ObjectContentRevision;
use App\Models\RankingPost;
use App\Models\Report;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\ModerationService;
use Inertia\Testing\AssertableInertia as Assert;

function notificationTypes(User $user): array
{
    return $user->notifications()->pluck('data')->map(fn (array $data): string => $data['type'])->all();
}

test('replying to a comment notifies its author but not the author replying to themselves', function () {
    $post = RankingPost::factory()->create();
    $author = User::factory()->create();
    $replier = User::factory()->create();
    $comment = Comment::factory()->create(['ranking_post_id' => $post->id, 'user_id' => $author->id]);

    $this->actingAs($author)->postJson(route('backend.comments.store', $post), ['body' => 'Talking to myself', 'parent_id' => $comment->id])->assertCreated();
    expect(notificationTypes($author))->toBe([]);

    $this->actingAs($replier)->postJson(route('backend.comments.store', $post), ['body' => 'Good point', 'parent_id' => $comment->id])->assertCreated();

    $notification = $author->notifications()->first();
    expect($notification->data)->toMatchArray(['type' => 'comment_reply', 'excerpt' => 'Good point', 'url' => "/ranking/{$post->slug}"])
        ->and($notification->data['actor']['id'])->toBe($replier->id);
});

test('a top level comment notifies the ranking creator', function () {
    $post = RankingPost::factory()->create();
    $commenter = User::factory()->create();

    $this->actingAs($commenter)->postJson(route('backend.comments.store', $post), ['body' => 'Nice ranking'])->assertCreated();
    $this->actingAs($post->creator)->postJson(route('backend.comments.store', $post), ['body' => 'Thanks everyone'])->assertCreated();

    expect(notificationTypes($post->creator))->toBe(['ranking_comment']);
});

test('likes on one comment share a single unread notification and own likes are ignored', function () {
    $author = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $author->id]);

    $this->actingAs($author)->postJson(route('backend.comments.like', $comment))->assertOk();
    expect(notificationTypes($author))->toBe([]);

    $this->actingAs(User::factory()->create())->postJson(route('backend.comments.like', $comment))->assertOk();
    $this->actingAs(User::factory()->create())->postJson(route('backend.comments.like', $comment))->assertOk();

    expect($author->notifications()->count())->toBe(1)
        ->and($author->notifications()->first()->data['count'])->toBe(2);
});

test('a new badge notifies once and a repeat evaluation does not duplicate it', function () {
    $user = User::factory()->create();
    $service = app(AchievementService::class);

    expect($service->unlockHidden($user, 'viral_comment'))->toBeTrue();
    $service->unlockHidden($user, 'viral_comment');

    expect(notificationTypes($user))->toBe(['achievement_unlocked'])
        ->and($user->notifications()->first()->data['badge_key'])->toBe('hidden_viral_comment');
});

test('moderation decisions notify the contributor and skip self review', function () {
    $moderator = User::factory()->moderator()->create();
    $contributor = User::factory()->create();
    $moderation = app(ModerationService::class);

    $object = MasterObject::factory()->pending()->create(['submitted_by' => $contributor->id]);
    $moderation->reviewObject($moderator, $object, 'rejected', 'Duplicate entry.');

    $verified = MasterObject::factory()->create();
    $revision = ObjectContentRevision::factory()->create(['object_id' => $verified->id, 'author_id' => $contributor->id]);
    $moderation->reviewContentRevision($moderator, $revision, 'needs_revision', 'Add sources.');

    $report = Report::factory()->create(['reporter_id' => $contributor->id]);
    $moderation->resolveReport($moderator, $report, 'dismissed', 'Not a violation.');

    $own = ObjectContentRevision::factory()->create(['object_id' => $verified->id, 'author_id' => $moderator->id]);
    $moderation->reviewContentRevision($moderator, $own, 'published', 'Own work.');

    expect(notificationTypes($contributor))->toEqualCanonicalizing(['object_reviewed', 'content_reviewed', 'report_resolved'])
        ->and(notificationTypes($moderator))->toBe([]);
});

test('members list, read and clear only their own notifications', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    app(AchievementService::class)->unlockHidden($user, 'viral_comment');
    app(AchievementService::class)->unlockHidden($other, 'viral_comment');
    $id = $user->notifications()->first()->id;

    $this->getJson(route('backend.notifications.index'))->assertUnauthorized();
    $this->actingAs($user)->getJson(route('backend.notifications.index'))->assertOk()
        ->assertJsonPath('unread_count', 1)->assertJsonCount(1, 'notifications');

    $this->actingAs($other)->patchJson(route('backend.notifications.read', $id))->assertNotFound();
    $this->actingAs($user)->patchJson(route('backend.notifications.read', $id))->assertOk()->assertJsonPath('unread_count', 0);

    app(AchievementService::class)->unlockHidden($user, 'night_owl');
    $this->postJson(route('backend.notifications.read-all'))->assertOk()->assertJsonPath('unread_count', 0);
    expect($user->unreadNotifications()->count())->toBe(0);
});

test('the notifications page requires sign in and shares the unread count', function () {
    $user = User::factory()->create();
    app(AchievementService::class)->unlockHidden($user, 'viral_comment');

    $this->get(route('community.notifications', 'id'))->assertRedirect();
    $this->actingAs($user)->get(route('community.notifications', 'id'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Community/Notifications')->where('unreadNotifications', 1));
});

test('only old read notifications are pruned', function () {
    $user = User::factory()->create();
    $service = app(AchievementService::class);
    $service->unlockHidden($user, 'viral_comment');
    $service->unlockHidden($user, 'night_owl');
    [$old, $unread] = $user->notifications()->get()->all();
    $old->forceFill(['read_at' => now()->subDays(120)])->save();

    $this->artisan('votmee:prune-notifications')->assertSuccessful();

    expect($user->notifications()->pluck('id')->all())->toBe([$unread->id]);
});
