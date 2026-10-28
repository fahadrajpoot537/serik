<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deterministic home valuation (sold comps) — Groq explains only
    |--------------------------------------------------------------------------
    */

    'min_sold_price' => 50_000,
    'min_comps' => 3,
    'max_comps_used' => 12,
    'max_comps_valuation_pool' => 36,
    'candidate_limit' => 280,

    'radius_km_steps' => [1.25, 2.0, 3.5, 6.0],
    'months_steps' => [12, 18],

    'bed_tolerance' => 1,
    'sqft_tolerance_ratio' => 0.35,
    'sqft_tolerance_ratio_small' => 0.28,

    'cache_ttl_seconds' => 180,

    'disclaimer' => 'This is an automated market estimate based on available MLS sold and listing data. It is not a certified appraisal, and individual sale prices can differ materially. Use it only as a starting point.',

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'endpoint' => env('GROQ_API_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions'),
        'timeout_seconds' => (float) env('GROQ_TIMEOUT', 8),
        'connect_timeout_seconds' => (float) env('GROQ_CONNECT_TIMEOUT', 3),
        'max_tokens' => 420,
        'temperature' => 0.2,
    ],
];
