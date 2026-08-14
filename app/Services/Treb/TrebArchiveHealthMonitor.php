<?php

namespace App\Services\Treb;

use Illuminate\Support\Facades\Cache;

/**
 * Archive import monitoring only (Memurai/Redis/file Cache).
 * Does not drive import, queues, or scheduler behaviour.
 */
final class TrebArchiveHealthMonitor
{
    public const METRICS_KEY = 'serik_treb_archive_metrics';

    public const WINDOW_KEY = 'serik_treb_archive_metrics_window';

    /** Rolling progress.json samples for throughput (monitoring only). */
    public const PROGRESS_SAMPLES_KEY = 'serik_treb_archive_progress_samples';

    private const SAMPLE_TTL = 86400 * 3;

    private const SAMPLE_MAX = 3000;

    private const SAMPLE_MIN_INTERVAL_SEC = 20;

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
     * Full monitoring snapshot. Prefer progress.json as authoritative import counters.
     *
     * @param  array<string, mixed>  $progress
     * @return array<string, mixed>
     */
    public function snapshot(array $progress = []): array
    {
        $this->sampleProgress($progress);

        /** @var array<string, mixed> $metrics */
        $metrics = Cache::get(self::METRICS_KEY, []);
        if (! is_array($metrics)) {
            $metrics = [];
        }

        $monitoring = $this->buildMonitoringReport($progress, $metrics);

        // Keep legacy fields, but drive ETA from progress-based throughput (never placeholder).
        $metrics['remaining_rows_est'] = $monitoring['remaining_rows_est'];
        $metrics['eta_hours_est'] = $monitoring['eta']['eta_hours'];
        $metrics['monitoring'] = $monitoring;
        $metrics['progress'] = [
            'year' => $progress['year'] ?? null,
            'skip' => $progress['skip'] ?? null,
            'completed' => $progress['completed'] ?? false,
            'total_fetched' => $progress['total_fetched'] ?? null,
            'total_imported' => $progress['total_imported'] ?? null,
            'last_run_at' => $progress['last_run_at'] ?? null,
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
        $monitoring = is_array($snap['monitoring'] ?? null) ? $snap['monitoring'] : [];
        $throughput = is_array($monitoring['throughput'] ?? null) ? $monitoring['throughput'] : [];
        $rps = (float) ($throughput['rows_per_sec'] ?? 0);

        $targets = [10_000, 100_000, 500_000, 1_000_000];
        $estimates = [];
        if ($rps > 0) {
            foreach ($targets as $n) {
                $seconds = $n / $rps;
                $estimates[(string) $n] = [
                    'seconds' => (int) round($seconds),
                    'minutes' => round($seconds / 60, 1),
                    'hours' => round($seconds / 3600, 2),
                ];
            }
        }

        return [
            'observed_rows_per_sec' => $rps > 0 ? $rps : null,
            'observed_rows_per_day_est' => $rps > 0 ? (int) round($rps * 86400) : null,
            'throughput_window' => $throughput['window_used'] ?? null,
            'placeholder_used' => false,
            'target_estimates' => $estimates,
            'eta' => $monitoring['eta'] ?? null,
            'limiting_factors' => [
                'TREB/AMP API latency and 429 rate limits',
                'imports queue worker count (parallel leases)',
                'MySQL upsert throughput / index on external_id',
                'Deferred Meilisearch catch-up (does not block import)',
            ],
            'metrics' => $snap,
            'monitoring' => $monitoring,
        ];
    }

    /**
     * Append a progress.json sample for rolling throughput (monitoring cache only).
     *
     * @param  array<string, mixed>  $progress
     */
    public function sampleProgress(array $progress): void
    {
        if ($progress === []) {
            return;
        }

        $now = time();
        $imported = (int) ($progress['total_imported'] ?? 0);
        $fetched = (int) ($progress['total_fetched'] ?? 0);
        $lastRunAt = $progress['last_run_at'] ?? null;
        $lastRunTs = is_string($lastRunAt) ? (strtotime($lastRunAt) ?: null) : null;

        Cache::lock('serik_treb_archive_progress_samples_lock', 5)->block(3, function () use ($now, $imported, $fetched, $lastRunTs) {
            /** @var list<array<string, mixed>> $samples */
            $samples = Cache::get(self::PROGRESS_SAMPLES_KEY, []);
            if (! is_array($samples)) {
                $samples = [];
            }

            $last = $samples !== [] ? $samples[count($samples) - 1] : null;
            $lastT = (int) ($last['t'] ?? 0);
            $lastImported = (int) ($last['imported'] ?? -1);

            $changed = $last === null || $imported !== $lastImported;
            $due = ($now - $lastT) >= self::SAMPLE_MIN_INTERVAL_SEC;
            if (! $changed && ! $due) {
                return;
            }

            $samples[] = [
                't' => $now,
                'imported' => $imported,
                'fetched' => $fetched,
                'last_run_ts' => $lastRunTs,
            ];

            $cutoff = $now - 86400;
            $samples = array_values(array_filter(
                $samples,
                static fn ($row): bool => is_array($row) && ((int) ($row['t'] ?? 0)) >= $cutoff
            ));
            if (count($samples) > self::SAMPLE_MAX) {
                $samples = array_slice($samples, -self::SAMPLE_MAX);
            }

            Cache::put(self::PROGRESS_SAMPLES_KEY, $samples, self::SAMPLE_TTL);
        });
    }

    /**
     * @param  array<string, mixed>  $progress
     * @param  array<string, mixed>  $metrics
     * @return array<string, mixed>
     */
    private function buildMonitoringReport(array $progress, array $metrics): array
    {
        $imported = (int) ($progress['total_imported'] ?? 0);
        $fetched = (int) ($progress['total_fetched'] ?? 0);
        $updated = (int) ($progress['total_updated'] ?? 0);
        $completed = ! empty($progress['completed']);

        $rolling = [
            '15m' => $this->rateFromProgressSamples(15 * 60),
            '1h' => $this->rateFromProgressSamples(3600),
            '24h' => $this->rateFromProgressSamples(86400),
        ];

        $throughput = $this->pickBestThroughput($rolling);
        $remaining = $this->estimateRemainingRows($progress);
        $completionPct = $this->completionPercent($progress);

        $runs = max(0, (int) ($metrics['total_runs'] ?? 0));
        $avgApi = $runs > 0 ? round(((int) ($metrics['total_api_ms'] ?? 0)) / $runs, 2) : null;
        $avgDb = $runs > 0 ? round(((int) ($metrics['total_db_ms'] ?? 0)) / $runs, 2) : null;
        $lastBatch = is_array($progress['last_batch'] ?? null) ? $progress['last_batch'] : [];

        $eta = $this->buildEta($remaining, $throughput, $completed);

        return [
            'authoritative_source' => 'progress.json (total_imported / total_fetched / last_run_at)',
            'imported_rows' => $imported,
            'fetched_rows' => $fetched,
            'updated_rows' => $updated,
            'remaining_rows_est' => $remaining,
            'completion_pct' => $completionPct,
            'completed' => $completed,
            'last_run_at' => $progress['last_run_at'] ?? null,
            'current_year' => $progress['year'] ?? null,
            'throughput' => $throughput,
            'rolling' => $rolling,
            'latency' => [
                'avg_api_ms' => $avgApi,
                'avg_db_ms' => $avgDb,
                'last_api_ms' => isset($lastBatch['api_ms']) ? (int) $lastBatch['api_ms'] : ($metrics['last_api_ms'] ?? null),
                'last_db_ms' => isset($lastBatch['db_ms']) ? (int) $lastBatch['db_ms'] : ($metrics['last_db_ms'] ?? null),
            ],
            'eta' => $eta,
            'legacy_job_window' => [
                'note' => 'Fetched-rows window from HealthMonitor::record() only; may be 0 when idle.',
                'rows_last_min' => $metrics['rows_last_min'] ?? 0,
                'rows_last_hour' => $metrics['rows_last_hour'] ?? 0,
            ],
        ];
    }

    /**
     * @param  array{15m:?array,1h:?array,24h:?array}  $rolling
     * @return array<string, mixed>
     */
    private function pickBestThroughput(array $rolling): array
    {
        foreach (['15m', '1h', '24h'] as $window) {
            $rate = $rolling[$window] ?? null;
            if (! is_array($rate)) {
                continue;
            }
            $rps = (float) ($rate['rows_per_sec'] ?? 0);
            $delta = (int) ($rate['delta_imported'] ?? 0);
            $dt = (int) ($rate['delta_seconds'] ?? 0);
            // Prefer windows with real imported growth and enough elapsed time.
            if ($rps > 0 && $delta > 0 && $dt >= 60) {
                return [
                    'rows_per_sec' => $rps,
                    'rows_per_hour' => (int) ($rate['rows_per_hour'] ?? 0),
                    'rows_per_day' => (int) ($rate['rows_per_day'] ?? 0),
                    'window_used' => $window,
                    'delta_imported' => $delta,
                    'delta_seconds' => $dt,
                    'source' => 'progress.json samples',
                ];
            }
        }

        return [
            'rows_per_sec' => null,
            'rows_per_hour' => null,
            'rows_per_day' => null,
            'window_used' => null,
            'delta_imported' => 0,
            'delta_seconds' => 0,
            'source' => 'progress.json samples',
            'reason' => 'Insufficient progress samples with imported growth (need active import + repeated health/status samples).',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function rateFromProgressSamples(int $windowSec): ?array
    {
        /** @var list<array<string, mixed>> $samples */
        $samples = Cache::get(self::PROGRESS_SAMPLES_KEY, []);
        if (! is_array($samples) || count($samples) < 2) {
            return null;
        }

        $now = time();
        $latest = $samples[count($samples) - 1];
        $latestT = (int) ($latest['t'] ?? 0);
        $latestImported = (int) ($latest['imported'] ?? 0);
        if ($latestT <= 0) {
            return null;
        }

        $anchor = null;
        $targetT = $latestT - $windowSec;
        foreach ($samples as $sample) {
            if (! is_array($sample)) {
                continue;
            }
            $t = (int) ($sample['t'] ?? 0);
            if ($t <= $targetT) {
                $anchor = $sample;
            }
        }
        if ($anchor === null) {
            $anchor = $samples[0];
        }

        $anchorT = (int) ($anchor['t'] ?? 0);
        $anchorImported = (int) ($anchor['imported'] ?? 0);
        $dt = $latestT - $anchorT;
        if ($dt < 30) {
            return null;
        }

        $deltaImported = max(0, $latestImported - $anchorImported);
        $rps = $deltaImported / $dt;

        return [
            'rows_per_sec' => round($rps, 4),
            'rows_per_hour' => (int) round($rps * 3600),
            'rows_per_day' => (int) round($rps * 86400),
            'delta_imported' => $deltaImported,
            'delta_seconds' => $dt,
            'anchor_at' => date('c', $anchorT),
            'latest_at' => date('c', $latestT),
            'window_requested_sec' => $windowSec,
        ];
    }

    /**
     * @param  array<string, mixed>  $progress
     */
    private function estimateRemainingRows(array $progress): ?int
    {
        if ($progress === [] || ! empty($progress['completed'])) {
            return 0;
        }

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
        if ($yearsLeft <= 0 || $from <= 0 || $to < $from) {
            return null;
        }

        $doneYears = max(0, $year - $from);
        $fetched = max(1, (int) ($progress['total_fetched'] ?? 1));
        $avgPerYear = $doneYears > 0 ? ($fetched / max(1, $doneYears)) : ($fetched * 2);

        return (int) round($avgPerYear * $yearsLeft);
    }

    /**
     * @param  array<string, mixed>  $progress
     */
    private function completionPercent(array $progress): ?float
    {
        if (! empty($progress['completed'])) {
            return 100.0;
        }
        $from = (int) ($progress['from_year'] ?? 0);
        $to = (int) ($progress['to_year'] ?? 0);
        if ($from <= 0 || $to < $from) {
            return null;
        }
        $totalYears = ($to - $from) + 1;
        $eof = is_array($progress['year_eof'] ?? null) ? $progress['year_eof'] : [];
        $done = 0;
        for ($y = $from; $y <= $to; $y++) {
            if (! empty($eof[(string) $y])) {
                $done++;
            }
        }

        return round(($done / $totalYears) * 100, 2);
    }

    /**
     * @param  array<string, mixed>  $throughput
     * @return array<string, mixed>
     */
    private function buildEta(?int $remaining, array $throughput, bool $completed): array
    {
        if ($completed) {
            return [
                'eta_hours' => 0,
                'seconds' => 0,
                'human' => '0m',
                'estimated_completion_at' => now()->toIso8601String(),
                'reliable' => true,
                'reason' => 'Import marked completed in progress.json',
            ];
        }

        $rps = (float) ($throughput['rows_per_sec'] ?? 0);
        if ($remaining === null) {
            return [
                'eta_hours' => null,
                'seconds' => null,
                'human' => null,
                'estimated_completion_at' => null,
                'reliable' => false,
                'reason' => 'Cannot estimate remaining rows from year_eof / progress.',
            ];
        }
        if ($rps <= 0) {
            return [
                'eta_hours' => null,
                'seconds' => null,
                'human' => null,
                'estimated_completion_at' => null,
                'reliable' => false,
                'reason' => $throughput['reason'] ?? 'No observed progress.json throughput (placeholder disabled).',
            ];
        }
        if ($remaining <= 0) {
            return [
                'eta_hours' => 0,
                'seconds' => 0,
                'human' => '0m',
                'estimated_completion_at' => now()->toIso8601String(),
                'reliable' => true,
                'reason' => 'Remaining estimate is zero.',
            ];
        }

        $seconds = (int) max(0, (int) round($remaining / $rps));
        $hours = round($seconds / 3600, 2);

        return [
            'eta_hours' => $hours,
            'seconds' => $seconds,
            'human' => $this->formatDuration($seconds),
            'estimated_completion_at' => now()->addSeconds($seconds)->toIso8601String(),
            'reliable' => true,
            'reason' => 'ETA = remaining_rows_est / progress-sample rows_per_sec (window=' . ($throughput['window_used'] ?? 'n/a') . ')',
            'window_used' => $throughput['window_used'] ?? null,
        ];
    }

    private function formatDuration(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $parts = [];
        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0 || $days > 0) {
            $parts[] = $hours . 'h';
        }
        $parts[] = $minutes . 'm';

        return implode(' ', $parts);
    }
}
