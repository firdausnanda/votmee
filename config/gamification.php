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
        'reminder_min_days' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Daily Missions
    |--------------------------------------------------------------------------
    |
    | Each mission reserves points once per Jakarta day. Members claim completed
    | rewards to add them to the wallet. The cap limits each day's total.
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

    /*
    |--------------------------------------------------------------------------
    | Seasonal Achievements
    |--------------------------------------------------------------------------
    |
    | One badge per category each Jakarta month, earned by reaching the count
    | within that month only. Keys read "season_{YYYY}_{MM}_{category}".
    |
    */

    'seasonal' => [
        'curator' => 3,
        'writer' => 5,
        'voice' => 20,
    ],

    'seasonal_points' => 10,

    /*
    |--------------------------------------------------------------------------
    | Hidden Achievements
    |--------------------------------------------------------------------------
    |
    | Surprise badges that stay out of view until unlocked, with the points
    | each one pays. Keys read "hidden_{name}".
    |
    */

    'hidden' => [
        'viral_comment' => 15,
        'perfect_day' => 15,
        'night_owl' => 15,
    ],

    'viral_comment_likes' => 100,

    /*
    |--------------------------------------------------------------------------
    | Trusted Contributor
    |--------------------------------------------------------------------------
    |
    | Unlocking this badge makes a member a trusted contributor, whose content
    | revisions publish without review, unless a moderator revoked it before.
    |
    */

    'trusted_badge' => 'writer_silver',

    'hall_of_fame_size' => 10,

];
