<?php

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

test('missing pages render the error page in the requested locale', function () {
    $this->get('/en/this-page-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 404)
            ->where('locale', 'en'));

    $this->get('/missing-without-locale')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'id'));
});

test('forbidden pages render the error page', function () {
    Route::get('/id/test-forbidden', fn () => abort(403));

    $this->get('/id/test-forbidden')
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 403));
});

test('server errors render the error page only when debug is off', function () {
    Route::get('/id/test-crash', fn () => throw new RuntimeException('Boom'));

    config(['app.debug' => false]);

    $this->get('/id/test-crash')
        ->assertServerError()
        ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 500));

    config(['app.debug' => true]);

    $this->get('/id/test-crash')
        ->assertServerError()
        ->assertSee('Boom');
});

test('maintenance mode renders the error page with its retry header', function () {
    $this->app->maintenanceMode()->activate(['retry' => 60]);

    $this->get('/id')
        ->assertServiceUnavailable()
        ->assertHeader('Retry-After', '60')
        ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 503));

    $this->app->maintenanceMode()->deactivate();
});

test('json requests keep their json error responses', function () {
    $this->getJson('/en/this-page-does-not-exist')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});
