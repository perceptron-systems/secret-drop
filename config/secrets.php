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
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'daily_limit_per_ip' => (int) env('SECRETS_DAILY_LIMIT_PER_IP', 50),

    /*
    |--------------------------------------------------------------------------
    | File Storage Quota (MB) — 0 for unlimited
    |--------------------------------------------------------------------------
    */
    'file_storage_quota_mb' => (int) env('SECRETS_FILE_STORAGE_QUOTA_MB', 5120),

    /*
    |--------------------------------------------------------------------------
    | Session & Magic Link Durations (minutes)
    |--------------------------------------------------------------------------
    */
    'magic_link_ttl' => (int) env('MAGIC_LINK_TTL', 10),
    'admin_session_ttl' => (int) env('ADMIN_SESSION_TTL', 15),
    'super_admin_session_ttl' => (int) env('SUPER_ADMIN_SESSION_TTL', 15),

    /*
    |--------------------------------------------------------------------------
    | Email Hash Pepper
    |--------------------------------------------------------------------------
    |
    | Used as HMAC key when hashing creator emails. Without a pepper, a leaked
    | DB allows trivial bruteforce of known email lists against unsalted SHA-256.
    | Defaults to a value derived from APP_KEY (so zero-config still works).
    | Override only if you want to rotate the pepper independently of APP_KEY.
    |
    */
    'email_hash_pepper' => env('EMAIL_HASH_PEPPER')
        ?: hash('sha256', 'secret-drop:email-hash-pepper:'.env('APP_KEY', '')),
];
