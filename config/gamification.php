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
    | Missions
    |--------------------------------------------------------------------------
    |
    | Each mission reserves its points once per period when its progress reaches
    | the target; members claim completed rewards to add them to the wallet.
    |
    | Daily: each Jakarta day assigns the "always" missions plus a rotating pick
    | from the pool, up to "slots". Finishing every assigned mission reserves
    | the "complete_bonus" on top of the daily cap.
    |
    | Weekly (Monday to Sunday, Jakarta): content counts toward the week it was
    | created in, only once it is published, and only if that happened within
    | "review_grace_days" after the week ended.
    |
    | Challenges: content created in a week earns the challenge from what other
    | members do within "window_days" of its publication. Only members whose
    | account was "min_account_age_days" old at the time count.
    |
    | Unclaimed rewards expire "claim_days" after their period closes.
    |
    */

    'missions' => [
        'daily' => [
            'slots' => 4,
            'cap' => 15,
            'always' => ['quick_vote'],
            'complete_bonus' => 5,
            'pool' => [
                'quick_vote' => ['target' => 1, 'points' => 3],
                'support' => ['target' => 1, 'points' => 3],
                'write_comments' => ['target' => 2, 'points' => 4],
                'like_comments' => ['target' => 3, 'points' => 3],
                'save_ranking' => ['target' => 1, 'points' => 2],
                'mention_friend' => ['target' => 1, 'points' => 3],
                'article_comment' => ['target' => 1, 'points' => 3],
            ],
        ],
        'weekly' => [
            'cap' => 60,
            'review_grace_days' => 3,
            'pool' => [
                'create_ranking' => ['target' => 1, 'points' => 10],
                'write_article' => ['target' => 1, 'points' => 15],
                'improve_summary' => ['target' => 1, 'points' => 10],
                'submit_object' => ['target' => 1, 'points' => 10],
                'daily_complete' => ['target' => 5, 'points' => 15],
            ],
        ],
        'challenges' => [
            'window_days' => 7,
            'min_account_age_days' => 3,
            'pool' => [
                'popular_ranking' => ['target' => 5, 'points' => 15],
                'discussed_ranking' => ['target' => 10, 'distinct' => 5, 'points' => 15],
                'read_article' => ['target' => 3, 'points' => 10],
                'liked_comment' => ['target' => 5, 'points' => 5],
            ],
        ],
        'claim_days' => 7,
    ],

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
