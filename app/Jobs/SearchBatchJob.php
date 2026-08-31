<?php

namespace App\Jobs;

use App\Support\PropertySearchSync;
use App\Support\SerikQueue;
use App\Support\SerikQueueMetrics;
use App\Support\SerikSafeLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Global Meilisearch batch drainer. One worker processes a bounded slice of
 * pending property IDs in chunks within a single execution (under the worker
 * lock), then schedules a continuation when work remains.
 */
class SearchBatchJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [15, 45, 120];

    public int $timeout = 300;

    public int $uniqueFor = 3600;

    public function __construct()
    {
        $this->onQueue(SerikQueue::search());
    }

    public function uniqueId(): string
    {
        return 'serik-search-batch-global';
    }

    public function handle(PropertySearchSync $sync): void
    {
        if ($sync->isMeilisearchCircuitOpen()) {
            $delay = max(5, $sync->meilisearchRetryAfterSeconds());
            SerikSafeLog::write('warning', '[SearchBatchJob] Meilisearch cooldown active; retaining pending checkpoint', [
                'pending_count' => $sync->pendingCount(),
                'retry_after_seconds' => $delay,
            ]);
            SerikQueueMetrics::recordRetry(SerikQueue::search());
            $this->release($delay);

            return;
        }

        SerikSafeLog::write('info', '[SearchBatchJob] handle start', [
            'pending_count' => $sync->pendingCount(),
        ]);

        $lock = Cache::lock(PropertySearchSync::WORKER_LOCK_KEY, 600);
        $workerLockAcquired = $lock->get();

        SerikSafeLog::write('info', '[SearchBatchJob] worker lock', [
            'acquired' => $workerLockAcquired,
        ]);

        if (! $workerLockAcquired) {
            SerikSafeLog::write('debug', '[SearchBatchJob] worker lock held — releasing job for retry');
            SerikQueueMetrics::recordRetry(SerikQueue::search());
            $this->release(5);

            return;
        }

        $batches = 0;
        $maxBatches = max(1, (int) config('serik.search_sync.max_batches_per_job', 20));
        $maxSeconds = max(30, min(270, (int) config('serik.search_sync.max_seconds_per_job', 240)));
        $deadline = microtime(true) + $maxSeconds;

        try {
            while ($sync->pendingCount() > 0 && $batches < $maxBatches && microtime(true) < $deadline) {
                $stats = $sync->processNextBatch();
                $batches++;

                if (($stats['property_count'] ?? 0) === 0 && ($stats['remaining_pending'] ?? 0) === 0) {
                    break;
                }
            }
        } catch (Throwable $e) {
            SerikSafeLog::write('warning', '[SearchBatchJob] batch drain failed', [
                'attempt' => $this->attempts(),
                'remaining_pending' => $sync->pendingCount(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            $lock->release();
        }

        if ($sync->pendingCount() > 0) {
            SerikSafeLog::write('info', '[SearchBatchJob] bounded drain yielding continuation', [
                'batches' => $batches,
                'remaining_pending' => $sync->pendingCount(),
            ]);
            self::dispatch();

            return;
        }

        $sync->releaseDispatchGuard();
    }

    public function failed(?Throwable $e): void
    {
        SerikSafeLog::write('error', '[SearchBatchJob] batch drain permanently failed', [
            'error' => $e?->getMessage(),
        ]);

        $sync = app(PropertySearchSync::class);
        if ($sync->pendingCount() === 0) {
            $sync->releaseDispatchGuard();

            return;
        }

        if ($sync->isMeilisearchCircuitOpen()) {
            SerikSafeLog::write('warning', '[SearchBatchJob] permanent failure held by Meilisearch cooldown', [
                'pending_count' => $sync->pendingCount(),
                'retry_after_seconds' => $sync->meilisearchRetryAfterSeconds(),
            ]);

            return;
        }

        $sync->releaseDispatchGuard();
        $sync->dispatchWorkerIfNeeded('permanent_failure_recovery');
    }
}
