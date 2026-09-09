<?php

namespace App\Support;

use Botble\RealEstate\Services\PropertySearchService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Meilisearch probe + scheduled self-heal (used by serik:queue:heal).
 *
 * Does not change search query behavior — only observability and optional
 * process/service restart so search can leave MySQL-fallback mode.
 */
final class SerikMeilisearchHealth
{
    public const HEALTH_CACHE_KEY = 'serik_meili_health_v2';

    public const DOWN_LOG_THROTTLE_KEY = 'serik_meili_down_logged_v1';

    /**
     * @return array{
     *     enabled: bool,
     *     reachable: bool,
     *     documents: int|null,
     *     available_for_search: bool,
     *     host: string,
     *     reason: string|null,
     *     healed: array<string, mixed>
     * }
     */
    public function probe(bool $bypassCache = false): array
    {
        $host = rtrim((string) config('scout.meilisearch.host', ''), '/');
        $driverOk = (string) config('scout.driver') === 'meilisearch';

        $report = [
            'enabled' => $driverOk,
            'reachable' => false,
            'documents' => null,
            'available_for_search' => false,
            'host' => $host,
            'reason' => null,
            'healed' => [],
        ];

        if (! $driverOk) {
            $report['reason'] = 'scout_driver_not_meilisearch';

            return $report;
        }

        if ($host === '') {
            $report['reason'] = 'meilisearch_host_empty';

            return $report;
        }

        if ($bypassCache) {
            SerikCache::forget(self::HEALTH_CACHE_KEY);
        }

        try {
            /** @var PropertySearchService $search */
            $search = app(PropertySearchService::class);
            $report['available_for_search'] = $search->isAvailable();

            // Direct HTTP health — distinguishes "process down" from "empty index".
            $timeout = max(0.2, (float) config('serik.health.meilisearch_timeout', 1.0));
            $response = \Illuminate\Support\Facades\Http::timeout($timeout)
                ->connectTimeout(min(0.5, $timeout))
                ->withToken((string) config('scout.meilisearch.key', ''))
                ->get($host . '/health');

            $report['reachable'] = $response->successful();
            if (! $report['reachable']) {
                $report['reason'] = 'health_http_' . $response->status();
            }
        } catch (Throwable $e) {
            $report['reason'] = 'unreachable: ' . mb_substr($e->getMessage(), 0, 200);
        }

        if ($report['reachable']) {
            try {
                $stats = \Illuminate\Support\Facades\Http::timeout(2)
                    ->connectTimeout(0.5)
                    ->withToken((string) config('scout.meilisearch.key', ''))
                    ->get($host . '/indexes/properties/stats');
                if ($stats->successful()) {
                    $report['documents'] = (int) ($stats->json('numberOfDocuments') ?? 0);
                    if ($report['documents'] <= 0 && $report['reason'] === null) {
                        $report['reason'] = 'index_empty';
                    }
                }
            } catch (Throwable) {
                // stats optional
            }
        }

        if (! $report['available_for_search'] && $report['reason'] === null) {
            $report['reason'] = 'search_service_unavailable';
        }

        return $report;
    }

