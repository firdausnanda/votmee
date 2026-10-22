<?php

use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\MasterObject;
use App\Models\ObjectContentRevision;
use App\Models\PointLedger;
use App\Models\Report;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\Wallet;
use App\Services\AchievementService;
use App\Services\GamificationService;
use App\Services\ModerationService;
use App\Services\SupportService;
use Inertia\Testing\AssertableInertia as Assert;

function achievementKeys(User $user): array
{
    return UserAchievement::query()->where('user_id', $user->id)->orderBy('achievement_key')->pluck('achievement_key')->all();
}

function achievementPoints(User $user): int
{
    return (int) PointLedger::query()->where('user_id', $user->id)->where('type', 'achievement')->sum('amount');
}

test('a seven day streak unlocks the bronze streak badge and pays its points once', function () {
    $user = User::factory()->create();
    $support = app(SupportService::class);
    $this->travelTo(now('Asia/Jakarta')->setTime(9, 0));

    foreach (range(1, 7) as $day) {
        $support->claimDaily($user);
        $this->travel(1)->day();
    }
    $support->claimDaily($user);

    expect(achievementKeys($user))->toBe(['streak_bronze'])
        ->and(achievementPoints($user))->toBe(5)
        ->and(app(AchievementService::class)->evaluate($user, 'streak'))->toBe([]);
});

test('moderation decisions unlock curator, writer and guardian badges for the contributor', function () {
    $moderator = User::factory()->moderator()->create();
    $contributor = User::factory()->create();
    $moderation = app(ModerationService::class);

    $object = MasterObject::factory()->pending()->create(['submitted_by' => $contributor->id]);
    $moderation->reviewObject($moderator, $object, 'verified', 'Matches the guidelines.');

    $verified = MasterObject::factory()->create();
    ObjectContentRevision::factory()->count(4)->create(['object_id' => $verified->id, 'author_id' => $contributor->id, 'status' => 'published']);
    $revision = ObjectContentRevision::factory()->create(['object_id' => $verified->id, 'author_id' => $contributor->id]);
    $moderation->reviewContentRevision($moderator, $revision, 'published', 'Accurate and sourced.');

    Report::factory()->count(4)->create(['reporter_id' => $contributor->id, 'status' => 'resolved']);
    $report = Report::factory()->create(['reporter_id' => $contributor->id]);
    $moderation->resolveReport($moderator, $report, 'dismissed', 'Not a violation.');
    expect(achievementKeys($contributor))->not->toContain('guardian_bronze');
    $moderation->resolveReport($moderator, $report, 'resolved', 'Confirmed violation.');

    expect(achievementKeys($contributor))->toBe(['curator_bronze', 'guardian_bronze', 'writer_bronze'])
        ->and(achievementPoints($contributor))->toBe(15);
});

test('likes from other members unlock the voice badge', function () {
    $author = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $author->id]);
    CommentLike::query()->create(['comment_id' => $comment->id, 'user_id' => $author->id]);
    User::factory()->count(9)->create()->each(fn (User $liker) => CommentLike::query()->create(['comment_id' => $comment->id, 'user_id' => $liker->id]));

    $this->actingAs(User::factory()->create())->postJson(route('backend.comments.like', $comment))->assertOk();

    expect(achievementKeys($author))->toBe(['voice_bronze']);
});

test('restricted members unlock badges without earning their points', function () {
    $user = User::factory()->restricted()->create();
    MasterObject::factory()->create(['submitted_by' => $user->id]);

    expect(app(AchievementService::class)->evaluate($user, 'curator'))->toBe(['curator_bronze'])
        ->and(achievementPoints($user))->toBe(0);
});

