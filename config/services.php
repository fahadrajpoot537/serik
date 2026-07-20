<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // OpenStreetMap Nominatim geocoder (free). Respect the usage policy:
    // max ~1 request/second and a descriptive, contactable User-Agent.
    // https://operations.osmfoundation.org/policies/nominatim/
    'nominatim' => [
        'url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org/search'),
        'user_agent' => env('NOMINATIM_USER_AGENT', 'SerikRealEstate/1.0 (+https://serik.ca; admin@serik.ca)'),
        'email' => env('NOMINATIM_EMAIL', ''),
        'rate_limit_ms' => (int) env('NOMINATIM_RATE_LIMIT_MS', 1100),
    ],

    'google_maps' => [
        'geocoding_api_key' => env('GOOGLE_MAPS_GEOCODING_API_KEY'),
        'geocoding_url' => env('GOOGLE_MAPS_GEOCODING_URL', 'https://maps.googleapis.com/maps/api/geocode/json'),
        'rate_per_minute' => (int) env('GOOGLE_MAPS_GEOCODING_RATE_PER_MINUTE', 50),
        'delay_ms' => (int) env('GOOGLE_MAPS_GEOCODING_DELAY_MS', 50),
    ],

];