    /**
     * Called from queue self-heal. Logs clearly when Meili is down and optionally
     * restarts the Windows NSSM service / configured start command.
     *
     * @return array<string, mixed>
     */
    public function heal(): array
    {
        if (! filter_var(config('serik.health.meili_heal.enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            return ['skipped' => true, 'reason' => 'meili_heal_disabled'];
        }

        $before = $this->probe(bypassCache: true);
        $actions = [
            'was_available' => $before['available_for_search'],
            'was_reachable' => $before['reachable'],
            'reason' => $before['reason'],
            'service_start_attempted' => false,
            'service_start_ok' => null,
            'process_start_attempted' => false,
            'process_start_ok' => null,
            'circuit_cleared' => false,
            'after_available' => null,
            'after_reachable' => null,
        ];

        if ($before['available_for_search']) {
            // Recovered — clear any open write-circuit so SearchBatchJob can drain.
            try {
                app(PropertySearchSync::class)->clearMeilisearchCircuit();
                $actions['circuit_cleared'] = true;
            } catch (Throwable) {
            }

            return $actions;
        }

        $this->logDown($before, 'heal');

        // Drop negative health cache so the next web request re-probes promptly.
        SerikCache::forget(self::HEALTH_CACHE_KEY);

        try {
            app(PropertySearchSync::class)->clearMeilisearchCircuit();
            $actions['circuit_cleared'] = true;
        } catch (Throwable) {
        }

        if (! $before['reachable']) {
            $serviceResult = $this->tryStartWindowsService();
            if ($serviceResult !== null) {
                $actions['service_start_attempted'] = true;
                $actions['service_start_ok'] = $serviceResult;
            }

            if (filter_var(config('serik.health.meili_heal.auto_start_process', false), FILTER_VALIDATE_BOOLEAN)) {
                $proc = $this->tryStartProcess();
                $actions['process_start_attempted'] = true;
                $actions['process_start_ok'] = $proc;
            }
        }

        // Brief pause if we attempted a start so the HTTP listener can bind.
        if ($actions['service_start_attempted'] || $actions['process_start_attempted']) {
            usleep(800_000);
        }

        $after = $this->probe(bypassCache: true);
        $actions['after_available'] = $after['available_for_search'];
        $actions['after_reachable'] = $after['reachable'];
        $actions['after_reason'] = $after['reason'];
        $actions['documents'] = $after['documents'];

        if ($after['available_for_search']) {
            Log::channel('reliability')->info('[MEILI] recovered after heal', $actions);
            SerikAuditLog::event(SerikAuditLog::DOMAIN_SEARCH, 'meili_recovered', $actions);
        } else {
            Log::channel('reliability')->warning('[MEILI] still unavailable after heal — search remains on MySQL fallback', $actions);
            SerikAuditLog::event(SerikAuditLog::DOMAIN_SEARCH, 'meili_heal_failed', $actions, 'warning');
        }

        return $actions;
    }

    /**
     * Rate-limited warning when search is forced onto MySQL fallback.
     *
     * @param  array<string, mixed>  $probe
     */
    public function logDown(array $probe, string $source = 'search'): void
    {
        $throttleSeconds = max(30, (int) config('serik.health.meili_heal.down_log_seconds', 120));
        try {
            if (! \Illuminate\Support\Facades\Cache::add(self::DOWN_LOG_THROTTLE_KEY, 1, $throttleSeconds)) {
                return;
            }
        } catch (Throwable) {
            // If cache is down, still log once per process via static flag.
            static $logged = false;
            if ($logged) {
                return;
            }
            $logged = true;
        }

        $payload = [
            'source' => $source,
            'host' => $probe['host'] ?? config('scout.meilisearch.host'),
            'reachable' => $probe['reachable'] ?? false,
            'documents' => $probe['documents'] ?? null,
            'reason' => $probe['reason'] ?? 'unknown',
            'hint' => 'Start Meilisearch (ops/windows/Start-SerikMeilisearch.ps1, storage/meilisearch/start-meilisearch.bat, docker compose up -d meilisearch, or NSSM SerikMeilisearch). Search is using MySQL FULLTEXT/LIKE fallback until Meili is healthy.',
        ];

        Log::warning('[MEILI] unavailable — degrading to MySQL search fallback', $payload);
        Log::channel('reliability')->warning('[MEILI] unavailable', $payload);
        SerikAuditLog::event(SerikAuditLog::DOMAIN_SEARCH, 'meili_unavailable', $payload, 'warning');
    }

    private function tryStartWindowsService(): ?bool
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $service = trim((string) config('serik.health.meili_heal.service_name', 'SerikMeilisearch'));
        if ($service === '') {
            return null;
        }

        try {
            $query = [];
            $code = 0;
            exec('sc query "' . str_replace('"', '', $service) . '" 2>&1', $query, $code);
            $out = implode("\n", $query);
            if ($code !== 0 || ! str_contains($out, 'SERVICE_NAME')) {
                return null; // service not installed
            }
            if (str_contains($out, 'RUNNING')) {
                return true;
            }

            $startOut = [];
            $startCode = 0;
            exec('sc start "' . str_replace('"', '', $service) . '" 2>&1', $startOut, $startCode);
            Log::channel('reliability')->info('[MEILI] sc start attempted', [
                'service' => $service,
                'code' => $startCode,
                'out' => mb_substr(implode(' | ', $startOut), 0, 400),
            ]);

            return $startCode === 0 || str_contains(implode("\n", $startOut), 'START_PENDING')
                || str_contains(implode("\n", $startOut), 'RUNNING');
        } catch (Throwable $e) {
            Log::channel('reliability')->warning('[MEILI] service start failed', [
                'service' => $service,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function tryStartProcess(): bool
    {
        $cmd = trim((string) config('serik.health.meili_heal.start_command', ''));
        if ($cmd === '') {
            $bat = base_path('storage/meilisearch/start-meilisearch.bat');
            if (! is_file($bat)) {
                return false;
            }
            $cmd = $bat;
        }

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $escaped = '"' . str_replace('"', '', $cmd) . '"';
                pclose(popen('start /B "" ' . $escaped, 'r'));
            } else {
                exec(escapeshellcmd($cmd) . ' > /dev/null 2>&1 &');
            }

            Log::channel('reliability')->info('[MEILI] process start attempted', ['command' => $cmd]);

            return true;
        } catch (Throwable $e) {
            Log::channel('reliability')->warning('[MEILI] process start failed', [
                'command' => $cmd,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