test('members feature up to three unlocked badges which then appear beside their comments', function () {
    $user = User::factory()->create();
    foreach (['streak_bronze', 'curator_bronze', 'writer_bronze', 'voice_bronze'] as $key) {
        UserAchievement::factory()->create(['user_id' => $user->id, 'achievement_key' => $key]);
    }
    $this->actingAs($user);

    $this->putJson(route('backend.achievements.feature'), ['keys' => ['streak_bronze', 'curator_bronze', 'writer_bronze', 'voice_bronze']])
        ->assertUnprocessable();
    $this->putJson(route('backend.achievements.feature'), ['keys' => ['guardian_gold']])
        ->assertUnprocessable()->assertJsonValidationErrors('keys');
    $this->putJson(route('backend.achievements.feature'), ['keys' => ['curator_bronze', 'voice_bronze']])
        ->assertOk()->assertJsonPath('featured', ['curator_bronze', 'voice_bronze']);

    $comment = Comment::factory()->create(['user_id' => $user->id]);
    $this->getJson(route('backend.comments.index', $comment->rankingPost))->assertOk()
        ->assertJsonPath('comments.0.badges', ['curator_bronze', 'voice_bronze']);
});

test('unlock notices are shared until the member marks them seen', function () {
    $user = User::factory()->create();
    UserAchievement::factory()->create(['user_id' => $user->id, 'achievement_key' => 'streak_bronze']);

    $this->actingAs($user)->get('/id/wallet')->assertInertia(fn (Assert $page) => $page
        ->where('unseenAchievements', ['streak_bronze']));
    $this->postJson(route('backend.achievements.seen'))->assertOk();
    $this->get('/id/wallet')->assertInertia(fn (Assert $page) => $page->where('unseenAchievements', []));
});

test('the profile lists every badge with progress', function () {
    $user = User::factory()->create();
    MasterObject::factory()->count(3)->create(['submitted_by' => $user->id]);
    app(AchievementService::class)->evaluate($user, 'curator');

    $this->get("/id/profile/{$user->username}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('achievements', 18)
        ->where('achievements.3.key', 'curator_bronze')
        ->where('achievements.3.current', 3)
        ->where('achievements.3.featured', false)
        ->whereNot('achievements.3.unlocked_at', null)
        ->where('achievements.4.key', 'curator_silver')
        ->where('achievements.4.unlocked_at', null));
});

test('the backfill command unlocks earned badges without paying points', function () {
    $user = User::factory()->create();
    MasterObject::factory()->create(['submitted_by' => $user->id]);
    Wallet::factory()->create(['user_id' => $user->id, 'longest_streak' => 30]);

    $this->artisan('votmee:achievements-backfill')->assertSuccessful();
    $this->artisan('votmee:achievements-backfill')->assertSuccessful();

    expect(achievementKeys($user))->toBe(['curator_bronze', 'streak_bronze', 'streak_silver'])
        ->and(achievementPoints($user))->toBe(0);
});

test('seasonal badges count only contributions within the current Jakarta month', function () {
    $user = User::factory()->create();
    MasterObject::factory()->count(2)->create(['submitted_by' => $user->id, 'verified_at' => now('UTC')->subMonths(2)]);
    MasterObject::factory()->count(2)->create(['submitted_by' => $user->id]);
    $achievements = app(AchievementService::class);
    $seasonKey = 'season_'.now('Asia/Jakarta')->format('Y_m').'_curator';

    expect($achievements->evaluate($user, 'curator'))->not->toContain($seasonKey);

    MasterObject::factory()->create(['submitted_by' => $user->id]);

    expect($achievements->evaluate($user, 'curator'))->toContain($seasonKey)
        ->and(PointLedger::query()->where('idempotency_key', "achievement:{$user->id}:{$seasonKey}")->value('amount'))->toBe(10);
});

test('hidden badges unlock from a late night claim, a perfect mission day and a viral comment', function () {
    config(['gamification.viral_comment_likes' => 2]);
    $user = User::factory()->create();
    $this->travelTo(now('Asia/Jakarta')->setTime(0, 30));
    app(SupportService::class)->claimDaily($user);

    $gamification = app(GamificationService::class);
    foreach (array_keys(config('gamification.missions')) as $mission) {
        $gamification->completeMission($user, $mission);
    }

    $comment = Comment::factory()->create(['user_id' => $user->id]);
    foreach (User::factory()->count(2)->create() as $liker) {
        $this->actingAs($liker)->postJson(route('backend.comments.like', $comment))->assertOk();
    }

    expect(achievementKeys($user))->toBe(['hidden_night_owl', 'hidden_perfect_day', 'hidden_viral_comment'])
        ->and(app(AchievementService::class)->hiddenLockedCount($user))->toBe(0);
});

