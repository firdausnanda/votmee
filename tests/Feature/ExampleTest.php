<?php

it('redirects the root URL to the canonical Indonesian home', function () {
    $response = $this->get('/');

    $response->assertRedirect('/id')->assertStatus(301);
});
