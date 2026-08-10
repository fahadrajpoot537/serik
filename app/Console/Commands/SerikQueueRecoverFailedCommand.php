<?php

namespace App\Console\Commands;

use App\Support\SerikQueueSelfHealService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Dead-letter (failed_jobs) recovery with exponential backoff metadata.
 */
class SerikQueueRecoverFailedCommand extends Command
{
    protected $signature = 'serik:queue:recover-failed
        {--limit=25 : Max failed jobs to retry}
        {--all : Retry all failed jobs via queue:retry all (no backoff)}
        {--prune : Also prune old failed rows}';

    protected $description = 'Requeue failed_jobs (dead-letter) with exponential backoff tracking';

    public function handle(SerikQueueSelfHealService $heal): int
    {
        if ($this->option('all')) {
            Artisan::call('queue:retry', ['id' => ['all']]);
            $this->info(trim(Artisan::output()));

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        config(['serik.orchestration.failed_requeue_limit' => $limit]);

        $count = $heal->requeueFailedWithBackoff();
        $this->info("Requeued {$count} failed job(s).");

        if ($this->option('prune')) {
            $pruned = $heal->pruneOldFailed();
            $this->info("Pruned {$pruned} old failed job(s).");
        }

        $this->line('Remaining failed_jobs: ' . DB::table('failed_jobs')->count());
        $this->line('Auto-retry markers in cache: serik_failed_retry:*');

        return self::SUCCESS;
    }
}
