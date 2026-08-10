<?php

namespace App\Console\Commands;

use App\Support\SerikQueue;
use App\Support\SerikQueueMetrics;
use App\Support\SerikScheduler;
use App\Support\SerikWindowsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SerikQueueStatusCommand extends Command
{
    protected $signature = 'serik:queue:status
        {--long-running=600 : Flag reserved jobs older than N seconds}
        {--json : Output JSON only}
        {--metrics : Include rolling execution / retry metrics}';

    protected $description = 'Queue depths, failed jobs, retries, worker health, and long-running jobs';

    public function handle(): int
    {
        $now = time();
        $longRunningAfter = max(60, (int) $this->option('long-running'));
        $queues = SerikQueue::laneMap();

        $depths = [];
        $reserved = [];
        $oldestPending = [];
        $retriesInFlight = [];

        foreach ($queues as $label => $name) {
            $depths[$label] = (int) DB::table('jobs')->where('queue', $name)->count();
            $reserved[$label] = (int) DB::table('jobs')
                ->where('queue', $name)
                ->whereNotNull('reserved_at')
                ->count();
            $retriesInFlight[$label] = (int) DB::table('jobs')
                ->where('queue', $name)
                ->where('attempts', '>', 0)
                ->count();

            $oldest = DB::table('jobs')
                ->where('queue', $name)
                ->whereNull('reserved_at')
                ->orderBy('available_at')
                ->value('available_at');

            $oldestPending[$label] = $oldest ? max(0, $now - (int) $oldest) : 0;
        }

        $failed = (int) DB::table('failed_jobs')->count();
        $serviceStates = SerikWindowsService::queueServiceStates();
        $imageWorkers = SerikWindowsService::imagesWorkerCount();

        $longRunning = DB::table('jobs')
            ->select(['id', 'queue', 'reserved_at', 'attempts', 'created_at'])
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '<', $now - $longRunningAfter)
            ->orderBy('reserved_at')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'queue' => (string) $row->queue,
                'reserved_for_sec' => $now - (int) $row->reserved_at,
                'attempts' => (int) $row->attempts,
                'age_sec' => $now - (int) $row->created_at,
            ])
            ->all();

        $importsPending = $depths['imports'] ?? 0;
        $userFacingBlockedByImports = false; // by design: separate workers

        $payload = [
            'depths' => $depths,
            'reserved' => $reserved,
            'retries_in_flight' => $retriesInFlight,
            'oldest_pending_sec' => $oldestPending,
            'failed_jobs' => $failed,
            'image_workers' => $imageWorkers,
            'images_active_cache' => (int) \Illuminate\Support\Facades\Cache::get('serik_images_active_jobs', 0),
            'windows_services' => $serviceStates,
            'worker_health' => $serviceStates,
            'should_dispatch_image_backfill' => SerikScheduler::shouldDispatchImageBackfill(),
            'should_dispatch_heavy_low' => SerikScheduler::shouldDispatchHeavyLow(),
            'should_dispatch_imports' => SerikScheduler::shouldDispatchImports(),
            'imports_isolated' => true,
            'imports_pending' => $importsPending,
            'user_facing_blocked_by_imports' => $userFacingBlockedByImports,
            'long_running' => $longRunning,
            'images_worker_ok' => $imageWorkers > 0 || ($depths['images'] ?? 0) === 0,
            'lanes' => $queues,
        ];

        if ($this->option('metrics') || $this->option('json')) {
            $payload['metrics'] = SerikQueueMetrics::snapshot();
        }

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT));

            return $payload['images_worker_ok'] ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Serik queue status');
        $this->table(
            ['Lane', 'Physical', 'Pending', 'Reserved', 'Retries', 'Oldest (sec)'],
            collect($queues)->map(fn ($name, $label) => [
                $label,
                $name,
                $depths[$label],
                $reserved[$label],
                $retriesInFlight[$label],
                $oldestPending[$label],
            ])->values()->all()
        );

        if (SerikWindowsService::isWindows()) {
            $this->newLine();
            $this->info('Windows NSSM worker health');
            $this->table(
                ['Service', 'Lane', 'State'],
                collect(SerikWindowsService::QUEUE_SERVICES)->map(fn ($serviceName, $label) => [
                    $serviceName,
                    $label,
                    $serviceStates[$label] ?? 'UNKNOWN',
                ])->values()->all()
            );
        }

        $this->line('Failed jobs (dead-letter): ' . $failed);
        $this->line('Image workers: ' . $imageWorkers);
        $this->line('Images in-flight (cache counter): ' . $payload['images_active_cache']);
        $this->line('Dispatch image backfill: ' . ($payload['should_dispatch_image_backfill'] ? 'yes' : 'no'));
        $this->line('Dispatch heavy LOW: ' . ($payload['should_dispatch_heavy_low'] ? 'yes' : 'no'));
        $this->line('Dispatch imports: ' . ($payload['should_dispatch_imports'] ? 'yes' : 'no'));
        $this->line('Imports isolated from user-facing: yes');

        if ($this->option('metrics')) {
            $avg = $payload['metrics']['avg_execution_ms'] ?? [];
            $this->line('Processed (1h counter): ' . ($payload['metrics']['processed_last_hour'] ?? 0));
            $this->line('Retry events (1h): ' . ($payload['metrics']['retry_events_last_hour'] ?? 0));
            $this->line('Avg execution ms: ' . json_encode($avg));
        }

        if (($depths['images'] ?? 0) > 0 && $imageWorkers === 0) {
            $this->error('CRITICAL: images queue has pending jobs but SerikQueueImages is not RUNNING.');
            $this->line('Fix: scripts\\windows\\install-serik-queue-images.cmd (Run as Administrator)');
        } elseif (($depths['images'] ?? 0) > 0 && ($reserved['images'] ?? 0) === 0 && $imageWorkers > 0) {
            $this->warn('Images worker is running but no jobs are reserved yet — wait a few seconds.');
        }

        if (($depths['imports'] ?? 0) > 0 && ($serviceStates['imports'] ?? '') !== 'RUNNING') {
            $this->warn('imports queue has pending jobs but SerikQueueImports is not RUNNING.');
            $this->line('Fix: re-run scripts\\windows\\deploy-all-queue-workers.cmd');
        }

        if (($depths['ghl'] ?? 0) > 0 && ($serviceStates['ghl'] ?? '') !== 'RUNNING') {
            $this->warn('ghl queue has pending jobs but SerikQueueGhl is not RUNNING.');
        }

        if (($depths['cache-refresh'] ?? 0) > 0
            && ($serviceStates['cache_refresh'] ?? '') !== 'RUNNING') {
            $this->warn('cache-refresh queue has pending jobs but SerikQueueCacheRefresh is not RUNNING.');
            $this->line('Fix: re-run scripts\\windows\\deploy-all-queue-workers.cmd');
        }

        if ($longRunning !== []) {
            $this->warn('Long-running reserved jobs (>' . $longRunningAfter . 's):');
            $this->table(['ID', 'Queue', 'Reserved (sec)', 'Attempts', 'Age (sec)'], array_map(
                fn (array $row) => [$row['id'], $row['queue'], $row['reserved_for_sec'], $row['attempts'], $row['age_sec']],
                $longRunning
            ));
        }

        return $payload['images_worker_ok'] ? self::SUCCESS : self::FAILURE;
    }
}
