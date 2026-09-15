<?php

namespace App\Console\Commands;

use App\Jobs\DispatchPendingGhlMlsSyncJob;
use App\Models\GhlMlsSyncTask;
use App\Services\GoHighLevel\GoHighLevelMlsPendingService;
use App\Services\GoHighLevel\GoHighLevelShowingSyncService;
use App\Support\SerikQueue;
use App\Support\SerikWindowsService;
use Illuminate\Console\Command;

class ProcessGhlPendingMlsCommand extends Command
{
    protected $signature = 'serik:ghl:process-pending-mls
        {--dispatch : Dispatch pending tasks onto the ghl queue (default)}
        {--sync : Process pending tasks inline (debug only)}
        {--limit= : Max tasks to claim}
        {--enqueue= : Manually enqueue contactId:MLS or showing:RECORD_ID:MLS}
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
            $parsed = $this->parseEnqueueOption((string) $enqueue);
            if ($parsed === null) {
                $this->error('Use --enqueue=contactId:MLS or --enqueue=showing:RECORD_ID:MLS');

                return self::FAILURE;
            }

            $task = $pending->enqueue(
                $parsed['contact_id'],
                $parsed['mls'],
                null,
                [],
                $parsed['showing_record_id'],
            );
            $this->info("Enqueued task #{$task->id} ({$task->external_key}) status={$task->status}");
            if ($parsed['showing_record_id']) {
                $this->line('showing_record_id=' . $parsed['showing_record_id']);
            }

            // Targeted debug: --enqueue + --sync processes only this contact+MLS pair.
            if ($this->option('sync')) {
                try {
                    $sync->processTask($task);
                    $this->line("OK #{$task->id} {$task->mls_number}");
                    $this->info('Inline sync done: ok=1 fail=0');

                    return self::SUCCESS;
                } catch (\Throwable $e) {
                    $sync->markFailed($task, $e);
                    $this->error("FAIL #{$task->id}: {$e->getMessage()}");
                    $this->info('Inline sync done: ok=0 fail=1');

                    return self::FAILURE;
                }
            }

            return self::SUCCESS;
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

        if (SerikWindowsService::isWindows()
            && ! SerikWindowsService::isRunning(SerikWindowsService::QUEUE_SERVICES['ghl'])) {
            $this->warn('SerikQueueGhl is not RUNNING — auto MLS push will sit in the ghl queue.');
            $this->comment('Fix: run scripts\\windows\\deploy-all-queue-workers.cmd as Administrator');
            $this->comment('Or process now: php artisan serik:ghl:process-pending-mls --sync');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{contact_id: string, mls: string, showing_record_id: ?string}|null
     */
    private function parseEnqueueOption(string $enqueue): ?array
    {
        $enqueue = trim($enqueue);
        if ($enqueue === '') {
            return null;
        }

        // showing:RECORD_ID:MLS  — Showings custom object row (not a Contact)
        if (preg_match('/^showing:(.+):([A-Za-z]\d+)$/i', $enqueue, $m)) {
            $showingId = trim($m[1]);
            $mls = strtoupper(trim($m[2]));
            if ($showingId === '' || $mls === '') {
                return null;
            }

            return [
                'contact_id' => '',
                'mls' => $mls,
                'showing_record_id' => $showingId,
            ];
        }

        // contactId:MLS
        [$contactId, $mls] = array_pad(explode(':', $enqueue, 2), 2, null);
        $contactId = trim((string) $contactId);
        $mls = strtoupper(trim((string) $mls));
        if ($contactId === '' || $mls === '') {
            return null;
        }

        return [
            'contact_id' => $contactId,
            'mls' => $mls,
            'showing_record_id' => null,
        ];
    }
}
