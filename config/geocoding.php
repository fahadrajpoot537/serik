<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Geocoding Provider
    |--------------------------------------------------------------------------
    |
    | Supported: "google", "mapbox" (HERE / OSM can be added later)
    |
    */

    'default' => env('GEOCODING_PROVIDER', 'mapbox'),

    'providers' => [

        'google' => [
            'key' => env('GOOGLE_MAPS_GEOCODING_API_KEY'),
            'url' => env('GOOGLE_MAPS_GEOCODING_URL', 'https://maps.googleapis.com/maps/api/geocode/json'),
            'rate_per_minute' => (int) env('GOOGLE_MAPS_GEOCODING_RATE_PER_MINUTE', 50),
            'delay_ms' => (int) env('GOOGLE_MAPS_GEOCODING_DELAY_MS', 50),
        ],

        'mapbox' => [
            'token' => env('MAPBOX_ACCESS_TOKEN'),
            'url' => env('MAPBOX_GEOCODING_URL', 'https://api.mapbox.com/geocoding/v5/mapbox.places'),
            'rate_per_minute' => (int) env('MAPBOX_GEOCODING_RATE_PER_MINUTE', 60),
            'delay_ms' => (int) env('MAPBOX_GEOCODING_DELAY_MS', 20),
        ],

    ],

];
