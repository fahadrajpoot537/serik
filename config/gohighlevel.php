<?php

return [

    /*
    |--------------------------------------------------------------------------
    | MLS → Showings sync (extends existing Contacts lead upsert; does not replace it)
    |--------------------------------------------------------------------------
    |
    | GHL stores Showings / property details as Contact custom fields under this
    | location. The Private Integration Token already has contacts + custom-fields
    | scopes. Custom Objects API is not required for this sync path.
    |
    */
    'mls_sync' => [
        'enabled' => (bool) env('GOHIGHLEVEL_MLS_SYNC_ENABLED', true),

        // Early-morning processor (America/Toronto unless app timezone differs)
        'process_at' => env('GOHIGHLEVEL_MLS_PROCESS_AT', '05:15'),

        // Max pending tasks claimed per morning dispatch
        'batch_size' => (int) env('GOHIGHLEVEL_MLS_BATCH_SIZE', 100),

        // Job retries (transient 429/5xx/timeouts/network)
        'job_tries' => (int) env('GOHIGHLEVEL_MLS_JOB_TRIES', 8),
        'job_backoff_seconds' => [60, 120, 300, 600, 900, 1800],

        // HTTP client
        'http_timeout' => (int) env('GOHIGHLEVEL_HTTP_TIMEOUT', 25),
        'http_retries' => (int) env('GOHIGHLEVEL_HTTP_RETRIES', 3),
        'http_retry_sleep_ms' => (int) env('GOHIGHLEVEL_HTTP_RETRY_SLEEP_MS', 750),

        // Soft circuit breaker (cache-backed)
        'circuit_breaker_enabled' => filter_var(env('GOHIGHLEVEL_CIRCUIT_BREAKER', true), FILTER_VALIDATE_BOOLEAN),
        'circuit_failure_threshold' => (int) env('GOHIGHLEVEL_CIRCUIT_THRESHOLD', 5),
        'circuit_cooldown_seconds' => (int) env('GOHIGHLEVEL_CIRCUIT_COOLDOWN', 60),

        // Custom field metadata cache TTL
        'field_cache_ttl' => (int) env('GOHIGHLEVEL_FIELD_CACHE_TTL', 3600),

        // Optional shared secret for inbound webhooks (X-GHL-Signature or ?token=)
        'webhook_secret' => env('GOHIGHLEVEL_WEBHOOK_SECRET'),

        // Deduplicate rapid GHL webhook retries (same contact+MLS)
        'webhook_idempotency' => filter_var(env('GOHIGHLEVEL_WEBHOOK_IDEMPOTENCY', true), FILTER_VALIDATE_BOOLEAN),
        'webhook_idempotency_seconds' => (int) env('GOHIGHLEVEL_WEBHOOK_IDEMPOTENCY_SECONDS', 120),

        // Optional timestamp skew check (off by default — never rejects when header absent)
        'webhook_timestamp_check' => filter_var(env('GOHIGHLEVEL_WEBHOOK_TIMESTAMP_CHECK', false), FILTER_VALIDATE_BOOLEAN),
        'webhook_max_skew_seconds' => (int) env('GOHIGHLEVEL_WEBHOOK_MAX_SKEW', 900),

        // Trigger custom field key (do not rename)
        'mls_field_key' => 'contact.mls_number',

        // Idempotency: skip GHL update when mapped payload hash unchanged
        'skip_unchanged' => (bool) env('GOHIGHLEVEL_MLS_SKIP_UNCHANGED', true),
    ],

];
