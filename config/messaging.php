<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Private Messages
    |--------------------------------------------------------------------------
    |
    | A new thread starts as a request: the initiator may send only a few
    | messages until the recipient accepts, which a reply also does. Limits
    | below are per member.
    |
    */

    'body_max' => 2000,

    'pending_message_limit' => 1,

    'new_conversations_per_day' => 10,

    'messages_per_minute' => 20,

];
