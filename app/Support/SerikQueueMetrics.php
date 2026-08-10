<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rolling queue metrics (depths, failures, retries, avg execution time).
 * Stored in Cache (Memurai when CACHE_STORE=redis).
 */
final class SerikQueueMetrics
{
    private const PREFIX = 'serik_qmetrics:';

    private const WINDOW = 3600;

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(): array
    {
        $lanes = SerikQueue::laneMap();
        $depths = [];
        $reserved = [];
        $retriesInFlight = [];

        foreach ($lanes as $label => $name) {
            $depths[$label] = (int) DB::table('jobs')->where('queue', $name)->count();
            $reserved[$label] = (int) DB::table('jobs')
                ->where('queue', $name)
                ->whereNotNull('reserved_at')
                ->count();
            $retriesInFlight[$label] = (int) DB::table('jobs')
                ->where('queue', $name)
                ->where('attempts', '>', 0)
                ->count();
        }

        $failedTotal = (int) DB::table('failed_jobs')->count();
        $failedRecent = (int) DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subHour())
            ->count();

        return [
            'at' => now()->toIso8601String(),
            'depths' => $depths,
            'reserved' => $reserved,
            'retries_in_flight' => $retriesInFlight,
            'failed_jobs' => $failedTotal,
            'failed_last_hour' => $failedRecent,
            'processed_last_hour' => self::counter('processed'),
            'failed_events_last_hour' => self::counter('failed_events'),
            'retry_events_last_hour' => self::counter('retry_events'),
            'avg_execution_ms' => self::avgExecutionMs(),
            'avg_execution_ms_by_queue' => self::avgExecutionMsByQueue(),
            'worker_health' => SerikWindowsService::queueServiceStates(),
            'isolation' => [
                'imports_blocks_user_facing' => false,
                'imports_queue' => SerikQueue::imports(),
                'user_facing' => SerikQueue::userFacing(),
            ],
        ];
    }

    public static function recordProcessed(string $queue, float $durationMs): void
    {
        self::increment('processed');
        self::pushDuration($queue, $durationMs);
    }

    public static function recordFailed(string $queue): void
    {
        self::increment('failed_events');
        self::increment('failed_queue:' . $queue);
    }

    public static function recordRetry(string $queue): void
    {
        self::increment('retry_events');
        self::increment('retry_queue:' . $queue);
    }

    public static function counter(string $name): int
    {
        return (int) Cache::get(self::PREFIX . $name, 0);
    }

    /**
     * @return array{avg_ms: float|null, samples: int}
     */
    public static function avgExecutionMs(): array
    {
        $samples = Cache::get(self::PREFIX . 'durations', []);
        if (! is_array($samples) || $samples === []) {
            return ['avg_ms' => null, 'samples' => 0];
        }

        $values = array_values(array_filter($samples, 'is_numeric'));
        if ($values === []) {
            return ['avg_ms' => null, 'samples' => 0];
        }

        return [
            'avg_ms' => round(array_sum($values) / count($values), 2),
            'samples' => count($values),
        ];
    }

    /**
     * @return array<string, array{avg_ms: float, samples: int}>
     */
    public static function avgExecutionMsByQueue(): array
    {
        $byQueue = Cache::get(self::PREFIX . 'durations_by_queue', []);
        if (! is_array($byQueue)) {
            return [];
        }

        $out = [];
        foreach ($byQueue as $queue => $samples) {
            if (! is_array($samples) || $samples === []) {
                continue;
            }
            $values = array_values(array_filter($samples, 'is_numeric'));
            if ($values === []) {
                continue;
            }
            $out[(string) $queue] = [
                'avg_ms' => round(array_sum($values) / count($values), 2),
                'samples' => count($values),
            ];
        }

        return $out;
    }

    protected static function increment(string $name): void
    {
        $key = self::PREFIX . $name;
        try {
            if (Cache::has($key)) {
                Cache::increment($key);
            } else {
                Cache::put($key, 1, self::WINDOW);
            }
        } catch (\Throwable) {
            Cache::put($key, self::counter($name) + 1, self::WINDOW);
        }
    }

    protected static function pushDuration(string $queue, float $durationMs): void
    {
        $durationMs = max(0, $durationMs);
        $all = Cache::get(self::PREFIX . 'durations', []);
        if (! is_array($all)) {
            $all = [];
        }
        $all[] = $durationMs;
        $all = array_slice($all, -200);
        Cache::put(self::PREFIX . 'durations', $all, self::WINDOW);

        $byQueue = Cache::get(self::PREFIX . 'durations_by_queue', []);
        if (! is_array($byQueue)) {
            $byQueue = [];
        }
        $list = is_array($byQueue[$queue] ?? null) ? $byQueue[$queue] : [];
        $list[] = $durationMs;
        $byQueue[$queue] = array_slice($list, -100);
        Cache::put(self::PREFIX . 'durations_by_queue', $byQueue, self::WINDOW);
    }
}
