<?php

use App\Models\Category;
use App\Models\Comment;
use App\Models\MasterObject;
use App\Models\ObjectAlias;
use App\Models\PointLedger;
use App\Models\SupportEvent;
use App\Models\User;
use App\Models\Wallet;
use App\Services\LeaderboardService;
use App\Services\ObjectMatchingService;
use App\Services\RankingService;
use App\Services\SupportService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('alias search resolves one master and respects identity conflicts', function () {
    $category = Category::factory()->create();
    $object = MasterObject::factory()->create([
        'category_id' => $category->id,
        'canonical_name' => 'Samsung Galaxy A26',
        'normalized_name' => 'samsung galaxy a26',
        'identity_attributes' => ['model' => 'A26'],
    ]);
    ObjectAlias::factory()->create([
        'object_id' => $object->id,
        'category_id' => $category->id,
        'alias' => 'Samsung A26',
        'normalized_alias' => 'samsung a26',
    ]);

    $this->getJson(route('backend.objects.index', ['q' => 'Samsung A26', 'category_id' => $category->id]))
        ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $object->id);

    expect(app(ObjectMatchingService::class)->search('Samsung A26', $category->id, ['model' => 'A25']))->toBeEmpty();
});

test('same name with conflicting brand can be proposed separately', function () {
    $category = Category::factory()->create();
    $firstBrand = DB::table('brands')->insertGetId(['name' => 'Brand One', 'normalized_name' => 'brand one', 'created_at' => now(), 'updated_at' => now()]);
    $secondBrand = DB::table('brands')->insertGetId(['name' => 'Brand Two', 'normalized_name' => 'brand two', 'created_at' => now(), 'updated_at' => now()]);
    MasterObject::factory()->create([
        'category_id' => $category->id,
        'brand_id' => $firstBrand,
        'canonical_name' => 'Model X',
        'normalized_name' => 'model x',
    ]);

    $this->actingAs(User::factory()->create())->postJson(route('backend.objects.store'), [
        'canonical_name' => 'Model X',
        'category_id' => $category->id,
        'brand_id' => $secondBrand,
    ])->assertCreated()->assertJsonPath('status', 'pending');
});

test('publishing requires two distinct objects in the chosen category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $first = MasterObject::factory()->create(['category_id' => $category->id]);
    $second = MasterObject::factory()->create(['category_id' => $category->id]);
    $payload = [
        'title' => 'Which candidate is better?',
        'criteria' => 'Choose the strongest candidate overall.',
        'category_id' => $category->id,
        'language_code' => 'en',
        'region_code' => 'GLOBAL',
        'object_ids' => [$first->id, $second->id],
    ];

    $this->actingAs($user)->postJson(route('backend.rankings.store'), $payload)
        ->assertCreated()->assertJsonCount(2, 'entries');

    $this->actingAs($user)->postJson(route('backend.rankings.store'), [...$payload, 'object_ids' => [$first->id, $first->id]])
        ->assertUnprocessable()->assertJsonValidationErrors('object_ids.1');

    $this->getJson(route('backend.rankings.index', ['sort' => 'popular']))
        ->assertOk()->assertJsonPath('data.0.title', $payload['title']);
});

test('daily bonus follows Jakarta calendar and remains idempotent', function () {
    $user = User::factory()->create();
    $this->travelTo(CarbonImmutable::parse('2026-10-17 23:59:00', 'Asia/Jakarta')->utc());

    $this->actingAs($user)->postJson(route('backend.wallet.claim'))->assertOk()->assertJsonPath('balance', 5);
    $this->actingAs($user)->postJson(route('backend.wallet.claim'))->assertOk()->assertJsonPath('balance', 5);
    expect(PointLedger::query()->where('user_id', $user->id)->count())->toBe(1);

    $this->travelTo(CarbonImmutable::parse('2026-10-18 00:01:00', 'Asia/Jakarta')->utc());
    $this->actingAs($user)->postJson(route('backend.wallet.claim'))->assertOk()->assertJsonPath('balance', 10);
    expect(PointLedger::query()->where('user_id', $user->id)->count())->toBe(2);
});

test('unverified accounts cannot claim points', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->withHeader('X-Locale', 'id')->postJson(route('backend.wallet.claim'))
        ->assertUnprocessable()->assertJsonPath('errors.account.0', 'Akun harus terverifikasi dan tidak dibatasi.');
    $this->actingAs($user)->withHeader('X-Locale', 'en')->postJson(route('backend.wallet.claim'))
        ->assertUnprocessable()->assertJsonPath('errors.account.0', 'A verified, unrestricted account is required.');
    expect(PointLedger::query()->where('user_id', $user->id)->count())->toBe(0);
});

