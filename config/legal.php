<?php

return [
    'editor_name' => env('LEGAL_EDITOR_NAME', 'Secret Drop'),
    'editor_address' => env('LEGAL_EDITOR_ADDRESS'),
    'editor_phone' => env('LEGAL_EDITOR_PHONE'),
    'contact_email' => env('LEGAL_CONTACT_EMAIL', 'contact@example.com'),

    'organization_name' => env('LEGAL_ORGANIZATION_NAME', 'Secret Drop'),

    'social' => [
        'github' => env('SOCIAL_GITHUB'),
        'twitter' => env('SOCIAL_TWITTER'),
        'linkedin' => env('SOCIAL_LINKEDIN'),
        'website' => env('SOCIAL_WEBSITE'),
    ],

    'hosting' => [
        'name' => env('LEGAL_HOSTING_NAME', 'Your Hosting Provider'),
        'address' => env('LEGAL_HOSTING_ADDRESS', '123 Hosting Street, City, Country'),
        'phone' => env('LEGAL_HOSTING_PHONE', '+00 0 00 00 00 00'),
    ],
];
