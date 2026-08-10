<?php

namespace App\Services\GoHighLevel;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Structured GHL observability counters (cache-backed; safe across workers).
 */
final class GoHighLevelMetrics
{
    private const PREFIX = 'ghl:metrics:';

    public static function correlationId(?string $existing = null): string
    {
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        return (string) Str::uuid();
    }

    public static function incr(string $metric, int $by = 1): void
    {
        $key = self::PREFIX . $metric . ':' . date('YmdH');
        try {
            $n = (int) Cache::increment($key, $by);
            if ($n === 1 || $n === $by) {
                Cache::put($key, $n, 86400 * 2);
            }
        } catch (\Throwable) {
            // Metrics must never break sync.
        }
    }

    public static function observeLatency(string $metric, float $ms): void
    {
        self::incr($metric . '_count');
        self::incr($metric . '_ms_sum', max(1, (int) round($ms)));
    }

    public static function markLastSuccess(): void
    {
        Cache::put(self::PREFIX . 'last_success_at', now()->toIso8601String(), 86400 * 14);
    }

    public static function markLastFailure(string $reason): void
    {
        Cache::put(self::PREFIX . 'last_failure_at', now()->toIso8601String(), 86400 * 14);
        Cache::put(self::PREFIX . 'last_failure_reason', mb_substr($reason, 0, 500), 86400 * 14);
    }

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(): array
    {
        $hour = date('YmdH');
        $day = date('Ymd');

        $get = static function (string $metric, string $suffix) {
            return (int) Cache::get(self::PREFIX . $metric . ':' . $suffix, 0);
        };

        $avg = static function (int $sum, int $count): ?float {
            return $count > 0 ? round($sum / $count, 1) : null;
        };

        $apiCount = $get('api_latency_count', $hour);
        $apiSum = $get('api_latency_ms_sum', $hour);
        $whCount = $get('webhook_latency_count', $hour);
        $whSum = $get('webhook_latency_ms_sum', $hour);
        $jobCount = $get('job_latency_count', $hour);
        $jobSum = $get('job_latency_ms_sum', $hour);

        return [
            'hour' => [
                'webhook_accepted' => $get('webhook_accepted', $hour),
                'webhook_duplicate' => $get('webhook_duplicate', $hour),
                'webhook_unauthorized' => $get('webhook_unauthorized', $hour),
                'tasks_enqueued' => $get('tasks_enqueued', $hour),
                'sync_completed' => $get('sync_completed', $hour),
                'sync_skipped_unchanged' => $get('sync_skipped_unchanged', $hour),
                'sync_failed' => $get('sync_failed', $hour),
                'http_retries' => $get('http_retries', $hour),
                'circuit_rejects' => $get('circuit_rejects', $hour),
                'avg_api_ms' => $avg($apiSum, $apiCount),
                'avg_webhook_ms' => $avg($whSum, $whCount),
                'avg_job_ms' => $avg($jobSum, $jobCount),
            ],
            'day' => [
                'webhook_accepted' => $get('webhook_accepted', $day),
                'sync_completed' => $get('sync_completed', $day),
                'sync_failed' => $get('sync_failed', $day),
            ],
            'last_success_at' => Cache::get(self::PREFIX . 'last_success_at'),
            'last_failure_at' => Cache::get(self::PREFIX . 'last_failure_at'),
            'last_failure_reason' => Cache::get(self::PREFIX . 'last_failure_reason'),
            'circuit' => GoHighLevelCircuitBreaker::snapshot(),
        ];
    }

    /** Also bump daily counters alongside hourly. */
    public static function incrDay(string $metric, int $by = 1): void
    {
        self::incr($metric, $by);
        $key = self::PREFIX . $metric . ':' . date('Ymd');
        try {
            $n = (int) Cache::increment($key, $by);
            if ($n === 1 || $n === $by) {
                Cache::put($key, $n, 86400 * 3);
            }
        } catch (\Throwable) {
        }
    }
}
