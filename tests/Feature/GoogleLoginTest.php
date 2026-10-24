<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.google.client_id', 'google-client-id');
    config()->set('services.google.client_secret', 'google-client-secret');
    config()->set('services.google.redirect', null);
});

function googleOAuthSession(string $locale = 'id', ?string $returnTo = null): array
{
    return ['google_oauth' => [
        'state' => 'valid-state',
        'code_verifier' => 'valid-code-verifier',
        'locale' => $locale,
        'started_at' => now()->timestamp,
        'return_to' => $returnTo,
    ]];
}

function fakeGoogleProfile(array $profile): void
{
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-access-token']),
        'https://openidconnect.googleapis.com/v1/userinfo' => Http::response($profile),
    ]);
}

test('google redirect includes a session state and PKCE challenge', function () {
    $response = $this->get(route('community.google.redirect', ['locale' => 'en', 'return_to' => '/en/explore']));

    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

    $response->assertRedirectContains('https://accounts.google.com/o/oauth2/v2/auth');
    expect($query['client_id'])->toBe('google-client-id')
        ->and($query['scope'])->toBe('openid email profile')
        ->and($query['code_challenge_method'])->toBe('S256')
        ->and($query['redirect_uri'])->toBe(route('google.callback'))
        ->and($query['state'])->toBe(session('google_oauth.state'))
        ->and(session('google_oauth.return_to'))->toBe('/en/explore');
});

test('google redirect reports missing credentials', function () {
    config()->set('services.google.client_id', null);

    $this->get(route('community.google.redirect', ['locale' => 'id']))
        ->assertRedirect(route('community.login', ['locale' => 'id']))
        ->assertSessionHas('google_auth_error');
});

test('google callback rejects invalid or expired state before contacting Google', function () {
    Http::fake();

    $this->withSession(googleOAuthSession())->get(route('google.callback', ['state' => 'wrong-state', 'code' => 'auth-code']))
        ->assertRedirect(route('community.login', ['locale' => 'id']))
        ->assertSessionHas('google_auth_error');

    $expired = googleOAuthSession();
    $expired['google_oauth']['started_at'] = now()->subMinutes(11)->timestamp;

    $this->withSession($expired)->get(route('google.callback', ['state' => 'valid-state', 'code' => 'auth-code']))
        ->assertRedirect(route('community.login', ['locale' => 'id']));

    Http::assertNothingSent();
    $this->assertGuest();
});

test('google callback creates and verifies a new user', function () {
    fakeGoogleProfile([
        'sub' => 'google-subject-1',
        'email' => 'new@example.com',
        'email_verified' => true,
        'name' => 'New Voter',
    ]);

    $this->withSession(googleOAuthSession('en', '/en/explore'))
        ->get(route('google.callback', ['state' => 'valid-state', 'code' => 'auth-code']))
        ->assertRedirect(route('community.username.create', ['locale' => 'en']));

    $user = User::where('email', 'new@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    expect($user->google_id)->toBe('google-subject-1')
        ->and($user->username)->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->locale)->toBe('en')
        ->and($user->hasRole('user'))->toBeTrue();

    Http::assertSent(fn ($request) => $request->url() === 'https://oauth2.googleapis.com/token'
        && $request['code_verifier'] === 'valid-code-verifier');

    $this->get(route('community.username.create', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/ChooseUsername')
            ->where('suggestedUsername', 'new-voter')
            ->etc());

    $this->post(route('community.username.store', ['locale' => 'en']), ['username' => 'my-voter-name'])
        ->assertRedirect('/en/explore');

    expect($user->fresh()->username)->toBe('my-voter-name');
});

test('google signup suggests a numbered username when the name is taken', function () {
    User::factory()->create(['username' => 'new-voter']);

    fakeGoogleProfile([
        'sub' => 'google-subject-7',
        'email' => 'another@example.com',
        'email_verified' => true,
        'name' => 'New Voter',
    ]);

    $this->withSession(googleOAuthSession())
        ->get(route('google.callback', ['state' => 'valid-state', 'code' => 'auth-code']))
        ->assertRedirect(route('community.username.create', ['locale' => 'id']));

    $user = User::where('email', 'another@example.com')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    $this->get(route('community.username.create', ['locale' => 'id']))
        ->assertInertia(fn ($page) => $page
            ->component('Auth/ChooseUsername')
            ->where('suggestedUsername', 'new-voter-2')
            ->etc());

    $this->post(route('community.username.store', ['locale' => 'id']), ['username' => 'new-voter'])
        ->assertSessionHasErrors('username');

    $this->post(route('community.username.store', ['locale' => 'id']), ['username' => 'new-voter-2'])
        ->assertRedirect(route('community.home', ['locale' => 'id']));

    expect($user->fresh()->username)->toBe('new-voter-2');
});

test('a Google account without a username must choose one before using the site', function () {
    $user = User::factory()->create(['username' => null]);

    $this->actingAs($user)->get(route('community.home', ['locale' => 'id']))
        ->assertRedirect(route('community.username.create', ['locale' => $user->locale]));

    $this->post(route('community.username.store', ['locale' => 'id']), ['username' => 'chosen-name'])
        ->assertRedirect(route('community.home', ['locale' => 'id']));

    $this->post(route('community.username.store', ['locale' => 'id']), ['username' => 'other-name'])
        ->assertRedirect(route('community.home', ['locale' => 'id']));

    expect($user->fresh()->username)->toBe('chosen-name');
});

test('google callback links an existing Gmail account', function () {
    $user = User::factory()->unverified()->create(['email' => 'voter@gmail.com']);
    fakeGoogleProfile([
        'sub' => 'google-subject-2',
        'email' => 'voter@gmail.com',
        'email_verified' => true,
        'name' => 'Voter',
    ]);

    $this->withSession(googleOAuthSession())->get(route('google.callback', ['state' => 'valid-state', 'code' => 'auth-code']))
        ->assertRedirect(route('community.home', ['locale' => 'id']));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->google_id)->toBe('google-subject-2')
        ->and($user->fresh()->email_verified_at)->not->toBeNull()
        ->and(User::count())->toBe(1);
});

