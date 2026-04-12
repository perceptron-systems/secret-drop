<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proof-of-Work Difficulty (leading zero bits)
    |--------------------------------------------------------------------------
    |
    | Number of leading zero bits required in SHA-256(challenge || nonce).
    | 20 bits ≈ 1M iterations ≈ 1-2 seconds on a modern browser.
    | Lower values for testing (e.g. 8), higher for stronger protection.
    |
    */
    'difficulty' => (int) env('POW_DIFFICULTY', 20),

    /*
    |--------------------------------------------------------------------------
    | Challenge TTL (seconds)
    |--------------------------------------------------------------------------
    */
    'ttl_seconds' => (int) env('POW_TTL_SECONDS', 300),

];
