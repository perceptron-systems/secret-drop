<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Restricted to the application's own origin only.
    | Cross-origin API calls from other domains are blocked.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST'],

    'allowed_origins' => [env('APP_URL', 'http://localhost')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Accept', 'X-CSRF-TOKEN', 'X-Captcha-Token', 'X-Captcha-Answer'],

    'exposed_headers' => ['Retry-After', 'X-Captcha-Required'],

    'max_age' => 3600,

    'supports_credentials' => false,

];