test('support is idempotent and cannot overdraw the wallet', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $objects = MasterObject::factory()->count(2)->create(['category_id' => $category->id]);
    $post = app(RankingService::class)->publish($user, [
        'title' => 'Two candidates for testing',
        'criteria' => 'Compare the same two candidates.',
        'category_id' => $category->id,
        'language_code' => 'id',
        'region_code' => 'GLOBAL',
        'object_ids' => $objects->modelKeys(),
    ]);
    app(SupportService::class)->claimDaily($user);
    $payload = ['ranking_entry_id' => $post->entries->first()->id, 'amount' => 4, 'idempotency_key' => (string) Str::uuid()];

    $this->actingAs($user)->postJson(route('backend.support.store'), $payload)->assertCreated();
    $this->actingAs($user)->postJson(route('backend.support.store'), $payload)->assertCreated();
    $this->actingAs($user)->postJson(route('backend.support.store'), [...$payload, 'idempotency_key' => (string) Str::uuid()])->assertUnprocessable();

    expect(SupportEvent::count())->toBe(1)
        ->and(Wallet::query()->where('user_id', $user->id)->value('balance'))->toBe(1)
        ->and(PointLedger::query()->where('type', 'support')->count())->toBe(1);
});

test('feed returns supporter count and top two candidates', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $objects = MasterObject::factory()->count(3)->create(['category_id' => $category->id]);
    $post = app(RankingService::class)->publish($user, [
        'title' => 'Three candidate comparison',
        'criteria' => 'Compare all three sample candidates.',
        'category_id' => $category->id,
        'language_code' => 'id',
        'region_code' => 'GLOBAL',
        'object_ids' => $objects->modelKeys(),
    ]);
    $support = app(SupportService::class);
    $support->claimDaily($user);
    $support->support($user, $post->entries->last(), 4, (string) Str::uuid());

    $this->getJson(route('backend.rankings.index'))->assertOk()
        ->assertJsonPath('data.0.supporters_count', 1)
        ->assertJsonPath('data.0.entries.0.object_id', $post->entries->last()->object_id)
        ->assertJsonCount(2, 'data.0.entries');
});

test('leaderboard counts unique supporters across posts and excludes reversed events', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $objects = MasterObject::factory()->count(2)->create(['category_id' => $category->id]);
    $rankings = app(RankingService::class);
    $payload = [
        'title' => 'The first comparison',
        'criteria' => 'Compare candidates for this context.',
        'category_id' => $category->id,
        'language_code' => 'id',
        'region_code' => 'GLOBAL',
        'object_ids' => $objects->modelKeys(),
    ];
    $first = $rankings->publish($user, $payload);
    $second = $rankings->publish($user, [...$payload, 'title' => 'The second comparison']);
    $support = app(SupportService::class);
    $support->claimDaily($user);
    $event = $support->support($user, $first->entries->first(), 2, (string) Str::uuid());
    $support->support($user, $second->entries->first(), 3, (string) Str::uuid());

    $leaders = app(LeaderboardService::class);
    expect($leaders->leaders('all_time', 'community')[0]['score'])->toBe(1)
        ->and($leaders->leaders('all_time', 'support')[0]['score'])->toBe(5);

    $support->reverse($event);
    expect($leaders->leaders('all_time', 'support')[0]['score'])->toBe(3)
        ->and(Wallet::query()->where('user_id', $user->id)->value('balance'))->toBe(2);
});

test('leaderboard uses Jakarta day and Monday week boundaries', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $objects = MasterObject::factory()->count(2)->create(['category_id' => $category->id]);
    $post = app(RankingService::class)->publish($user, [
        'title' => 'Boundary testing ranking',
        'criteria' => 'Compare candidates across the week.',
        'category_id' => $category->id,
        'language_code' => 'id',
        'region_code' => 'GLOBAL',
        'object_ids' => $objects->modelKeys(),
    ]);
    $support = app(SupportService::class);
    $this->travelTo(CarbonImmutable::parse('2026-10-18 23:59:00', 'Asia/Jakarta')->utc());
    $support->claimDaily($user);
    $support->support($user, $post->entries->first(), 2, (string) Str::uuid());

    $this->travelTo(CarbonImmutable::parse('2026-10-19 00:01:00', 'Asia/Jakarta')->utc());
    $support->claimDaily($user);
    $support->support($user, $post->entries->first(), 3, (string) Str::uuid());

    $leaders = app(LeaderboardService::class);
    expect($leaders->leaders('daily', 'support')[0]['score'])->toBe(3)
        ->and($leaders->leaders('weekly', 'support')[0]['score'])->toBe(3)
        ->and($leaders->leaders('all_time', 'support')[0]['score'])->toBe(5);
});

test('moderation routes reject ordinary users and audit reviews', function () {
    $category = Category::factory()->create();
    $object = MasterObject::factory()->pending()->create(['category_id' => $category->id]);
    $user = User::factory()->create();
    $moderator = User::factory()->create(['role' => 'moderator']);
    $route = route('admin.backend.objects.review', $object);
    $payload = ['status' => 'verified', 'reason' => 'Identity confirmed by moderator.'];

    $this->actingAs($user)->patchJson($route, $payload)->assertForbidden();
    $this->actingAs($moderator)->patchJson($route, $payload)->assertOk()->assertJsonPath('status', 'verified');
    $this->actingAs($moderator)->putJson(route('admin.backend.objects.translations.store', $object), [
        'locale' => 'en',
        'name' => 'Verified object',
        'reason' => 'Localized name reviewed.',
    ])->assertOk()->assertJsonPath('name', 'Verified object');
    $this->getJson(route('backend.objects.show', $object->slug))->assertJsonPath('translations.0.name', 'Verified object');
    $this->assertDatabaseHas('audit_logs', ['action' => 'object.review', 'target_id' => $object->id]);
});

