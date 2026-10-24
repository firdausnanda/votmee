<?php

use App\Models\Category;
use App\Models\MissionCompletion;
use App\Models\PointLedger;
use App\Models\QuickDuel;
use App\Models\RankingEntry;
use App\Models\RankingPost;
use App\Models\SupportEvent;
use App\Models\User;
use App\Models\Wallet;
use App\Services\LeaderboardService;
use App\Services\QuickVoteService;
use App\Services\SupportService;
use Illuminate\Support\Collection;

/** @return array{0: RankingPost, 1: Collection<int, RankingEntry>} */
function rankingWithEntries(int $count = 3, ?Category $category = null): array
{
    $post = RankingPost::factory()->create(['category_id' => ($category ?? Category::factory()->create())->id]);
    $entries = collect(range(1, $count))->map(fn (int $position): RankingEntry => RankingEntry::factory()->create([
        'ranking_post_id' => $post->id,
        'position' => $position,
    ]));

    return [$post, $entries];
}

function paidSupport(RankingEntry $entry, int $amount): void
{
    SupportEvent::factory()->create(['ranking_entry_id' => $entry->id, 'amount' => $amount]);
}

function quickVoteFor(RankingEntry $entry, ?User $user = null): SupportEvent
{
    return SupportEvent::factory()->create([
        'ranking_entry_id' => $entry->id,
        'user_id' => ($user ?? User::factory()->create())->id,
        'ledger_id' => null,
        'amount' => 1,
        'source' => 'quick_vote',
        'vote_date' => app(QuickVoteService::class)->today(),
    ]);
}

test('guests see today duel without results and must sign in to vote', function () {
    [, $entries] = rankingWithEntries(2);
    $duel = QuickDuel::factory()->create(['ranking_post_id' => $entries[0]->ranking_post_id, 'entry_a_id' => $entries[0]->id, 'entry_b_id' => $entries[1]->id]);

    $this->getJson(route('backend.quick-duel.show'))->assertOk()
        ->assertJsonPath('duel.id', $duel->id)
        ->assertJsonPath('duel.my_vote', null)
        ->assertJsonPath('duel.results', null)
        ->assertJsonCount(2, 'duel.entries');

    $this->postJson(route('backend.quick-duel.vote', $duel), ['ranking_entry_id' => $entries[0]->id])->assertUnauthorized();
});

test('the first viewer request builds the day duels from the closest scored pair', function () {
    [$post, $entries] = rankingWithEntries(3);
    paidSupport($entries[0], 10);
    paidSupport($entries[1], 9);
    paidSupport($entries[2], 1);

    $this->getJson(route('backend.quick-duel.show'))->assertOk()
        ->assertJsonPath('duel.ranking.slug', $post->slug)
        ->assertJsonPath('duel.entries.0.id', $entries[0]->id)
        ->assertJsonPath('duel.entries.1.id', $entries[1]->id);
});

test('an object does not return to a duel for a week and stays out of the same day twice', function () {
    [, $entries] = rankingWithEntries(2);
    [, $sameObject] = rankingWithEntries(2);
    $sameObject[0]->update(['object_id' => $entries[0]->object_id]);
    $quickVotes = app(QuickVoteService::class);
    $today = $quickVotes->today();

    expect($quickVotes->generate($today))->toBe(1)
        ->and($quickVotes->generate(now('Asia/Jakarta')->addDay()->toDateString()))->toBe(0)
        ->and($quickVotes->generate(now('Asia/Jakarta')->addDays(8)->toDateString()))->toBe(1);
});

test('a quick vote is free, records one point and leaves the daily mission ready to claim', function () {
    $user = User::factory()->create();
    [, $entries] = rankingWithEntries(2);
    $duel = QuickDuel::factory()->create(['ranking_post_id' => $entries[0]->ranking_post_id, 'entry_a_id' => $entries[0]->id, 'entry_b_id' => $entries[1]->id]);

    $this->actingAs($user)->postJson(route('backend.quick-duel.vote', $duel), ['ranking_entry_id' => $entries[1]->id])
        ->assertOk()
        ->assertJsonPath('duel.my_vote', $entries[1]->id)
        ->assertJsonPath('duel.results.total', 1)
        ->assertJsonPath('duel.results.enough', false)
        ->assertJsonPath('duel.results.percents', null);

    $event = SupportEvent::query()->sole();

    expect($event->source)->toBe('quick_vote')
        ->and($event->amount)->toBe(1)
        ->and($event->ledger_id)->toBeNull()
        ->and($event->user_id)->toBe($user->id)
        ->and(Wallet::query()->where('user_id', $user->id)->value('balance'))->toBe(0)
        ->and(MissionCompletion::query()->where('user_id', $user->id)->where('mission', 'quick_vote')->value('amount'))->toBe(3)
        ->and(PointLedger::query()->count())->toBe(0);
});

