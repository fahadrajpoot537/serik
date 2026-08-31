<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Liveness: PHP/Laravel can boot. Readiness: dependency checks with timeouts.
 */
class HealthController
{
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'app' => 'serik',
        ], 200);
    }

    public function ready(Request $request): JsonResponse
    {
        $detailed = $this->maySeeDetails($request);
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'meilisearch' => $this->checkMeilisearch(),
        ];

        $statuses = array_column($checks, 'status');
        $overall = 'ok';
        if (in_array('down', $statuses, true)) {
            $overall = in_array($checks['database']['status'], ['down'], true) ? 'down' : 'degraded';
        }

        $http = $overall === 'down' ? 503 : 200;

        $payload = [
            'status' => $overall,
        ];

        if ($detailed) {
            $payload['checks'] = $checks;
        }

        return response()->json($payload, $http);
    }

    private function maySeeDetails(Request $request): bool
    {
        $token = (string) config('serik.health.token', '');
        if ($token !== '') {
            $provided = (string) $request->header('X-Serik-Health-Token', $request->query('token', ''));

            return hash_equals($token, $provided);
        }

        return app()->environment('local', 'testing');
    }

    /**
     * @return array{status: string, latency_ms: int}
     */
    private function checkDatabase(): array
    {
        $started = microtime(true);

        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');

            return [
                'status' => 'ok',
                'latency_ms' => $this->latencyMs($started),
            ];
        } catch (Throwable) {
            return [
                'status' => 'down',
                'latency_ms' => $this->latencyMs($started),
            ];
        }
    }

    /**
     * @return array{status: string, latency_ms: int}
     */
    private function checkRedis(): array
    {
        $driver = (string) config('cache.default');
        $sessionDriver = (string) config('session.driver');

        if ($driver !== 'redis' && $sessionDriver !== 'redis' && (string) config('queue.default') !== 'redis') {
            return [
                'status' => 'unused',
                'latency_ms' => 0,
            ];
        }

        $started = microtime(true);

        try {
            $pong = Redis::connection('default')->ping();
            $ok = $pong === true || $pong === 'PONG' || $pong === '+PONG';

            return [
                'status' => $ok ? 'ok' : 'down',
                'latency_ms' => $this->latencyMs($started),
            ];
        } catch (Throwable) {
            return [
                'status' => 'down',
                'latency_ms' => $this->latencyMs($started),
            ];
        }
    }

    /**
     * @return array{status: string, latency_ms: int}
     */
    private function checkMeilisearch(): array
    {
        if ((string) config('scout.driver') !== 'meilisearch') {
            return [
                'status' => 'unused',
                'latency_ms' => 0,
            ];
        }

        $host = rtrim((string) config('scout.meilisearch.host', ''), '/');
        if ($host === '') {
            return [
                'status' => 'down',
                'latency_ms' => 0,
            ];
        }

        $timeout = max(0.2, (float) config('serik.health.meilisearch_timeout', 1.0));
        $started = microtime(true);

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(min(0.5, $timeout))
                ->get($host.'/health');

            return [
                'status' => $response->successful() ? 'ok' : 'down',
                'latency_ms' => $this->latencyMs($started),
            ];
        } catch (Throwable) {
            return [
                'status' => 'down',
                'latency_ms' => $this->latencyMs($started),
            ];
        }
    }

    private function latencyMs(float $started): int
    {
        return (int) max(0, round((microtime(true) - $started) * 1000));
    }
}
