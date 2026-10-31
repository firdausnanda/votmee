<?php

use App\Models\Category;
use App\Models\MasterObject;
use App\Models\RankingEntry;
use App\Models\RankingPost;
use App\Models\SupportEvent;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\AutoRankingService;
use App\Services\LeaderboardService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.gemini.key' => 'test-key', 'autopilot.rankings.auto_publish' => false]);
});

/** @return list<string> */
function autoRankingObjects(Category $category, int $count = 6): array
{
    return MasterObject::factory()->count($count)->create(['category_id' => $category->id])->pluck('id')->all();
}

/** @param array<string, mixed>|string $draft */
function fakeGeminiDraft(array|string $draft): void
{
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => is_string($draft) ? $draft : json_encode($draft)]]]]],
    ])]);
}

test('members publishing within the quiet window keep the generator idle', function () {
    Http::fake();
    autoRankingObjects(Category::factory()->create());
    RankingPost::factory()->create(['published_at' => now('UTC')->subDays(2)]);

    expect(app(AutoRankingService::class)->run()['status'])->toBe('active');

    Http::assertNothingSent();
    expect(RankingPost::query()->whereNotNull('generator')->exists())->toBeFalse();
});

test('rankings from the official account do not end the quiet period', function () {
    RankingPost::factory()->create(['creator_id' => User::official()->id, 'published_at' => now('UTC')->subDay()]);
    RankingPost::factory()->create(['published_at' => now('UTC')->subDays(4)]);

    expect(app(AutoRankingService::class)->isQuiet())->toBeTrue();
});

test('a quiet community gets a gemini ranking held for review under the official account', function () {
    $category = Category::factory()->create();
    $objectIds = autoRankingObjects($category);
    RankingPost::factory()->create(['published_at' => now('UTC')->subDays(4)]);
    $picked = array_reverse(array_slice($objectIds, 0, 5));
    fakeGeminiDraft(['title' => 'Pilihan terbaik untuk begadang', 'criteria' => "Dinilai dari rasa dan tenaga.\n1. A - alasan", 'object_ids' => $picked]);

    $this->artisan('votmee:auto-rankings')->expectsOutputToContain('Generated 1 ranking(s)')->assertSuccessful();

    $post = RankingPost::query()->whereNotNull('generator')->with(['creator', 'entries'])->sole();
    expect($post->status)->toBe('pending_review')
        ->and($post->published_at)->toBeNull()
        ->and($post->generator)->toBe('gemini')
        ->and($post->creator->is_official)->toBeTrue()
        ->and($post->entries->pluck('object_id')->all())->toBe($picked);
    Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-key')
        && collect($request['generationConfig']['responseSchema']['properties']['object_ids']['items']['enum'])->sort()->values()->all() === collect($objectIds)->sort()->values()->all());
});

test('an unusable gemini answer falls back to the template', function (Closure $fake) {
    $category = Category::factory()->create();
    $objectIds = autoRankingObjects($category);
    $fake($objectIds);

    $result = app(AutoRankingService::class)->run();

    expect($result['posts'])->toHaveCount(1)
        ->and($result['posts'][0]->generator)->toBe('template')
        ->and($result['posts'][0]->entries)->toHaveCount(6);
})->with([
    'unknown object' => fn () => fn (array $ids) => fakeGeminiDraft(['title' => 'Judul ranking', 'criteria' => 'Kriteria yang cukup panjang.', 'object_ids' => [...array_slice($ids, 0, 4), '01JUNKNOWNOBJECTIDXXXXXXXX']]),
    'rate limited' => fn () => fn (array $ids) => Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota'], 429)]),
    'unreadable json' => fn () => fn (array $ids) => fakeGeminiDraft('not json'),
]);

test('nothing is generated without enough verified objects or past the daily limit', function () {
    Http::fake();
    $category = Category::factory()->create();
    autoRankingObjects($category, 3);

    $result = app(AutoRankingService::class)->run();
    expect($result['status'])->toBe('done')->and($result['posts'])->toBe([]);
    Http::assertNothingSent();

    RankingPost::factory()->count(3)->create(['creator_id' => User::official()->id, 'generator' => 'template', 'status' => 'pending_review']);
    expect(app(AutoRankingService::class)->run()['status'])->toBe('daily_limit');
});

test('moderators publish or reject generated rankings', function () {
    $post = RankingPost::factory()->create(['creator_id' => User::official()->id, 'generator' => 'gemini', 'status' => 'pending_review', 'published_at' => null]);
    $payload = ['status' => 'published', 'reason' => 'Ranking layak tayang.'];

    $this->actingAs(User::factory()->create())->patchJson(route('admin.backend.rankings.review', $post), $payload)->assertForbidden();
    $this->actingAs(User::factory()->moderator()->create())->patchJson(route('admin.backend.rankings.review', $post), $payload)
        ->assertOk()->assertJsonPath('status', 'published');
    expect($post->refresh()->published_at)->not->toBeNull();

    $this->patchJson(route('admin.backend.rankings.review', $post), ['status' => 'rejected', 'reason' => 'Sudah ditinjau.'])
        ->assertUnprocessable();
});

test('the official account stays off the creator leaderboard and hall of fame', function () {
    $official = User::official();
    $post = RankingPost::factory()->create(['creator_id' => $official->id, 'generator' => 'gemini']);
    SupportEvent::factory()->create(['ranking_entry_id' => RankingEntry::factory()->create(['ranking_post_id' => $post->id])->id]);

    $board = app(AchievementService::class)->hallOfFame(app(AchievementService::class)->currentMonth());

    expect(app(LeaderboardService::class)->creators('all_time'))->toBe([])
        ->and(collect($board)->flatten(1)->pluck('user.id')->all())->not->toContain($official->id);
});
