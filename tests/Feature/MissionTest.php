<?php

use App\Models\Comment;
use App\Models\MissionCompletion;
use App\Models\ObjectArticle;
use App\Models\ObjectContentRevision;
use App\Models\RankingEntry;
use App\Models\RankingPost;
use App\Models\SupportEvent;
use App\Models\User;
use App\Models\UserAchievement;
use App\Services\MissionService;
use App\Services\ModerationService;
use Carbon\CarbonImmutable;

function travelToJakarta(string $moment): void
{
    test()->travelTo(CarbonImmutable::parse($moment, 'Asia/Jakarta'));
}

function writeArticleAt(User $author, string $writtenAt, ?ObjectArticle $article = null): ObjectContentRevision
{
    travelToJakarta($writtenAt);
    $article ??= ObjectArticle::factory()->create(['author_id' => $author->id]);

    return ObjectContentRevision::factory()->create(['object_id' => $article->object_id, 'article_id' => $article->id, 'author_id' => $author->id]);
}

function publishRevisionAt(ObjectContentRevision $revision, string $publishedAt): void
{
    travelToJakarta($publishedAt);
    app(ModerationService::class)->reviewContentRevision(User::factory()->moderator()->create(), $revision, 'published', 'Looks good.');
}

function establishedMember(): User
{
    return User::factory()->create(['created_at' => now()->subDays(30)]);
}

function supportFrom(User $supporter, RankingEntry $entry): SupportEvent
{
    return SupportEvent::factory()->create(['user_id' => $supporter->id, 'ranking_entry_id' => $entry->id, 'ledger_id' => null, 'occurred_at' => now('UTC')]);
}

/** @return list<string> */
function completedWeeks(User $user, string $mission): array
{
    return MissionCompletion::query()->where('user_id', $user->id)->where('mission', $mission)->get()
        ->map(fn (MissionCompletion $completion): string => $completion->completed_on->toDateString())->all();
}

test('the daily assignment is stable for a member and day and always includes the fixed missions', function () {
    $user = User::factory()->create();
    $missions = app(MissionService::class);
    $day = CarbonImmutable::parse('2026-10-19', 'Asia/Jakarta');
    $assignment = $missions->dailyAssignment($user, $day);

    expect($assignment)->toBe($missions->dailyAssignment($user, $day))
        ->toHaveCount(4)
        ->and($assignment[0])->toBe('quick_vote')
        ->and(array_unique($assignment))->toHaveCount(4)
        ->and(array_diff($assignment, array_keys(config('gamification.missions.daily.pool'))))->toBe([]);
});

test('finishing every assigned mission pays a perfect day bonus and five perfect days complete the weekly mission', function () {
    config(['gamification.missions.daily.always' => ['write_comments', 'article_comment'], 'gamification.missions.daily.slots' => 2]);
    $user = User::factory()->create();
    $missions = app(MissionService::class);

    foreach (range(0, 4) as $day) {
        travelToJakarta('2026-10-19 09:00');
        $this->travel($day)->days();
        Comment::factory()->count(2)->create(['user_id' => $user->id, 'ranking_post_id' => null, 'article_id' => ObjectArticle::factory()]);
        $missions->record($user, 'write_comments');
        $missions->record($user, 'article_comment');

        if ($day === 3) {
            expect(completedWeeks($user, 'daily_complete'))->toBe([]);
        }
    }

    $perfectDays = MissionCompletion::query()->where('user_id', $user->id)->where('mission', MissionService::PERFECT_DAY)->get();

    expect($perfectDays)->toHaveCount(5)
        ->and($perfectDays->pluck('amount')->unique()->all())->toBe([5])
        ->and(UserAchievement::query()->where('user_id', $user->id)->where('achievement_key', 'hidden_perfect_day')->exists())->toBeTrue()
        ->and(MissionCompletion::query()->where('user_id', $user->id)->where('mission', 'daily_complete')->sole())
        ->period->toBe('weekly')
        ->amount->toBe(15)
        ->and(completedWeeks($user, 'daily_complete'))->toBe(['2026-10-19']);
});

test('a weekly article counts only when it was written that week and published within the grace period', function () {
    $author = User::factory()->create();
    $oldArticle = writeArticleAt($author, '2026-10-05 10:00');
    publishRevisionAt($oldArticle, '2026-10-20 10:00');

    expect(completedWeeks($author, 'write_article'))->toBe([]);

    publishRevisionAt(writeArticleAt($author, '2026-10-18 23:30'), '2026-10-20 11:00');

    expect(completedWeeks($author, 'write_article'))->toBe(['2026-10-12'])
        ->and(MissionCompletion::query()->where('mission', 'write_article')->value('period'))->toBe('weekly');

    publishRevisionAt(writeArticleAt($author, '2026-10-21 10:00', $oldArticle->article), '2026-10-21 12:00');

    expect(completedWeeks($author, 'write_article'))->toBe(['2026-10-12']);

    $slowReview = User::factory()->create();
    publishRevisionAt(writeArticleAt($slowReview, '2026-10-11 20:00'), '2026-10-16 10:00');

    expect(completedWeeks($slowReview, 'write_article'))->toBe([]);
});

