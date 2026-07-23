<?php

namespace App\Console\Commands;

use App\Support\SerikQueue;
use App\Support\SerikScheduler;
use App\Support\SerikWindowsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SerikQueueStatusCommand extends Command
{
    protected $signature = 'serik:queue:status
        {--long-running=600 : Flag reserved jobs older than N seconds}
        {--json : Output JSON only}';

    protected $description = 'Queue depths, reserved workers, failed jobs, and long-running jobs';

    public function handle(): int
    {
        $now = time();
        $longRunningAfter = max(60, (int) $this->option('long-running'));
        $queues = [
            'high' => SerikQueue::high(),
            'default' => SerikQueue::default(),
            'images' => SerikQueue::images(),
            'low' => SerikQueue::low(),
        ];

        $depths = [];
        $reserved = [];
        $oldestPending = [];

        foreach ($queues as $label => $name) {
            $depths[$label] = (int) DB::table('jobs')->where('queue', $name)->count();
            $reserved[$label] = (int) DB::table('jobs')
                ->where('queue', $name)
                ->whereNotNull('reserved_at')
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

        $payload = [
            'depths' => $depths,
            'reserved' => $reserved,
            'oldest_pending_sec' => $oldestPending,
            'failed_jobs' => $failed,
            'image_workers' => $imageWorkers,
            'images_active_cache' => (int) \Illuminate\Support\Facades\Cache::get('serik_images_active_jobs', 0),
            'windows_services' => $serviceStates,
            'should_dispatch_image_backfill' => SerikScheduler::shouldDispatchImageBackfill(),
            'should_dispatch_heavy_low' => SerikScheduler::shouldDispatchHeavyLow(),
            'long_running' => $longRunning,
            'images_worker_ok' => $imageWorkers > 0 || $depths['images'] === 0,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT));

            return $payload['images_worker_ok'] ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Serik queue status');
        $this->table(
            ['Queue', 'Pending', 'Reserved (running)', 'Oldest pending (sec)'],
            collect($queues)->map(fn ($name, $label) => [
                $label,
                $depths[$label],
                $reserved[$label],
                $oldestPending[$label],
            ])->values()->all()
        );

        if (SerikWindowsService::isWindows()) {
            $this->newLine();
            $this->info('Windows NSSM services');
            $this->table(
                ['Service', 'Queue', 'State'],
                collect(SerikWindowsService::QUEUE_SERVICES)->map(fn ($serviceName, $label) => [
                    $serviceName,
                    $label,
                    $serviceStates[$label] ?? 'UNKNOWN',
                ])->values()->all()
            );
        }

        $this->line('Failed jobs: ' . $failed);
        $this->line('Image workers: ' . $imageWorkers);
        $this->line('Images in-flight (cache counter): ' . $payload['images_active_cache']);
        $this->line('Dispatch image backfill: ' . ($payload['should_dispatch_image_backfill'] ? 'yes' : 'no'));
        $this->line('Dispatch heavy LOW: ' . ($payload['should_dispatch_heavy_low'] ? 'yes' : 'no'));

        if ($depths['images'] > 0 && $imageWorkers === 0) {
            $this->error('CRITICAL: images queue has pending jobs but SerikQueueImages is not RUNNING.');
            $this->line('Fix: scripts\\windows\\install-serik-queue-images.cmd (Run as Administrator)');
        } elseif ($depths['images'] > 0 && $reserved['images'] === 0 && $imageWorkers > 0) {
            $this->warn('Images worker is running but no jobs are reserved yet — wait a few seconds.');
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
