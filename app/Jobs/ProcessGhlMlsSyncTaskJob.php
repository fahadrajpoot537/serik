<?php

namespace App\Jobs;

use App\Models\GhlMlsSyncTask;
use App\Services\GoHighLevel\GoHighLevelMetrics;
use App\Services\GoHighLevel\GoHighLevelShowingSyncService;
use App\Support\SerikQueue;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Process one pending MLS → GHL Showings sync on the dedicated ghl queue.
 */
class ProcessGhlMlsSyncTaskJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    public int $tries;

    /** @var list<int> */
    public array $backoff;

    public int $timeout = 180;

    public int $uniqueFor = 900;

    public function __construct(public int $taskId)
    {
        $this->onQueue(SerikQueue::ghl());
        $this->tries = max(1, (int) config('gohighlevel.mls_sync.job_tries', 8));
        $this->backoff = array_values(array_map(
            'intval',
            (array) config('gohighlevel.mls_sync.job_backoff_seconds', [60, 120, 300, 600])
        ));
    }

    public function uniqueId(): string
    {
        return 'ghl-mls-sync-task-' . $this->taskId;
    }

    public function handle(GoHighLevelShowingSyncService $sync): void
    {
        $t0 = microtime(true);
        $correlationId = GoHighLevelMetrics::correlationId();

        /** @var GhlMlsSyncTask|null $task */
        $task = GhlMlsSyncTask::query()->find($this->taskId);
        if (! $task) {
            Log::channel('ghl_sync')->warning('GoHighLevel MLS sync task missing', [
                'correlation_id' => $correlationId,
                'task_id' => $this->taskId,
            ]);

            return;
        }

        if ($task->status === GhlMlsSyncTask::STATUS_COMPLETED) {
            return;
        }

        $lock = Cache::lock('ghl:task:lock:' . $this->taskId, 200);
        if (! $lock->get()) {
            Log::channel('ghl_sync')->info('GoHighLevel MLS sync lock held — release for retry', [
                'correlation_id' => $correlationId,
                'task_id' => $this->taskId,
            ]);
            $this->release(15);

            return;
        }

        try {
            // Re-read after lock (another worker may have completed).
            $task->refresh();
            if ($task->status === GhlMlsSyncTask::STATUS_COMPLETED) {
                return;
            }

            $sync->processTask($task);
            GoHighLevelMetrics::observeLatency('job_latency', (microtime(true) - $t0) * 1000);
        } catch (Throwable $e) {
            // Keep task retryable until final failed() — do not permanently mark FAILED mid-retry.
            $task->refresh();
            if ($task->status !== GhlMlsSyncTask::STATUS_COMPLETED) {
                $task->status = GhlMlsSyncTask::STATUS_PENDING;
                $task->last_error = mb_substr($e->getMessage(), 0, 2000);
                $task->started_at = null;
                $task->save();
            }

            GoHighLevelMetrics::incrDay('sync_failed');
            GoHighLevelMetrics::markLastFailure($e->getMessage());

            Log::channel('ghl_sync')->warning('GoHighLevel MLS sync attempt failed (will retry)', [
                'correlation_id' => $correlationId,
                'task_id' => $this->taskId,
                'attempt' => $this->attempts(),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            optional($lock)->release();
        }
    }

    public function failed(?Throwable $e): void
    {
        /** @var GhlMlsSyncTask|null $task */
        $task = GhlMlsSyncTask::query()->find($this->taskId);
        if ($task && $task->status !== GhlMlsSyncTask::STATUS_COMPLETED) {
            app(GoHighLevelShowingSyncService::class)->markFailed($task, $e ?? new \RuntimeException('unknown'));
        }

        Log::channel('ghl_sync')->error('GoHighLevel MLS sync job permanently failed', [
            'task_id' => $this->taskId,
            'message' => $e?->getMessage(),
        ]);
    }
}
