<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Memurai/Redis-compatible locks via Cache::lock (works when CACHE_STORE=redis).
 * Falls back safely when the store does not support locks.
 */
final class SerikQueueLock
{
    /**
     * Run $callback once under a lock. Returns null if the lock could not be acquired.
     *
     * @template T
     * @param  Closure(): T  $callback
     * @return T|null
     */
    public static function once(string $key, int $ttlSeconds, Closure $callback, int $waitSeconds = 0): mixed
    {
        $lockKey = 'serik_qlock:' . $key;
        $ttlSeconds = max(5, $ttlSeconds);

        try {
            $lock = Cache::lock($lockKey, $ttlSeconds);

            if ($waitSeconds > 0) {
                return $lock->block($waitSeconds, $callback);
            }

            if (! $lock->get()) {
                Log::debug('SerikQueueLock: skipped (held)', ['key' => $key]);

                return null;
            }

            try {
                return $callback();
            } finally {
                try {
                    $lock->release();
                } catch (Throwable) {
                }
            }
        } catch (Throwable $e) {
            // File cache / missing lock driver — still run (scheduler withoutOverlapping remains).
            Log::debug('SerikQueueLock: fallback without lock', [
                'key' => $key,
                'message' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    /**
     * True if a short-lived "already dispatched" marker exists.
     */
    public static function wasRecentlyDispatched(string $key, int $cooldownSeconds = 55): bool
    {
        return SerikCache::has('serik_qdispatch:' . $key);
    }

    /**
     * Mark a schedule/dispatch key to prevent duplicate fan-out within the cooldown.
     */
    public static function markDispatched(string $key, int $cooldownSeconds = 55): void
    {
        SerikCache::put('serik_qdispatch:' . $key, 1, max(5, $cooldownSeconds));
    }

    /**
     * Acquire-or-skip helper combining cooldown + lock for scheduler ticks.
     *
     * @template T
     * @param  Closure(): T  $callback
     * @return T|null
     */
    public static function dispatchGuard(string $key, Closure $callback, int $cooldownSeconds = 55, int $lockTtl = 50): mixed
    {
        if (self::wasRecentlyDispatched($key, $cooldownSeconds)) {
            return null;
        }

        return self::once($key, $lockTtl, function () use ($key, $callback, $cooldownSeconds) {
            if (self::wasRecentlyDispatched($key, $cooldownSeconds)) {
                return null;
            }

            $result = $callback();
            self::markDispatched($key, $cooldownSeconds);

            return $result;
        });
    }
}
