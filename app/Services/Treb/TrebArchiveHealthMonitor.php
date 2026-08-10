<?php

namespace App\Services\Treb;

use Illuminate\Support\Facades\Cache;

/**
 * Rolling import health metrics (Memurai/Redis/file Cache).
 */
final class TrebArchiveHealthMonitor
{
    public const METRICS_KEY = 'serik_treb_archive_metrics';

    public const WINDOW_KEY = 'serik_treb_archive_metrics_window';

    /**
     * @param  array{
     *   fetched:int,
     *   imported:int,
     *   updated:int,
     *   skipped:int,
     *   api_ms:int,
     *   db_ms:int,
     *   elapsed_ms:int,
     *   pages:int,
     *   success:bool,
     *   retries?:int
     * }  $sample
     */
    public function record(array $sample): void
    {
        $now = time();
        $fetched = max(0, (int) ($sample['fetched'] ?? 0));
        $elapsedMs = max(1, (int) ($sample['elapsed_ms'] ?? 1));

        Cache::lock('serik_treb_archive_metrics_lock', 10)->block(5, function () use ($sample, $now, $fetched, $elapsedMs) {
            /** @var array<string, mixed> $metrics */
            $metrics = Cache::get(self::METRICS_KEY, []);
            if (! is_array($metrics)) {
                $metrics = [];
            }

            $metrics['total_fetched'] = (int) ($metrics['total_fetched'] ?? 0) + $fetched;
            $metrics['total_imported'] = (int) ($metrics['total_imported'] ?? 0) + (int) ($sample['imported'] ?? 0);
            $metrics['total_updated'] = (int) ($metrics['total_updated'] ?? 0) + (int) ($sample['updated'] ?? 0);
            $metrics['total_skipped'] = (int) ($metrics['total_skipped'] ?? 0) + (int) ($sample['skipped'] ?? 0);
            $metrics['total_pages'] = (int) ($metrics['total_pages'] ?? 0) + (int) ($sample['pages'] ?? 0);
            $metrics['total_api_ms'] = (int) ($metrics['total_api_ms'] ?? 0) + (int) ($sample['api_ms'] ?? 0);
            $metrics['total_db_ms'] = (int) ($metrics['total_db_ms'] ?? 0) + (int) ($sample['db_ms'] ?? 0);
            $metrics['total_runs'] = (int) ($metrics['total_runs'] ?? 0) + 1;
            $metrics['total_success'] = (int) ($metrics['total_success'] ?? 0) + (! empty($sample['success']) ? 1 : 0);
            $metrics['total_failures'] = (int) ($metrics['total_failures'] ?? 0) + (empty($sample['success']) ? 1 : 0);
            $metrics['total_retries'] = (int) ($metrics['total_retries'] ?? 0) + (int) ($sample['retries'] ?? 0);
            $metrics['last_fetched'] = $fetched;
            $metrics['last_elapsed_ms'] = $elapsedMs;
            $metrics['last_api_ms'] = (int) ($sample['api_ms'] ?? 0);
            $metrics['last_db_ms'] = (int) ($sample['db_ms'] ?? 0);
            $metrics['last_rows_per_sec'] = round($fetched / ($elapsedMs / 1000), 2);
            $metrics['last_run_at'] = now()->toIso8601String();
            $metrics['memory_mb'] = round(memory_get_usage(true) / 1048576, 2);
            $metrics['memory_peak_mb'] = round(memory_get_peak_usage(true) / 1048576, 2);

            /** @var list<array{t:int,fetched:int}> $window */
            $window = Cache::get(self::WINDOW_KEY, []);
            if (! is_array($window)) {
                $window = [];
            }
            $window[] = ['t' => $now, 'fetched' => $fetched];
            $window = array_values(array_filter(
                $window,
                static fn ($row): bool => is_array($row) && (($row['t'] ?? 0) >= ($now - 3600))
            ));
            // Cap window size.
            if (count($window) > 500) {
                $window = array_slice($window, -500);
            }

            $hourFetched = 0;
            $minFetched = 0;
            foreach ($window as $row) {
                $hourFetched += (int) ($row['fetched'] ?? 0);
                if (($row['t'] ?? 0) >= ($now - 60)) {
                    $minFetched += (int) ($row['fetched'] ?? 0);
                }
            }

            $metrics['rows_last_min'] = $minFetched;
            $metrics['rows_last_hour'] = $hourFetched;
            $metrics['rows_per_min'] = $minFetched;
            $metrics['rows_per_hour'] = $hourFetched;
            $metrics['rows_per_day_est'] = $hourFetched * 24;
            $metrics['avg_rows_per_sec'] = $metrics['total_runs'] > 0
                ? round(
                    (int) $metrics['total_fetched'] / max(1, ((int) $metrics['total_api_ms'] + (int) $metrics['total_db_ms']) / 1000),
                    2
                )
                : 0.0;
            $metrics['success_rate'] = $metrics['total_runs'] > 0
                ? round(((int) $metrics['total_success'] / (int) $metrics['total_runs']) * 100, 2)
                : 100.0;

            Cache::put(self::METRICS_KEY, $metrics, 86400 * 7);
            Cache::put(self::WINDOW_KEY, $window, 86400);
            Cache::put('serik_treb_archive_last_api_ms', (int) ($sample['api_ms'] ?? 0), 3600);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(array $progress = []): array
    {
        /** @var array<string, mixed> $metrics */
        $metrics = Cache::get(self::METRICS_KEY, []);
        if (! is_array($metrics)) {
            $metrics = [];
        }

        $rowsPerDay = (int) ($metrics['rows_per_day_est'] ?? 0);
        $remainingHint = null;
        $etaHours = null;

        // Rough remaining: assume ~avg rows/year * remaining years if progress known.
        if ($progress !== [] && empty($progress['completed'])) {
            $from = (int) ($progress['from_year'] ?? 0);
            $to = (int) ($progress['to_year'] ?? 0);
            $year = (int) ($progress['year'] ?? $from);
            $eof = is_array($progress['year_eof'] ?? null) ? $progress['year_eof'] : [];
            $yearsLeft = 0;
            for ($y = $year; $y <= $to; $y++) {
                if (empty($eof[(string) $y])) {
                    $yearsLeft++;
                }
            }
            $doneYears = max(0, ($year - $from));
            $fetched = max(1, (int) ($progress['total_fetched'] ?? 1));
            $avgPerYear = $doneYears > 0 ? ($fetched / max(1, $doneYears)) : ($fetched * 2);
            $remainingHint = (int) round($avgPerYear * max(1, $yearsLeft));
            if ($rowsPerDay > 0 && $remainingHint > 0) {
                $etaHours = round(($remainingHint / $rowsPerDay) * 24, 1);
            }
        }

        $metrics['remaining_rows_est'] = $remainingHint;
        $metrics['eta_hours_est'] = $etaHours;
        $metrics['progress'] = [
            'year' => $progress['year'] ?? null,
            'skip' => $progress['skip'] ?? null,
            'completed' => $progress['completed'] ?? false,
            'total_fetched' => $progress['total_fetched'] ?? null,
        ];

        return $metrics;
    }

    /**
     * @param  array<string, mixed>  $progress
     * @return array<string, mixed>
     */
    public function benchmark(array $progress = []): array
    {
        $snap = $this->snapshot($progress);
        $rps = (float) ($snap['last_rows_per_sec'] ?? $snap['avg_rows_per_sec'] ?? 0);
        if ($rps <= 0) {
            $rps = 5.0; // conservative placeholder until real samples exist
        }
        $perDay = (int) round($rps * 86400);

        $targets = [10_000, 100_000, 500_000, 1_000_000];
        $estimates = [];
        foreach ($targets as $n) {
            $seconds = $n / $rps;
            $estimates[(string) $n] = [
                'seconds' => (int) round($seconds),
                'minutes' => round($seconds / 60, 1),
                'hours' => round($seconds / 3600, 2),
            ];
        }

        return [
            'observed_rows_per_sec' => $rps,
            'observed_rows_per_day_est' => $perDay,
            'target_estimates' => $estimates,
            'limiting_factors' => [
                'TREB/AMP API latency and 429 rate limits',
                'imports queue worker count (parallel leases)',
                'MySQL upsert throughput / index on external_id',
                'Deferred Meilisearch catch-up (does not block import)',
            ],
            'metrics' => $snap,
        ];
    }
}