test('only one quick vote per Jakarta day is allowed and a new day restores it', function () {
    $user = User::factory()->create();
    [, $entries] = rankingWithEntries(2);
    $duel = QuickDuel::factory()->create(['ranking_post_id' => $entries[0]->ranking_post_id, 'entry_a_id' => $entries[0]->id, 'entry_b_id' => $entries[1]->id]);
    $url = route('backend.quick-duel.vote', $duel);

    $this->actingAs($user)->postJson($url, ['ranking_entry_id' => $entries[0]->id])->assertOk();
    $this->actingAs($user)->postJson($url, ['ranking_entry_id' => $entries[0]->id])->assertOk();
    $this->actingAs($user)->postJson($url, ['ranking_entry_id' => $entries[1]->id])->assertUnprocessable()->assertJsonValidationErrors('ranking_entry_id');

    expect(SupportEvent::query()->count())->toBe(1);

    $this->travelTo(now('Asia/Jakarta')->addDay()->startOfDay()->addHour());
    [, $nextEntries] = rankingWithEntries(2);
    $nextDuel = QuickDuel::factory()->create(['ranking_post_id' => $nextEntries[0]->ranking_post_id, 'entry_a_id' => $nextEntries[0]->id, 'entry_b_id' => $nextEntries[1]->id]);

    $this->actingAs($user)->postJson($url, ['ranking_entry_id' => $entries[0]->id])->assertUnprocessable()->assertJsonValidationErrors('duel');
    $this->actingAs($user)->postJson(route('backend.quick-duel.vote', $nextDuel), ['ranking_entry_id' => $nextEntries[0]->id])->assertOk();

    expect(SupportEvent::query()->count())->toBe(2);
});

test('quick votes reject entries outside the duel and unverified accounts', function () {
    [, $entries] = rankingWithEntries(3);
    $duel = QuickDuel::factory()->create(['ranking_post_id' => $entries[0]->ranking_post_id, 'entry_a_id' => $entries[0]->id, 'entry_b_id' => $entries[1]->id]);

    $this->actingAs(User::factory()->create())->postJson(route('backend.quick-duel.vote', $duel), ['ranking_entry_id' => $entries[2]->id])
        ->assertUnprocessable()->assertJsonValidationErrors('ranking_entry_id');
    $this->actingAs(User::factory()->unverified()->create())->postJson(route('backend.quick-duel.vote', $duel), ['ranking_entry_id' => $entries[0]->id])
        ->assertUnprocessable()->assertJsonValidationErrors('account');

    expect(SupportEvent::query()->count())->toBe(0);
});

test('percentages appear only after the viewer votes and enough people have voted', function () {
    [, $entries] = rankingWithEntries(2);
    $duel = QuickDuel::factory()->create(['ranking_post_id' => $entries[0]->ranking_post_id, 'entry_a_id' => $entries[0]->id, 'entry_b_id' => $entries[1]->id]);
    collect(range(1, 7))->each(fn () => quickVoteFor($entries[0]));
    collect(range(1, 2))->each(fn () => quickVoteFor($entries[1]));
    $viewer = User::factory()->create();

    $this->actingAs($viewer)->getJson(route('backend.quick-duel.show'))->assertOk()->assertJsonPath('duel.results', null);

    $this->actingAs($viewer)->postJson(route('backend.quick-duel.vote', $duel), ['ranking_entry_id' => $entries[1]->id])
        ->assertOk()
        ->assertJsonPath('duel.results.total', 10)
        ->assertJsonPath('duel.results.enough', true)
        ->assertJsonPath('duel.results.percents.'.$entries[0]->id, 70)
        ->assertJsonPath('duel.results.percents.'.$entries[1]->id, 30);
});

test('quick votes lift support points but not community supporter counts', function () {
    [$post, $entries] = rankingWithEntries(2);
    paidSupport($entries[0], 5);
    quickVoteFor($entries[0]);
    quickVoteFor($entries[1]);
    $leaderboard = app(LeaderboardService::class);

    $supportBoard = collect($leaderboard->leaders('all_time', 'support'))->pluck('score', 'object_id');
    $communityBoard = collect($leaderboard->leaders('all_time', 'community'))->pluck('score', 'object_id');

    expect($supportBoard[$entries[0]->object_id])->toBe(6)
        ->and($supportBoard[$entries[1]->object_id])->toBe(1)
        ->and($communityBoard[$entries[0]->object_id])->toBe(1)
        ->and($communityBoard->has($entries[1]->object_id))->toBeFalse();

    $this->getJson(route('backend.rankings.index'))->assertOk()
        ->assertJsonPath('data.0.id', $post->id)
        ->assertJsonPath('data.0.supporters_count', 1)
        ->assertJsonPath('data.0.support_points', 7);
});

test('reversing a quick vote removes its score without refunding or creating a wallet', function () {
    $user = User::factory()->create();
    [, $entries] = rankingWithEntries(2);
    $event = quickVoteFor($entries[0], $user);

    app(SupportService::class)->reverse($event);

    expect($event->refresh()->status)->toBe('reversed')
        ->and(Wallet::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(PointLedger::query()->count())->toBe(0)
        ->and(collect(app(LeaderboardService::class)->leaders('all_time', 'support'))->pluck('object_id'))->not->toContain($entries[0]->object_id);
});
