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

    /*
    |--------------------------------------------------------------------------
    | Achievements
    |--------------------------------------------------------------------------
    |
    | Each category unlocks a bronze, silver and gold badge as its count
    | reaches the listed thresholds. A badge pays its tier points once, on
    | top of the daily mission cap. Keys read "{category}_{tier}".
    |
    */

    'achievements' => [
        'streak' => ['bronze' => 7, 'silver' => 30, 'gold' => 100],
        'curator' => ['bronze' => 1, 'silver' => 10, 'gold' => 50],
        'writer' => ['bronze' => 5, 'silver' => 25, 'gold' => 100],
        'voice' => ['bronze' => 10, 'silver' => 50, 'gold' => 200],
        'guardian' => ['bronze' => 5, 'silver' => 20, 'gold' => 50],
    ],

    'achievement_points' => [
        'bronze' => 5,
        'silver' => 10,
        'gold' => 20,
    ],

];
