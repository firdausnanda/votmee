<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('login links open a modal over the localized feed', function () {
    $this->withHeader('Accept-Language', 'id')->get('/login')
        ->assertRedirect(route('community.login', ['locale' => 'id']));

    foreach (['id', 'en'] as $locale) {
        $this->get('/'.$locale.'/login')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Community/Feed')
            ->where('locale', $locale)
            ->where('loginModal', true)
            ->etc());
    }

    $this->withSession(['google_auth_error' => 'Google sign-in failed.'])
        ->get('/en/login')->assertInertia(fn (Assert $page) => $page
        ->component('Community/Feed')
        ->where('googleAuthError', 'Google sign-in failed.')
        ->etc());
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('login returns to the page behind the modal and rejects external destinations', function () {
    $user = User::factory()->create();

    $this->post('/login?return_to=%2Fen%2Fexplore%3Fq%3Dphone', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/en/explore?q=phone');

    $this->post('/logout');

    $this->post('/login?return_to=%2F%2Fevil.example', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
