<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Secret Expiration Options
    |--------------------------------------------------------------------------
    |
    | Each key is the value used in forms and validation.
    | Each value is the number of hours until expiration.
    | The translation key is derived automatically: messages.expiration_{key}
    |
    */
    'expirations' => [
        '1h' => 1,
        '1d' => 24,
        '7d' => 168,
        '30d' => 720,
        '90d' => 2160,
    ],

    'default_expiration' => '7d',

    /*
    |--------------------------------------------------------------------------
    | Session & Magic Link Durations (minutes)
    |--------------------------------------------------------------------------
    */
    'magic_link_ttl' => (int) env('MAGIC_LINK_TTL', 10),
    'session_ttl' => (int) env('SESSION_TTL', 15),
];
