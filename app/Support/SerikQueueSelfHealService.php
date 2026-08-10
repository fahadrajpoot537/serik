<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Automatic recovery after worker crashes, restarts, reboots, and deployments.
 */
final class SerikQueueSelfHealService
{
    /**
     * @return array<string, mixed>
     */
    public function heal(): array
    {
        $report = [
            'released_stale_reservations' => 0,
            'requeued_failed' => 0,
            'pruned_failed' => 0,
            'queue_restart_signaled' => false,
            'domain_recover' => [],
            'errors' => [],
        ];

        try {
            $report['released_stale_reservations'] = $this->releaseStaleReservations();
        } catch (Throwable $e) {
            $report['errors'][] = 'release: ' . $e->getMessage();
        }

        try {
            $report['requeued_failed'] = $this->requeueFailedWithBackoff();
        } catch (Throwable $e) {
            $report['errors'][] = 'requeue: ' . $e->getMessage();
        }

        try {
            $report['pruned_failed'] = $this->pruneOldFailed();
        } catch (Throwable $e) {
            $report['errors'][] = 'prune: ' . $e->getMessage();
        }

        try {
            if ($this->shouldSignalQueueRestart()) {
                Artisan::call('queue:restart');
                Cache::put('serik_queue_restart_at', now()->timestamp, 86400);
                $report['queue_restart_signaled'] = true;
            }
        } catch (Throwable $e) {
            $report['errors'][] = 'restart: ' . $e->getMessage();
        }

        try {
            $report['domain_recover'] = app(SerikReliabilityService::class)->recoverSafe();
        } catch (Throwable $e) {
            $report['errors'][] = 'domain: ' . $e->getMessage();
        }

        Log::info('SerikQueueSelfHeal', $report);
        SerikAuditLog::event(SerikAuditLog::DOMAIN_QUEUE, 'self_heal', $report);

        return $report;
    }

    /**
     * Workers that died mid-job leave reserved_at set; free them after stale TTL.
     */
    public function releaseStaleReservations(): int
    {
        $staleSeconds = max(120, (int) config('serik.orchestration.stale_reserved_seconds', 900));
        $cutoff = time() - $staleSeconds;

        return (int) DB::table('jobs')
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '<', $cutoff)
            ->update([
                'reserved_at' => null,
                'available_at' => time(),
            ]);
    }

    /**
     * Dead-letter recovery: requeue recent failed jobs with exponential delay.
     */
    public function requeueFailedWithBackoff(): int
    {
        if (! config('serik.orchestration.auto_requeue_failed', true)) {
            return 0;
        }

        $limit = max(1, (int) config('serik.orchestration.failed_requeue_limit', 25));
        $maxAgeHours = max(1, (int) config('serik.orchestration.failed_requeue_max_age_hours', 24));
        $maxRetries = max(1, (int) config('serik.orchestration.failed_max_auto_retries', 5));

        $rows = DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subHours($maxAgeHours))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $requeued = 0;

        foreach ($rows as $row) {
            $uuid = (string) $row->uuid;
            $metaKey = 'serik_failed_retry:' . $uuid;
            $attempts = (int) Cache::get($metaKey, 0);

            if ($attempts >= $maxRetries) {
                continue;
            }

            $delay = $this->exponentialBackoffSeconds($attempts);

            try {
                Artisan::call('queue:retry', ['id' => [$uuid]]);
                Cache::put($metaKey, $attempts + 1, 86400 * 7);

                // Push available_at out for backoff when the job lands back in jobs table.
                $payload = json_decode((string) $row->payload, true);
                $display = is_array($payload) ? (string) ($payload['displayName'] ?? '') : '';
                if ($delay > 0) {
                    DB::table('jobs')
                        ->whereNull('reserved_at')
                        ->where('payload', 'like', '%' . addcslashes($uuid, '%_') . '%')
                        ->limit(1)
                        ->update(['available_at' => time() + $delay]);
                }

                $requeued++;
                SerikQueueMetrics::recordRetry((string) ($row->queue ?? 'default'));
                Log::info('SerikQueueSelfHeal requeued failed job', [
                    'uuid' => $uuid,
                    'attempt' => $attempts + 1,
                    'delay' => $delay,
                    'job' => $display,
                ]);
            } catch (Throwable $e) {
                Log::warning('SerikQueueSelfHeal retry failed', [
                    'uuid' => $uuid,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $requeued;
    }

    public function pruneOldFailed(): int
    {
        $hours = max(24, (int) config('serik.orchestration.failed_prune_hours', 168));

        return (int) DB::table('failed_jobs')
            ->where('failed_at', '<', now()->subHours($hours))
            ->delete();
    }

    protected function exponentialBackoffSeconds(int $attempt): int
    {
        $base = max(30, (int) config('serik.orchestration.retry_base_seconds', 60));
        $cap = max($base, (int) config('serik.orchestration.retry_max_seconds', 3600));

        return (int) min($cap, $base * (2 ** max(0, $attempt)));
    }

    /**
     * After deploy markers / missing restart signal, ask workers to exit gracefully.
     */
    protected function shouldSignalQueueRestart(): bool
    {
        if (! config('serik.orchestration.auto_queue_restart', false)) {
            return false;
        }

        $marker = base_path('storage/framework/queue-restart.flag');
        if (! is_file($marker)) {
            return false;
        }

        $mtime = (int) filemtime($marker);
        $last = (int) Cache::get('serik_queue_restart_at', 0);

        if ($mtime <= $last) {
            return false;
        }

        @unlink($marker);

        return true;
    }
}
