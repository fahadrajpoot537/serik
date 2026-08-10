<?php

return [
    'auth' => env('TRREB_AUTH', env('TREB_AUTH')),
    'auth1' => env('TRREB_AUTH1', env('TREB_AUTH1')),

    // Isolated 14-year Archive sold feed (never reuse auth / auth1).
    'auth2' => env('TRREB_AUTH2', env('TREB_AUTH2')),
    'archive_odata_url' => env('TREB_ARCHIVE_ODATA_URL', 'https://query.ampre.ca/odata/Property'),

    /*
    |--------------------------------------------------------------------------
    | Enterprise sold-history archive importer (AUTH2)
    |--------------------------------------------------------------------------
    | Scheduler only DISPATCHES ProcessTrebArchiveImportJob onto the imports
    | queue. Actual AMP fetch + bulk upsert runs in workers.
    */
    'archive' => [
        // OData $top per API page (10–500).
        'chunk_size' => (int) env('TREB_ARCHIVE_CHUNK_SIZE', 200),
        'adaptive_chunk' => filter_var(env('TREB_ARCHIVE_ADAPTIVE_CHUNK', true), FILTER_VALIDATE_BOOL),
        'upsert_chunk' => (int) env('TREB_ARCHIVE_UPSERT_CHUNK', 250),

        // Max AMP pages processed inside one queue job.
        'pages_per_job' => (int) env('TREB_ARCHIVE_PAGES_PER_JOB', 15),

        // Soft time budget (seconds) per job before yielding.
        'max_seconds_per_job' => (int) env('TREB_ARCHIVE_MAX_SECONDS', 90),

        // Parallel workers (page leases). Safe: no two workers claim same skip.
        'parallel_enabled' => filter_var(env('TREB_ARCHIVE_PARALLEL', true), FILTER_VALIDATE_BOOL),
        'max_parallel_jobs' => (int) env('TREB_ARCHIVE_MAX_PARALLEL', 4),
        'lease_ttl_seconds' => (int) env('TREB_ARCHIVE_LEASE_TTL', 180),

        // HTTP client
        'http_timeout' => (int) env('TREB_ARCHIVE_HTTP_TIMEOUT', 45),
        'http_connect_timeout' => (int) env('TREB_ARCHIVE_HTTP_CONNECT_TIMEOUT', 10),

        // Dynamic throttle (Memurai/Redis/file Cache)
        'min_sleep_ms' => (int) env('TREB_ARCHIVE_MIN_SLEEP_MS', 50),
        'max_sleep_ms' => (int) env('TREB_ARCHIVE_MAX_SLEEP_MS', 5000),
        'backoff_key' => 'serik_treb_archive_rate_backoff_ms',
        'backoff_ttl_seconds' => (int) env('TREB_ARCHIVE_BACKOFF_TTL', 600),

        // Locks / resume
        'lock_seconds' => (int) env('TREB_ARCHIVE_LOCK_SECONDS', 180),
        'job_unique_for' => (int) env('TREB_ARCHIVE_JOB_UNIQUE_FOR', 180),
        'progress_cache_key' => 'serik_treb_archive_progress',

        // Queue job retries
        'job_tries' => (int) env('TREB_ARCHIVE_JOB_TRIES', 5),
        'job_timeout' => (int) env('TREB_ARCHIVE_JOB_TIMEOUT', 180),
        'job_backoff' => array_values(array_filter(array_map(
            'intval',
            explode(',', (string) env('TREB_ARCHIVE_JOB_BACKOFF', '15,45,120,300'))
        ))),

        // After each successful page, chain another job when more work remains.
        'chain_when_more' => filter_var(env('TREB_ARCHIVE_CHAIN_WHEN_MORE', true), FILTER_VALIDATE_BOOL),
        'chain_delay_seconds' => (int) env('TREB_ARCHIVE_CHAIN_DELAY', 1),

        // Queue search indexing for touched external_ids (never inline Scout).
        'queue_search_index' => filter_var(env('TREB_ARCHIVE_QUEUE_SEARCH', true), FILTER_VALIDATE_BOOL),

        // Calendar window (years back from current year inclusive).
        'years' => (int) env('TREB_ARCHIVE_YEARS', 14),

        // Scheduler cadence minutes (documentation / helpers).
        'schedule_every_minutes' => (int) env('TREB_ARCHIVE_SCHEDULE_MINUTES', 1),
    ],
];