test('merge redirects scores to one master and undo restores original attribution', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $moderator = User::factory()->create(['role' => 'moderator']);
    $category = Category::factory()->create();
    $source = MasterObject::factory()->create(['category_id' => $category->id]);
    $target = MasterObject::factory()->create(['category_id' => $category->id]);
    $post = app(RankingService::class)->publish($user, [
        'title' => 'A comparison before merging',
        'criteria' => 'Compare these two master objects.',
        'category_id' => $category->id,
        'language_code' => 'en',
        'region_code' => 'GLOBAL',
        'object_ids' => [$source->id, $target->id],
    ]);
    $support = app(SupportService::class);
    $support->claimDaily($user);
    $support->support($user, $post->entries->first(), 2, (string) Str::uuid());

    $this->actingAs($moderator)->postJson(route('admin.backend.objects.merge', $source), [
        'target_id' => $target->id,
        'reason' => 'Confirmed duplicate identity.',
    ])->assertForbidden();

    $this->actingAs($admin)->postJson(route('admin.backend.objects.merge', $source), [
        'target_id' => $target->id,
        'reason' => 'Confirmed duplicate identity.',
    ])->assertOk()->assertJsonPath('status', 'merged');
    $this->get(route('backend.objects.show', $source->slug))->assertRedirect(route('backend.objects.show', $target->slug));
    expect(app(LeaderboardService::class)->leaders('all_time', 'support')[0]['object_id'])->toBe($target->id);

    $this->actingAs($admin)->postJson(route('admin.backend.objects.undo-merge', $source), [
        'reason' => 'The source is a distinct variant.',
    ])->assertOk()->assertJsonPath('status', 'verified');
    expect(app(LeaderboardService::class)->leaders('all_time', 'support')[0]['object_id'])->toBe($source->id);
    $this->assertDatabaseHas('audit_logs', ['action' => 'object.merge.undo', 'target_id' => $source->id]);
});

test('unverified objects cannot appear in global leaderboard', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();
    $objects = MasterObject::factory()->count(2)->create(['category_id' => $category->id]);
    $post = app(RankingService::class)->publish($user, [
        'title' => 'Ranking before object rejection',
        'criteria' => 'Compare two candidates before review.',
        'category_id' => $category->id,
        'language_code' => 'id',
        'region_code' => 'GLOBAL',
        'object_ids' => $objects->modelKeys(),
    ]);
    $support = app(SupportService::class);
    $support->claimDaily($user);
    $support->support($user, $post->entries->first(), 2, (string) Str::uuid());
    $objects->first()->update(['status' => 'rejected']);

    expect(app(LeaderboardService::class)->leaders('all_time', 'support'))->toBeEmpty();
});

test('closing an account keeps its point ledger for audit', function () {
    $user = User::factory()->create();
    app(SupportService::class)->claimDaily($user);

    $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');

    expect(User::find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id))->not->toBeNull()
        ->and(PointLedger::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('role bootstrap command records an audit event', function () {
    $user = User::factory()->create();

    expect(Artisan::call('votmee:grant-role', ['email' => $user->email, 'role' => 'admin']))->toBe(0)
        ->and($user->refresh()->role)->toBe('admin');
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.role.grant', 'target_id' => (string) $user->id]);
});

test('only administrators can create categories and brands', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($user)->postJson(route('admin.backend.categories.store'), ['name' => 'Technology'])->assertForbidden();
    $this->actingAs($admin)->postJson(route('admin.backend.categories.store'), ['name' => 'Technology'])
        ->assertCreated()->assertJsonPath('slug', 'technology');
    $this->actingAs($admin)->postJson(route('admin.backend.brands.store'), ['name' => 'Example Brand'])
        ->assertCreated()->assertJsonPath('normalized_name', 'example brand');
    $this->getJson(route('backend.categories.index'))->assertOk()->assertJsonFragment(['slug' => 'technology']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'category.create']);
});

test('moderators can hide comments while audit and ledger views remain admin only', function () {
    $moderator = User::factory()->create(['role' => 'moderator']);
    $admin = User::factory()->create(['role' => 'admin']);
    $comment = Comment::factory()->create();

    $this->actingAs($moderator)->patchJson(route('admin.backend.comments.hide', $comment), [
        'reason' => 'The comment violates community rules.',
    ])->assertOk()->assertJsonPath('status', 'hidden');
    $this->actingAs($moderator)->getJson(route('admin.backend.audit-logs.index'))->assertForbidden();
    $this->actingAs($moderator)->getJson(route('admin.backend.ledger.index'))->assertForbidden();
    $this->actingAs($admin)->getJson(route('admin.backend.audit-logs.index'))
        ->assertOk()->assertJsonPath('data.0.action', 'comment.hide');
});
