<?php

namespace App\Jobs;

use App\Support\SerikQueue;
use App\Support\SerikQueueSelfHealService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Lightweight self-heal on the low lane (never imports). Scheduler only dispatches this.
 */
class RunQueueSelfHealJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue(SerikQueue::low());
    }

    public function handle(SerikQueueSelfHealService $heal): void
    {
        if (! config('serik.orchestration.enabled', true)) {
            return;
        }

        $heal->heal();
    }
}