test('google callback signs in an account already linked by Google subject', function () {
    $user = User::factory()->create(['google_id' => 'google-subject-5', 'email' => 'old@example.com']);
    fakeGoogleProfile([
        'sub' => 'google-subject-5',
        'email' => 'updated@example.com',
        'email_verified' => true,
        'name' => 'Voter',
    ]);

    $this->withSession(googleOAuthSession())->get(route('google.callback', ['state' => 'valid-state', 'code' => 'auth-code']))
        ->assertRedirect(route('community.home', ['locale' => 'id']));

    $this->assertAuthenticatedAs($user);
    expect(User::count())->toBe(1);
});

test('google callback refuses to claim an existing account with a non-authoritative email', function () {
    $user = User::factory()->create(['email' => 'voter@example.com']);
    fakeGoogleProfile([
        'sub' => 'google-subject-3',
        'email' => 'voter@example.com',
        'email_verified' => true,
        'name' => 'Voter',
    ]);

    $this->withSession(googleOAuthSession())->get(route('google.callback', ['state' => 'valid-state', 'code' => 'auth-code']))
        ->assertRedirect(route('community.login', ['locale' => 'id']))
        ->assertSessionHas('google_auth_error');

    $this->assertGuest();
    expect($user->fresh()->google_id)->toBeNull();
});

test('google callback refuses an unverified Google email', function () {
    fakeGoogleProfile([
        'sub' => 'google-subject-4',
        'email' => 'unverified@example.com',
        'email_verified' => false,
        'name' => 'Unverified',
    ]);

    $this->withSession(googleOAuthSession())->get(route('google.callback', ['state' => 'valid-state', 'code' => 'auth-code']))
        ->assertRedirect(route('community.login', ['locale' => 'id']))
        ->assertSessionHas('google_auth_error');

    $this->assertGuest();
    expect(User::count())->toBe(0);
});

test('google callback does not revive a deleted account', function () {
    $user = User::factory()->create(['google_id' => 'google-subject-6']);
    $user->delete();
    fakeGoogleProfile([
        'sub' => 'google-subject-6',
        'email' => $user->email,
        'email_verified' => true,
        'name' => $user->name,
    ]);

    $this->withSession(googleOAuthSession())->get(route('google.callback', ['state' => 'valid-state', 'code' => 'auth-code']))
        ->assertRedirect(route('community.login', ['locale' => 'id']))
        ->assertSessionHas('google_auth_error');

    $this->assertGuest();
    expect(User::withTrashed()->count())->toBe(1);
});

test('google callback handles token exchange failure', function () {
    Http::fake(['https://oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);

    $this->withSession(googleOAuthSession())->get(route('google.callback', ['state' => 'valid-state', 'code' => 'invalid-code']))
        ->assertRedirect(route('community.login', ['locale' => 'id']))
        ->assertSessionHas('google_auth_error');

    $this->assertGuest();
});
