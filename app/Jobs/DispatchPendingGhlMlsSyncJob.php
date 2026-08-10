<?php

namespace App\Jobs;

use App\Models\GhlMlsSyncTask;
use App\Services\GoHighLevel\GoHighLevelMetrics;
use App\Support\SerikQueue;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Morning dispatcher: claim pending MLS tasks and fan out onto the ghl queue.
 * Never uses the imports queue.
 */
class DispatchPendingGhlMlsSyncJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 300;

    public function __construct(public ?int $limit = null)
    {
        $this->onQueue(SerikQueue::ghl());
    }

    public function uniqueId(): string
    {
        return 'ghl-dispatch-pending-mls';
    }

    public function handle(): void
    {
        if (! config('services.gohighlevel.enabled')) {
            return;
        }

        if (! config('gohighlevel.mls_sync.enabled', true)) {
            return;
        }

        $limit = $this->limit ?? max(1, (int) config('gohighlevel.mls_sync.batch_size', 100));

        $tasks = GhlMlsSyncTask::query()
            ->pending()
            ->orderBy('queued_at')
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'contact_id', 'mls_number']);

        $dispatched = 0;
        foreach ($tasks as $task) {
            ProcessGhlMlsSyncTaskJob::dispatch((int) $task->id)
                ->onQueue(SerikQueue::ghl());
            $dispatched++;
        }

        if ($dispatched > 0) {
            GoHighLevelMetrics::incrDay('tasks_enqueued', $dispatched);
        }

        Log::channel('ghl_sync')->info('GoHighLevel pending MLS dispatch', [
            'dispatched' => $dispatched,
            'limit' => $limit,
        ]);
    }
}