test('locked hidden badges stay out of the profile progress', function () {
    $user = User::factory()->create();
    app(AchievementService::class)->unlockHidden($user, 'night_owl');

    $this->get("/id/profile/{$user->username}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('achievements', 19)
        ->where('achievements.18.key', 'hidden_night_owl')
        ->where('hiddenAchievementsLocked', 2));
});

test('the silver writer badge makes a trusted contributor whose revisions publish at once', function () {
    $user = User::factory()->create();
    $object = MasterObject::factory()->create();
    ObjectContentRevision::factory()->count(25)->create(['object_id' => $object->id, 'author_id' => $user->id, 'status' => 'published']);
    app(AchievementService::class)->evaluate($user, 'writer');

    expect($user->refresh()->isTrustedContributor())->toBeTrue();

    $this->actingAs($user)->postJson(route('backend.objects.summary.store', $object), [
        'body' => str_repeat('A trusted summary with enough detail. ', 2),
        'language_code' => 'id',
        'sources' => ['https://example.com/source'],
    ])->assertCreated()->assertJsonPath('status', 'published');

    $untrusted = User::factory()->create();
    $this->actingAs($untrusted)->postJson(route('backend.objects.summary.store', MasterObject::factory()->create()), [
        'body' => str_repeat('An ordinary summary with enough detail. ', 2),
        'language_code' => 'id',
        'sources' => ['https://example.com/source'],
    ])->assertCreated()->assertJsonPath('status', 'pending');
});

test('moderators revoke trust and a revoked member is not trusted again automatically', function () {
    $moderator = User::factory()->moderator()->create();
    $user = User::factory()->create(['trusted_at' => now('UTC')]);
    UserAchievement::factory()->create(['user_id' => $user->id, 'achievement_key' => 'writer_silver']);

    $this->actingAs(User::factory()->create())->patchJson(route('admin.backend.users.trust', $user), ['trusted' => false, 'reason' => 'Low quality edits.'])
        ->assertForbidden();
    $this->actingAs($moderator)->patchJson(route('admin.backend.users.trust', $user), ['trusted' => false, 'reason' => 'Low quality edits.'])
        ->assertOk();
    app(AchievementService::class)->evaluate($user->refresh(), 'writer');

    expect($user->refresh()->trusted_at)->toBeNull()
        ->and($user->trust_revoked_at)->not->toBeNull();

    $this->patchJson(route('admin.backend.users.trust', $user), ['trusted' => true, 'reason' => 'Quality restored.'])->assertOk();
    expect($user->refresh()->isTrustedContributor())->toBeTrue();
});

test('the hall of fame ranks contributors within the chosen month', function () {
    $top = User::factory()->create();
    $runnerUp = User::factory()->create();
    MasterObject::factory()->count(3)->create(['submitted_by' => $top->id]);
    MasterObject::factory()->create(['submitted_by' => $runnerUp->id]);
    MasterObject::factory()->count(5)->create(['submitted_by' => $runnerUp->id, 'verified_at' => now('UTC')->subMonths(2)]);
    $month = now('Asia/Jakarta')->format('Y-m');

    $this->get("/id/hall-of-fame?month={$month}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Community/HallOfFame')
        ->where('month', $month)
        ->where('board.curator.0.user.username', $top->username)
        ->where('board.curator.0.count', 3)
        ->where('board.curator.1.count', 1));

    $this->get('/id/hall-of-fame?month=1999-01')->assertOk()->assertInertia(fn (Assert $page) => $page->where('month', $month));
});
