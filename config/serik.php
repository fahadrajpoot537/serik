<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dual-priority queue names (same database connection, separate workers)
    |--------------------------------------------------------------------------
    */
    'queues' => [
        'critical' => env('SERIK_QUEUE_CRITICAL', 'critical'),
        'high' => env('SERIK_QUEUE_HIGH', 'high'),
        'default' => env('SERIK_QUEUE_DEFAULT', 'default'),
        'images' => env('SERIK_QUEUE_IMAGES', 'images'),
        'imports' => env('SERIK_QUEUE_IMPORTS', 'imports'),
        'low' => env('SERIK_QUEUE_LOW', 'low'),
        // BC: SearchBatchJob still uses `search` (default physical name: low).
        'search' => env('SERIK_QUEUE_SEARCH', 'low'),
        // Dedicated search-index lane (opt-in via SERIK_QUEUE_SEARCH=search-index).
        'search_index' => env('SERIK_QUEUE_SEARCH_INDEX', 'search-index'),
        'cache_refresh' => env('SERIK_QUEUE_CACHE_REFRESH', 'cache-refresh'),
        'ghl' => env('SERIK_QUEUE_GHL', 'ghl'),
        'notifications' => env('SERIK_QUEUE_NOTIFICATIONS', 'notifications'),
        'emails' => env('SERIK_QUEUE_EMAILS', 'emails'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue orchestration (monitoring, self-heal, locks) — additive only
    |--------------------------------------------------------------------------
    */
    'orchestration' => [
        'enabled' => filter_var(env('SERIK_QUEUE_ORCHESTRATION', true), FILTER_VALIDATE_BOOLEAN),
        'stale_reserved_seconds' => (int) env('SERIK_QUEUE_STALE_RESERVED', 900),
        // RunArtisanOnLowQueueJob can intentionally run bounded maintenance longer
        // than the general stale reservation threshold.
        'maintenance_stale_reserved_seconds' => (int) env('SERIK_QUEUE_MAINTENANCE_STALE_RESERVED', 7500),
        'auto_requeue_failed' => filter_var(env('SERIK_QUEUE_AUTO_REQUEUE_FAILED', true), FILTER_VALIDATE_BOOLEAN),
        'failed_requeue_limit' => (int) env('SERIK_QUEUE_FAILED_REQUEUE_LIMIT', 25),
        'failed_requeue_max_age_hours' => (int) env('SERIK_QUEUE_FAILED_MAX_AGE_HOURS', 24),
        'failed_max_auto_retries' => (int) env('SERIK_QUEUE_FAILED_MAX_AUTO_RETRIES', 5),
        'failed_prune_hours' => (int) env('SERIK_QUEUE_FAILED_PRUNE_HOURS', 168),
        'retry_base_seconds' => (int) env('SERIK_QUEUE_RETRY_BASE', 60),
        'retry_max_seconds' => (int) env('SERIK_QUEUE_RETRY_MAX', 3600),
        // Opt-in: touch storage/framework/queue-restart.flag after deploy
        'auto_queue_restart' => filter_var(env('SERIK_QUEUE_AUTO_RESTART', true), FILTER_VALIDATE_BOOLEAN),
        'heal_every_minutes' => (int) env('SERIK_QUEUE_HEAL_EVERY', 5),
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
    | Deferred Meilisearch sync (SearchBatchJob on LOW/search queue)
    |--------------------------------------------------------------------------
    */
    'search_sync' => [
        // Max properties indexed per Meilisearch addDocuments request (throughput only; same docs/order).
        'batch_size' => (int) env('SERIK_SEARCH_SYNC_BATCH', 50),
        // Bound one worker run so indexing cannot starve other LOW-lane work.
        'max_batches_per_job' => (int) env('SERIK_SEARCH_SYNC_MAX_BATCHES', 20),
        'max_seconds_per_job' => (int) env('SERIK_SEARCH_SYNC_MAX_SECONDS', 240),
        // Requeue claimed-but-unfinished IDs after this many minutes (crash mid-index).
        'inflight_stale_minutes' => (int) env('SERIK_SEARCH_INFLIGHT_STALE', 30),
        // Retain a dispatch guard while a batch worker owns the backlog.
        'dispatch_guard_seconds' => (int) env('SERIK_SEARCH_SYNC_DISPATCH_GUARD', 360),
        // Persistent Meilisearch failures retain the durable pending checkpoint and cool down
        // before queue self-healing attempts another batch worker.
        'failure_backoff_base_seconds' => (int) env('SERIK_SEARCH_SYNC_FAILURE_BACKOFF_BASE', 30),
        'failure_backoff_max_seconds' => (int) env('SERIK_SEARCH_SYNC_FAILURE_BACKOFF_MAX', 900),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reliability / integrity (additive)
    |--------------------------------------------------------------------------
    */
    'reliability' => [
        'ghl_stuck_minutes' => (int) env('SERIK_GHL_STUCK_MINUTES', 45),
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
        // Cap imports fan-out when the imports lane is already deep (never touches high/low).
        'max_imports_queue_depth' => (int) env('SERIK_SCHEDULER_MAX_IMPORTS_DEPTH', 20),
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
            explode(',', (string) env('GEO_BLOCK_ALLOWED_COUNTRIES', 'US,CA,PK'))
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

    /*
    |--------------------------------------------------------------------------
    | Response / payload cache TTLs (seconds)
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'property_detail_ttl' => (int) env('SERIK_PROPERTY_DETAIL_TTL', 1800),
        'homepage_ttl' => (int) env('SERIK_HOMEPAGE_TTL', 7200),
        'fragment_ttl' => (int) env('SERIK_FRAGMENT_TTL', 3600),
        'featured_ttl' => (int) env('SERIK_FEATURED_TTL', 900),
        'counts_ttl' => (int) env('SERIK_COUNTS_TTL', 3600),
        'place_search_ttl' => (int) env('SERIK_PLACE_SEARCH_TTL', 86400),
        'related_ttl' => (int) env('SERIK_RELATED_TTL', 900),
        // After homepage warm, also warm map APIs + popular place searches (guest only).
        'warm_extended_on_homepage' => filter_var(env('SERIK_CACHE_WARM_EXTENDED', true), FILTER_VALIDATE_BOOLEAN),
        // Full warm (serik:cache:warm) — used by deploy / cache-refresh worker.
        'warm_dispatch_on_optimize' => filter_var(env('SERIK_CACHE_WARM_ON_OPTIMIZE', true), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | Production security headers (additive — does not change page content)
    |--------------------------------------------------------------------------
    */
    'security' => [
        'headers_enabled' => filter_var(env('SERIK_SECURITY_HEADERS', true), FILTER_VALIDATE_BOOLEAN),
        'hsts_enabled' => filter_var(env('SERIK_HSTS', true), FILTER_VALIDATE_BOOLEAN),
        'hsts_max_age' => (int) env('SERIK_HSTS_MAX_AGE', 31536000),
        'hsts_preload' => filter_var(env('SERIK_HSTS_PRELOAD', false), FILTER_VALIDATE_BOOLEAN),
        // Permissive CSP: allows existing Maps / GHL / CDN / inline theme scripts.
        'csp_enabled' => filter_var(env('SERIK_CSP', true), FILTER_VALIDATE_BOOLEAN),
        'csp_report_only' => filter_var(env('SERIK_CSP_REPORT_ONLY', false), FILTER_VALIDATE_BOOLEAN),
        // MapLibre (and similar map engines) spawn Web Workers from blob: URLs.
        // Without an explicit worker-src, browsers fall back to script-src, which
        // historically omitted blob: and silently blocked clustering / pin layers.
        'csp' => env(
            'SERIK_CSP_POLICY',
            "default-src 'self' https: data: blob:; "
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https: blob:; "
            . "worker-src 'self' blob: https:; "
            . "child-src 'self' blob: https:; "
            . "style-src 'self' 'unsafe-inline' https:; "
            . "img-src 'self' data: blob: https:; "
            . "font-src 'self' data: https:; "
            . "connect-src 'self' https: wss: ws:; "
            . "frame-src 'self' https:; "
            . "media-src 'self' https: data: blob:; "
            . "object-src 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self' https:; "
            . "frame-ancestors 'self'; "
            . 'upgrade-insecure-requests'
        ),
        'permissions_policy' => env(
            'SERIK_PERMISSIONS_POLICY',
            'accelerometer=(), camera=(), geolocation=(self), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()'
        ),
        'corp' => env('SERIK_CORP', 'same-site'),
        'coop' => env('SERIK_COOP', 'same-origin-allow-popups'),
    ],

];
