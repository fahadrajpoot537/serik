<?php

namespace App\Console\Commands;

use App\Jobs\DispatchPendingGhlMlsSyncJob;
use App\Models\GhlMlsSyncTask;
use App\Services\GoHighLevel\GoHighLevelMlsPendingService;
use App\Services\GoHighLevel\GoHighLevelShowingSyncService;
use App\Support\SerikQueue;
use Illuminate\Console\Command;

class ProcessGhlPendingMlsCommand extends Command
{
    protected $signature = 'serik:ghl:process-pending-mls
        {--dispatch : Dispatch pending tasks onto the ghl queue (default)}
        {--sync : Process pending tasks inline (debug only)}
        {--limit= : Max tasks to claim}
        {--enqueue= : Manually enqueue contactId:MLS for testing}
        {--status : Show pending/completed/failed counts}';

    protected $description = 'Process pending GoHighLevel MLS → Showings sync tasks (ghl queue)';

    public function handle(
        GoHighLevelMlsPendingService $pending,
        GoHighLevelShowingSyncService $sync,
    ): int {
        if ($this->option('status')) {
            $counts = GhlMlsSyncTask::query()
                ->selectRaw('status, COUNT(*) as c')
                ->groupBy('status')
                ->pluck('c', 'status')
                ->all();
            $this->table(['status', 'count'], collect($counts)->map(fn ($c, $s) => [$s, $c])->values()->all());

            return self::SUCCESS;
        }

        if ($enqueue = $this->option('enqueue')) {
            [$contactId, $mls] = array_pad(explode(':', (string) $enqueue, 2), 2, null);
            if (! $contactId || ! $mls) {
                $this->error('Use --enqueue=contactId:MLS');

                return self::FAILURE;
            }
            $task = $pending->enqueue(trim($contactId), trim($mls));
            $this->info("Enqueued task #{$task->id} ({$task->external_key}) status={$task->status}");

            // Allow --enqueue=... --sync to process immediately (debug / retry).
            if (! $this->option('sync')) {
                return self::SUCCESS;
            }
        }

        $limit = $this->option('limit') !== null
            ? max(1, (int) $this->option('limit'))
            : max(1, (int) config('gohighlevel.mls_sync.batch_size', 100));

        if ($this->option('sync')) {
            $tasks = GhlMlsSyncTask::query()->pending()->orderBy('id')->limit($limit)->get();
            $ok = 0;
            $fail = 0;
            foreach ($tasks as $task) {
                try {
                    $sync->processTask($task);
                    $ok++;
                    $this->line("OK #{$task->id} {$task->mls_number}");
                } catch (\Throwable $e) {
                    $sync->markFailed($task, $e);
                    $fail++;
                    $this->error("FAIL #{$task->id}: {$e->getMessage()}");
                }
            }
            $this->info("Inline sync done: ok={$ok} fail={$fail}");

            return $fail > 0 ? self::FAILURE : self::SUCCESS;
        }

        // Default: dispatch only (never block schedule / website)
        DispatchPendingGhlMlsSyncJob::dispatch($limit)->onQueue(SerikQueue::ghl());

        $depth = (int) \Illuminate\Support\Facades\DB::table('jobs')
            ->where('queue', SerikQueue::ghl())
            ->count();

        $this->info("Dispatched DispatchPendingGhlMlsSyncJob (limit={$limit}) on queue=" . SerikQueue::ghl());
        $this->line("ghl queue depth now: {$depth}");
        if ($depth === 0) {
            $this->comment('Note: depth 0 usually means a ghl worker already consumed the job, or a unique lock skipped a duplicate DispatchPendingGhlMlsSyncJob still waiting.');
        }

        return self::SUCCESS;
    }
}
