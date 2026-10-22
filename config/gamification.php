<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Daily Streak
    |--------------------------------------------------------------------------
    |
    | Claiming the daily bonus on consecutive Jakarta days builds a streak.
    | Every completed cycle pays a one-off bonus on top of the daily bonus.
    |
    */

    'streak' => [
        'cycle_days' => 7,
        'bonus' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Daily Missions
    |--------------------------------------------------------------------------
    |
    | Each mission pays its points once per Jakarta day. The cap limits the
    | total mission points a user can earn in one day, whatever is listed.
    |
    */

    'missions' => [
        'quick_vote' => 3,
        'support' => 3,
        'comment_liked' => 4,
    ],

    'daily_mission_cap' => 10,

];
