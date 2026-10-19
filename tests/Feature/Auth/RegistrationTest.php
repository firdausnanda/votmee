<?php

use Inertia\Testing\AssertableInertia as Assert;

test('registration links open a modal over the localized feed', function () {
    $this->withHeader('Accept-Language', 'id')->get('/register')
        ->assertRedirect(route('community.register', ['locale' => 'id']));

    foreach (['id', 'en'] as $locale) {
        $this->get('/'.$locale.'/register')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Community/Feed')
            ->where('locale', $locale)
            ->where('registerModal', true)
            ->where('loginModal', false)
            ->etc());
    }
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration returns to the page behind the modal and rejects external destinations', function () {
    $this->post('/register?return_to=%2Fen%2Fexplore%3Fq%3Dphone', [
        'name' => 'Test User',
        'email' => 'first@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect('/en/explore?q=phone');

    $this->post('/logout');

    $this->post('/register?return_to=%2F%2Fevil.example', [
        'name' => 'Second User',
        'email' => 'second@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));
});

test('registration errors use the selected language', function () {
    $this->from('/id/register')->post('/register?locale=id', [])
        ->assertRedirect('/id/register')
        ->assertSessionHasErrors(['name', 'email', 'password']);

    expect(session('errors')->first('name'))->toBe('Nama wajib diisi.');
});
