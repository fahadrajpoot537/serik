<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dual-priority queue names (same database connection, separate workers)
    |--------------------------------------------------------------------------
    */
    'queues' => [
        'high' => env('SERIK_QUEUE_HIGH', 'high'),
        'default' => env('SERIK_QUEUE_DEFAULT', 'default'),
        'images' => env('SERIK_QUEUE_IMAGES', 'images'),
        'low' => env('SERIK_QUEUE_LOW', 'low'),
        'search' => env('SERIK_QUEUE_SEARCH', 'low'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image persistence queue (TREB WebP / gallery)
    |--------------------------------------------------------------------------
    */
    'images' => [
        // Max PersistTrebImagesJob instances processing at once (all workers).
        'max_concurrent' => (int) env('SERIK_IMAGES_MAX_CONCURRENT', 2),
        // Pause backfill dispatch when pending images jobs reach this depth.
        'max_pending' => (int) env('SERIK_IMAGES_MAX_PENDING', 120),
        // Release job back to queue when no concurrency slot is free.
        'slot_wait_seconds' => (int) env('SERIK_IMAGES_SLOT_WAIT', 15),
        // Cooldown before the same property can be queued again (SSR/API dedupe).
        'dispatch_cooldown_seconds' => (int) env('SERIK_IMAGES_DISPATCH_COOLDOWN', 3600),
        // Throttle AMP/HTTP gallery fetches inside one job.
        'gallery_fetch_delay_ms' => (int) env('SERIK_IMAGES_GALLERY_DELAY_MS', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deferred Meilisearch sync (SearchSyncJob on LOW/search queue)
    |--------------------------------------------------------------------------
    */
    'search_sync' => [
        // Max properties indexed per SearchSyncJob Meilisearch request.
        'batch_size' => (int) env('SERIK_SEARCH_SYNC_BATCH', 25),
    ],

    /*
    |--------------------------------------------------------------------------
    | Live sync (HIGH lane)
    |--------------------------------------------------------------------------
    */
    'sync_live' => [
        'days' => (int) env('SERIK_SYNC_LIVE_DAYS', 2),
        'pages' => (int) env('SERIK_SYNC_LIVE_PAGES', 2),
        'max_seconds' => (int) env('SERIK_SYNC_LIVE_MAX_SECONDS', 40),
        'max_new' => (int) env('SERIK_SYNC_LIVE_MAX_NEW', 25),
        'page_size' => (int) env('SERIK_SYNC_LIVE_PAGE_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backlog dispatcher (LOW lane)
    |--------------------------------------------------------------------------
    */
    'backlog' => [
        // Max GeocodeBacklogPropertyJob dispatches per scheduler tick.
        'dispatch_limit' => (int) env('SERIK_BACKLOG_DISPATCH', 40),
        // If HIGH queue has this many waiting jobs, pause / shrink backlog.
        'high_depth_pause' => (int) env('SERIK_BACKLOG_PAUSE_HIGH_DEPTH', 5),
        'active_only' => filter_var(env('SERIK_BACKLOG_ACTIVE_ONLY', true), FILTER_VALIDATE_BOOL),
        'days' => (int) env('SERIK_BACKLOG_DAYS', 90),
        // Extra sleep (ms) between LOW geocodes when HIGH has pending work.
        'throttle_ms_when_busy' => (int) env('SERIK_BACKLOG_THROTTLE_MS', 500),
    ],

    'geocode' => [
        // Reset processing → pending if started older than this.
        'stuck_minutes' => (int) env('SERIK_GEOCODE_STUCK_MINUTES', 20),
        // Max rows reset / requeued per dispatcher tick.
        'reset_limit' => (int) env('SERIK_GEOCODE_RESET_LIMIT', 200),
        'retry_failed_limit' => (int) env('SERIK_GEOCODE_RETRY_FAILED_LIMIT', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduler — keep schedule:run under ~2s on production IIS
    |--------------------------------------------------------------------------
    | Heavy commands must dispatch to LOW queue workers, never Artisan::call()
    | inside schedule:run (blocks Task Scheduler + competes with web traffic).
    */
    'scheduler' => [
        // Skip new LOW maintenance dispatches when queue depth is at/above this.
        'max_low_queue_depth' => (int) env('SERIK_SCHEDULER_MAX_LOW_DEPTH', 3),
        'search_index_recent_limit' => (int) env('SERIK_SEARCH_INDEX_RECENT_LIMIT', 300),
        'import_historical_max_runtime' => (int) env('SERIK_IMPORT_HISTORICAL_MAX_RUNTIME', 180),
        'treb_images_max_runtime' => (int) env('SERIK_TREB_IMAGES_MAX_RUNTIME', 600),
        'treb_images_chunk' => (int) env('SERIK_TREB_IMAGES_CHUNK', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Geo block (public site)
    |--------------------------------------------------------------------------
    | When enabled, only listed ISO country codes may view the public site.
    | Admin + API + ajax shortcode routes are bypassed (see middleware).
    */
    'geo_block' => [
        'enabled' => filter_var(env('GEO_BLOCK_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'allowed_countries' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('GEO_BLOCK_ALLOWED_COUNTRIES', 'US,CA'))
        ))),
        'bypass_ips' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('GEO_BLOCK_BYPASS_IPS', ''))
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request profiling (disabled by default)
    |--------------------------------------------------------------------------
    */
    'profile_requests' => filter_var(env('SERIK_PROFILE_REQUESTS', false), FILTER_VALIDATE_BOOLEAN),

];
