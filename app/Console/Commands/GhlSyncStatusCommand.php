<?php

namespace App\Console\Commands;

use App\Models\GhlMlsSyncTask;
use App\Services\GoHighLevel\GoHighLevelCircuitBreaker;
use App\Services\GoHighLevel\GoHighLevelMetrics;
use App\Support\SerikQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GhlSyncStatusCommand extends Command
{
    protected $signature = 'serik:ghl:status
        {--json : Output JSON only}';

    protected $description = 'GoHighLevel MLS sync monitoring: depths, success/failure rates, circuit, last sync';

    public function handle(): int
    {
        $counts = GhlMlsSyncTask::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $pendingJobs = 0;
        try {
            $pendingJobs = (int) DB::table('jobs')->where('queue', SerikQueue::ghl())->count();
        } catch (\Throwable) {
            $pendingJobs = 0;
        }

        $lastCompleted = GhlMlsSyncTask::query()
            ->where('status', GhlMlsSyncTask::STATUS_COMPLETED)
            ->orderByDesc('completed_at')
            ->first(['id', 'mls_number', 'contact_id', 'completed_at']);

        $lastFailed = GhlMlsSyncTask::query()
            ->where('status', GhlMlsSyncTask::STATUS_FAILED)
            ->orderByDesc('updated_at')
            ->first(['id', 'mls_number', 'contact_id', 'last_error', 'updated_at']);

        $payload = [
            'enabled' => (bool) config('services.gohighlevel.enabled'),
            'mls_sync_enabled' => (bool) config('gohighlevel.mls_sync.enabled', true),
            'queue' => SerikQueue::ghl(),
            'queue_pending_jobs' => $pendingJobs,
            'task_counts' => $counts,
            'metrics' => GoHighLevelMetrics::snapshot(),
            'circuit' => GoHighLevelCircuitBreaker::snapshot(),
            'last_completed' => $lastCompleted?->toArray(),
            'last_failed' => $lastFailed?->toArray(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('GoHighLevel MLS sync status');
        $this->table(['Key', 'Value'], [
            ['enabled', $payload['enabled'] ? 'yes' : 'no'],
            ['mls_sync_enabled', $payload['mls_sync_enabled'] ? 'yes' : 'no'],
            ['queue', $payload['queue']],
            ['queue_pending_jobs', $payload['queue_pending_jobs']],
            ['circuit', $payload['circuit']['status'] . ' (failures=' . $payload['circuit']['failures'] . ')'],
            ['last_success_at', $payload['metrics']['last_success_at'] ?? '-'],
            ['last_failure_at', $payload['metrics']['last_failure_at'] ?? '-'],
        ]);

        if ($counts !== []) {
            $this->table(
                ['status', 'count'],
                collect($counts)->map(fn ($c, $s) => [$s, $c])->values()->all()
            );
        }

        $hour = $payload['metrics']['hour'] ?? [];
        $this->table(['Hourly metric', 'Value'], [
            ['webhook_received', $hour['webhook_received'] ?? 0],
            ['webhook_accepted', $hour['webhook_accepted'] ?? 0],
            ['webhook_duplicate', $hour['webhook_duplicate'] ?? 0],
            ['webhook_unauthorized', $hour['webhook_unauthorized'] ?? 0],
            ['webhook_ignored_no_mls', $hour['webhook_ignored_no_mls'] ?? 0],
            ['tasks_enqueued', $hour['tasks_enqueued'] ?? 0],
            ['sync_completed', $hour['sync_completed'] ?? 0],
            ['sync_failed', $hour['sync_failed'] ?? 0],
            ['http_retries', $hour['http_retries'] ?? 0],
            ['avg_api_ms', $hour['avg_api_ms'] ?? '-'],
            ['avg_webhook_ms', $hour['avg_webhook_ms'] ?? '-'],
            ['avg_job_ms', $hour['avg_job_ms'] ?? '-'],
        ]);

        return self::SUCCESS;
    }
}
