<?php

namespace App\Support;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Production-safe cache helpers (Memurai / Redis / file).
 * Does not change cached payloads — only reduces stampedes and lock races.
 */
final class SerikCache
{
    /**
     * Cache::remember with single-flight lock (dogpile / stampede prevention).
     * On lock failure, falls back to a direct remember (never drops the value).
     *
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public static function remember(string $key, int $ttlSeconds, callable $callback, int $lockSeconds = 15): mixed
    {
        $hit = Cache::get($key);
        if ($hit !== null) {
            return $hit;
        }

        $lockKey = 'serik:cache:sf:' . md5($key);
        $lock = Cache::lock($lockKey, max(5, $lockSeconds));

        try {
            return $lock->block(5, static function () use ($key, $ttlSeconds, $callback) {
                $again = Cache::get($key);
                if ($again !== null) {
                    return $again;
                }

                $value = $callback();
                Cache::put($key, $value, max(1, $ttlSeconds));

                return $value;
            });
        } catch (LockTimeoutException|Throwable) {
            return Cache::remember($key, max(1, $ttlSeconds), $callback);
        }
    }
}