test('a ranking challenge counts distinct established members within seven days of a ranking created that week', function () {
    config(['gamification.missions.challenges.pool.popular_ranking.target' => 3]);
    travelToJakarta('2026-10-19 09:00');
    $owner = User::factory()->create();
    $post = RankingPost::factory()->create(['creator_id' => $owner->id]);
    $entry = RankingEntry::factory()->create(['ranking_post_id' => $post->id]);
    $missions = app(MissionService::class);

    supportFrom($owner, $entry);
    supportFrom(User::factory()->create(), $entry);
    $repeatSupporter = establishedMember();
    supportFrom($repeatSupporter, $entry);
    supportFrom($repeatSupporter, $entry);
    supportFrom(establishedMember(), $entry);

    expect($missions->record($owner, 'popular_ranking', $post->created_at))->toBeNull();

    travelToJakarta('2026-10-27 10:00');
    supportFrom(establishedMember(), $entry);

    expect($missions->record($owner, 'popular_ranking', $post->created_at))->toBeNull();

    travelToJakarta('2026-10-21 10:00');
    supportFrom(establishedMember(), $entry);

    expect($missions->record($owner, 'popular_ranking', $post->created_at))
        ->period->toBe('challenge')
        ->amount->toBe(15)
        ->and(completedWeeks($owner, 'popular_ranking'))->toBe(['2026-10-19']);

    $oldOwner = User::factory()->create();
    travelToJakarta('2026-10-05 09:00');
    $oldPost = RankingPost::factory()->create(['creator_id' => $oldOwner->id]);
    $oldEntry = RankingEntry::factory()->create(['ranking_post_id' => $oldPost->id]);
    travelToJakarta('2026-10-21 12:00');
    foreach (range(1, 3) as $supporter) {
        supportFrom(establishedMember(), $oldEntry);
    }

    expect($missions->record($oldOwner, 'popular_ranking', $oldPost->created_at))->toBeNull()
        ->and(collect($missions->overview($oldOwner)['challenges'])->firstWhere('key', 'popular_ranking')['progress'])->toBe(0);
});

test('a lively ranking needs comments from enough different members', function () {
    config([
        'gamification.missions.challenges.pool.discussed_ranking' => ['target' => 4, 'distinct' => 2, 'points' => 15],
        'gamification.missions.challenges.min_account_age_days' => 0,
    ]);
    $owner = User::factory()->create();
    $post = RankingPost::factory()->create(['creator_id' => $owner->id]);
    $missions = app(MissionService::class);

    Comment::factory()->count(4)->create(['ranking_post_id' => $post->id, 'user_id' => User::factory()]);
    Comment::factory()->count(4)->create(['ranking_post_id' => $post->id, 'user_id' => $owner->id]);
    $chatty = User::factory()->create();
    Comment::factory()->count(4)->create(['ranking_post_id' => $post->id, 'user_id' => $chatty->id]);

    expect($missions->progress($owner, 'discussed_ranking', CarbonImmutable::now('Asia/Jakarta')->startOfWeek()))->toBe(6);

    $chattyOnly = User::factory()->create();
    $lonePost = RankingPost::factory()->create(['creator_id' => $chattyOnly->id]);
    Comment::factory()->count(4)->create(['ranking_post_id' => $lonePost->id, 'user_id' => $chatty->id]);

    expect($missions->record($chattyOnly, 'discussed_ranking', $lonePost->created_at))->toBeNull()
        ->and($missions->record($owner, 'discussed_ranking', $post->created_at))->not->toBeNull();
});

test('hidden content voids an unclaimed weekly reward', function () {
    travelToJakarta('2026-10-19 09:00');
    $owner = User::factory()->create();
    $post = RankingPost::factory()->create(['creator_id' => $owner->id]);
    $completion = app(MissionService::class)->record($owner, 'create_ranking', $post->created_at);

    expect($completion->amount)->toBe(10);

    $post->update(['status' => 'hidden']);

    $this->actingAs($owner)->postJson(route('backend.wallet.missions.claim', $completion->id))
        ->assertUnprocessable()->assertJsonValidationErrors('mission');

    expect(MissionCompletion::query()->whereKey($completion->id)->exists())->toBeFalse();
});

test('unclaimed rewards stay listed until their claim window ends', function () {
    $user = User::factory()->create();
    $completion = MissionCompletion::factory()->create(['user_id' => $user->id, 'completed_on' => '2026-10-19']);
    $missions = app(MissionService::class);

    travelToJakarta('2026-10-22 09:00');
    expect($missions->overview($user)['pending'])->toHaveCount(1);

    travelToJakarta('2026-10-28 09:00');
    expect($missions->overview($user)['pending'])->toBe([]);

    $this->actingAs($user)->postJson(route('backend.wallet.missions.claim', $completion->id))
        ->assertUnprocessable()->assertJsonValidationErrors('mission');
});

test('weekly rewards share the weekly cap', function () {
    config(['gamification.missions.weekly.cap' => 12]);
    travelToJakarta('2026-10-19 09:00');
    $author = User::factory()->create();
    $post = RankingPost::factory()->create(['creator_id' => $author->id]);

    expect(app(MissionService::class)->record($author, 'create_ranking', $post->created_at)->amount)->toBe(10);

    publishRevisionAt(ObjectContentRevision::factory()->create(['author_id' => $author->id]), '2026-10-20 09:00');

    expect(MissionCompletion::query()->where('user_id', $author->id)->where('mission', 'improve_summary')->value('amount'))->toBe(2);
});
