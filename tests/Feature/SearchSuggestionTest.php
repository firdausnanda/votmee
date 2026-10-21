<?php

use App\Models\MasterObject;
use App\Models\RankingPost;
use App\Models\User;

test('search suggestions return matching published rankings and objects', function () {
    $published = RankingPost::factory()->create(['title' => 'Smartphone Gaming Terbaik']);
    RankingPost::factory()->create(['title' => 'Smartphone Draft', 'status' => 'draft']);
    RankingPost::factory()->create(['title' => 'Kuliner Nusantara']);
    $object = MasterObject::factory()->create(['canonical_name' => 'Smartwatch Pro', 'normalized_name' => 'smartwatch pro']);

    $this->getJson('/backend/search?q=smart')
        ->assertOk()
        ->assertJsonCount(1, 'rankings')
        ->assertJsonPath('rankings.0.slug', $published->slug)
        ->assertJsonPath('rankings.0.category', $published->category->name)
        ->assertJsonCount(1, 'objects')
        ->assertJsonPath('objects.0.slug', $object->slug);
});

test('search suggestions treat like wildcards literally', function () {
    RankingPost::factory()->create(['title' => 'Diskon 100% Hari Ini']);
    RankingPost::factory()->create(['title' => 'Diskon besar hari ini']);

    $this->getJson('/backend/search?q='.urlencode('100%'))
        ->assertOk()
        ->assertJsonCount(1, 'rankings');
});

test('search suggestions require a query of at least two characters', function () {
    $this->getJson('/backend/search?q=a')->assertUnprocessable()->assertJsonValidationErrors('q');
});

test('search suggestions find users by name or @username', function () {
    $prefixMatch = User::factory()->create(['name' => 'Galih Pratama', 'username' => 'galihpratama']);
    $innerMatch = User::factory()->create(['name' => 'Rina Sari', 'username' => 'rina_galih']);
    User::factory()->create(['name' => 'Budi Santoso', 'username' => 'budisantoso']);
    User::factory()->create(['name' => 'Galih Tanpa Handle', 'username' => null]);
    User::factory()->create(['name' => 'Galih Terhapus', 'username' => 'galihterhapus'])->delete();

    $this->getJson('/backend/search?q=galih')
        ->assertOk()
        ->assertJsonCount(2, 'users')
        ->assertJsonPath('users.0.username', $prefixMatch->username)
        ->assertJsonPath('users.1.username', $innerMatch->username)
        ->assertJsonMissingPath('users.0.email');

    RankingPost::factory()->create(['title' => 'Budi daya ikan terbaik']);

    $this->getJson('/backend/search?q=@budi')
        ->assertOk()
        ->assertJsonCount(0, 'rankings')
        ->assertJsonCount(1, 'users')
        ->assertJsonPath('users.0.username', 'budisantoso');
});
