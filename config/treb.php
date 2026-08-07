<?php

return [
    'auth' => env('TRREB_AUTH', env('TREB_AUTH')),
    'auth1' => env('TRREB_AUTH1', env('TREB_AUTH1')),

    // Isolated 14-year Archive sold feed (never reuse auth / auth1).
    'auth2' => env('TRREB_AUTH2', env('TREB_AUTH2')),
    'archive_odata_url' => env('TREB_ARCHIVE_ODATA_URL', 'https://query.ampre.ca/odata/Property'),
];
