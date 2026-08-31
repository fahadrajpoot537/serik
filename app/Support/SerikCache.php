<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Production-safe cache helpers (Memurai / Redis / file).
 * Optional cache failures must not become HTTP 500s. Callers that need a hard
 * cache miss should use this class; session and auth must not use it.
 */
final class SerikCache
{
    /**
     * Cache::remember with single-flight lock (dogpile / stampede prevention).
     * On lock or store failure, computes the value from source (never drops it).
     *
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public static function remember(string $key, int $ttlSeconds, callable $callback, int $lockSeconds = 15): mixed
    {
        try {
            $hit = Cache::get($key);
            if ($hit !== null) {
                return $hit;
            }
        } catch (Throwable $e) {
            SerikSafeLog::write('warning', '[SerikCache] get failed; using source', [
                'key_hash' => sha1($key),
                'error' => $e->getMessage(),
            ]);

            return $callback();
        }

        $lockKey = 'serik:cache:sf:'.md5($key);
        try {
            $lock = Cache::lock($lockKey, max(5, $lockSeconds));

            return $lock->block(5, static function () use ($key, $ttlSeconds, $callback) {
                $again = Cache::get($key);
                if ($again !== null) {
                    return $again;
                }

                $value = $callback();
                Cache::put($key, $value, max(1, $ttlSeconds));

                return $value;
            });
        } catch (LockTimeoutException|Throwable $e) {
            try {
                return Cache::remember($key, max(1, $ttlSeconds), $callback);
            } catch (Throwable) {
                SerikSafeLog::write('warning', '[SerikCache] remember failed; using source', [
                    'key_hash' => sha1($key),
                    'error' => $e->getMessage(),
                ]);

                return $callback();
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::get($key, $default);
        } catch (Throwable $e) {
            SerikSafeLog::write('warning', '[SerikCache] get failed', [
                'key_hash' => sha1($key),
                'error' => $e->getMessage(),
            ]);

            return $default instanceof Closure ? $default() : $default;
        }
    }

    public static function put(string $key, mixed $value, mixed $ttl = null): bool
    {
        try {
            if ($ttl === null) {
                Cache::put($key, $value);
            } else {
                Cache::put($key, $value, $ttl);
            }

            return true;
        } catch (Throwable $e) {
            SerikSafeLog::write('warning', '[SerikCache] put failed', [
                'key_hash' => sha1($key),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function forever(string $key, mixed $value): bool
    {
        try {
            Cache::forever($key, $value);

            return true;
        } catch (Throwable $e) {
            SerikSafeLog::write('warning', '[SerikCache] forever failed', [
                'key_hash' => sha1($key),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function forget(string $key): bool
    {
        try {
            return (bool) Cache::forget($key);
        } catch (Throwable $e) {
            SerikSafeLog::write('warning', '[SerikCache] forget failed', [
                'key_hash' => sha1($key),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function has(string $key): bool
    {
        try {
            return Cache::has($key);
        } catch (Throwable $e) {
            SerikSafeLog::write('warning', '[SerikCache] has failed', [
                'key_hash' => sha1($key),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
