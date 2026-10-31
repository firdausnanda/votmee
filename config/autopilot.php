<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Automatic Rankings
    |--------------------------------------------------------------------------
    |
    | When members stop publishing rankings, the official Votmee account fills
    | the feed with generated rankings so Quick Duel and the feed stay alive.
    | Only rankings by regular members count towards activity, so generated
    | posts never make a quiet community look busy.
    |
    | "auto_publish" false sends every generated ranking to the admin queue.
    |
    */

    'rankings' => [
        'enabled' => (bool) env('AUTO_RANKINGS_ENABLED', true),
        'quiet_days' => 3,
        'daily_max' => 3,
        'auto_publish' => (bool) env('AUTO_RANKINGS_AUTO_PUBLISH', false),
        'max_pending' => 10,
        'category_cooldown_days' => 7,
        'duplicate_window_days' => 30,
        'min_entries' => 5,
        'max_entries' => 10,
        'candidate_limit' => 40,
        'language_code' => 'id',
        'region_code' => 'GLOBAL',
    ],

    /*
    |--------------------------------------------------------------------------
    | Official Account
    |--------------------------------------------------------------------------
    */

    'official_account' => [
        'email' => 'official@votmee.com',
        'name' => 'Votmee Official',
        'username' => 'votmee',
    ],

];
