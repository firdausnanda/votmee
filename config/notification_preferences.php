<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification Preference Groups
    |--------------------------------------------------------------------------
    |
    | Members switch in-app notifications on or off per group. Every
    | notification type belongs to exactly one group; a type missing here is
    | always delivered.
    |
    */

    'groups' => ['replies', 'likes', 'messages', 'badges', 'reviews', 'streak'],

    'types' => [
        'comment_reply' => 'replies',
        'ranking_comment' => 'replies',
        'comment_liked' => 'likes',
        'message_request' => 'messages',
        'achievement_unlocked' => 'badges',
        'trust_granted' => 'badges',
        'content_reviewed' => 'reviews',
        'object_reviewed' => 'reviews',
        'report_resolved' => 'reviews',
        'streak_reminder' => 'streak',
    ],

];
