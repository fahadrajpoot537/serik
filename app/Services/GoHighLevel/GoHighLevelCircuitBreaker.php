<?php

namespace App\Services\GoHighLevel;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Soft circuit breaker for LeadConnector API (cache-backed, Memurai/file safe).
 * Open circuit fails fast; does not alter successful request payloads.
 */
final class GoHighLevelCircuitBreaker
{
    private const STATE_KEY = 'ghl:circuit:state';

    private const FAILURES_KEY = 'ghl:circuit:failures';

    public static function enabled(): bool
    {
        return (bool) config('gohighlevel.mls_sync.circuit_breaker_enabled', true);
    }

    public static function allow(): bool
    {
        if (! self::enabled()) {
            return true;
        }

        $state = Cache::get(self::STATE_KEY);
        if (! is_array($state) || ($state['status'] ?? '') !== 'open') {
            return true;
        }

        $openedAt = (int) ($state['opened_at'] ?? 0);
        $coolDown = max(10, (int) config('gohighlevel.mls_sync.circuit_cooldown_seconds', 60));
        if ($openedAt > 0 && (time() - $openedAt) >= $coolDown) {
            // Half-open: allow one probe.
            Cache::put(self::STATE_KEY, [
                'status' => 'half_open',
                'opened_at' => $openedAt,
            ], $coolDown * 2);

            return true;
        }

        return false;
    }

    public static function recordSuccess(): void
    {
        if (! self::enabled()) {
            return;
        }

        Cache::forget(self::FAILURES_KEY);
        Cache::forget(self::STATE_KEY);
    }

    public static function recordFailure(): void
    {
        if (! self::enabled()) {
            return;
        }

        $threshold = max(2, (int) config('gohighlevel.mls_sync.circuit_failure_threshold', 5));
        $coolDown = max(10, (int) config('gohighlevel.mls_sync.circuit_cooldown_seconds', 60));
        $failures = (int) Cache::increment(self::FAILURES_KEY);
        Cache::put(self::FAILURES_KEY, $failures, $coolDown * 3);

        if ($failures >= $threshold) {
            Cache::put(self::STATE_KEY, [
                'status' => 'open',
                'opened_at' => time(),
                'failures' => $failures,
            ], $coolDown * 3);

            Log::channel('ghl_sync')->warning('GoHighLevel circuit opened', [
                'failures' => $failures,
                'cooldown_seconds' => $coolDown,
            ]);
        }
    }

    /**
     * @return array{status: string, failures: int}
     */
    public static function snapshot(): array
    {
        $state = Cache::get(self::STATE_KEY);
        $status = is_array($state) ? (string) ($state['status'] ?? 'closed') : 'closed';

        return [
            'status' => $status !== '' ? $status : 'closed',
            'failures' => (int) Cache::get(self::FAILURES_KEY, 0),
        ];
    }
}
