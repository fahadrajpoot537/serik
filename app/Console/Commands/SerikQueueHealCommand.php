<?php

namespace App\Console\Commands;

use App\Jobs\RunQueueSelfHealJob;
use App\Support\SerikQueue;
use App\Support\SerikQueueLock;
use App\Support\SerikQueueSelfHealService;
use Illuminate\Console\Command;

class SerikQueueHealCommand extends Command
{
    protected $signature = 'serik:queue:heal
        {--sync : Run heal inline (debug); default dispatches to low queue}
        {--dispatch : Force queue dispatch}';

    protected $description = 'Self-heal: release stale reservations, requeue failed with backoff, optional queue:restart';

    public function handle(SerikQueueSelfHealService $heal): int
    {
        if ($this->option('sync')) {
            $report = $heal->heal();
            $this->line(json_encode($report, JSON_PRETTY_PRINT));

            return ($report['errors'] ?? []) === [] ? self::SUCCESS : self::FAILURE;
        }

        SerikQueueLock::dispatchGuard('queue-heal-dispatch', function () {
            RunQueueSelfHealJob::dispatch()->onQueue(SerikQueue::low());

            return true;
        }, 240);

        $this->info('Dispatched RunQueueSelfHealJob on queue=' . SerikQueue::low());

        return self::SUCCESS;
    }
}
