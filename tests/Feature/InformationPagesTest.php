<?php

use Inertia\Testing\AssertableInertia as Assert;

test('information pages render in both languages and are indexable', function (string $path, string $component, string $title) {
    config(['app.support_email' => 'help@example.test']);

    $this->get('/en'.$path)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component($component)
        ->where('locale', 'en')
        ->where('supportEmail', 'help@example.test')
        ->where('seo.title', $title)
        ->where('seo.robots', 'index, follow')
        ->where('seo.alternates.id', app('url')->to('/id'.$path))
        ->etc());

    $this->get('/id'.$path)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component($component)
        ->where('locale', 'id')
        ->etc());
})->with([
    'privacy' => ['/privacy', 'Community/Privacy', 'Privacy Policy'],
    'terms' => ['/terms', 'Community/Terms', 'User Agreement'],
    'support' => ['/support', 'Community/Support', 'Help Center'],
]);

test('information pages are listed in the static sitemap', function () {
    $this->get('/sitemaps/static.xml')->assertOk()
        ->assertSee('/id/privacy')
        ->assertSee('/en/terms')
        ->assertSee('/id/support');
});
