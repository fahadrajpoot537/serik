<?php

namespace App\Jobs;

use App\Support\PropertySearchSync;
use App\Support\SerikQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Global Meilisearch batch drainer. One worker processes all pending property IDs
 * in chunks within a single execution (under the worker lock).
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
        Log::info('[SearchBatchJob] handle start', [
            'pending_count' => $sync->pendingCount(),
        ]);

        $lock = Cache::lock(PropertySearchSync::WORKER_LOCK_KEY, 600);
        $workerLockAcquired = $lock->get();

        Log::info('[SearchBatchJob] worker lock', [
            'acquired' => $workerLockAcquired,
        ]);

        if (! $workerLockAcquired) {
            Log::debug('[SearchBatchJob] worker lock held — releasing job for retry');
            $this->release(5);

            return;
        }

        try {
            while ($sync->pendingCount() > 0) {
                $stats = $sync->processNextBatch();

                if (($stats['property_count'] ?? 0) === 0 && ($stats['remaining_pending'] ?? 0) === 0) {
                    break;
                }
            }
        } catch (Throwable $e) {
            Log::warning('[SearchBatchJob] batch drain failed', [
                'attempt' => $this->attempts(),
                'remaining_pending' => $sync->pendingCount(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::error('[SearchBatchJob] batch drain permanently failed', [
            'error' => $e?->getMessage(),
        ]);

        if (app(PropertySearchSync::class)->pendingCount() > 0) {
            SearchBatchJob::dispatch();
        }
    }
}
