<?php

namespace App\Console\Commands;

use App\Support\SerikQueue;
use App\Support\SerikScheduler;
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
            'images_active_cache' => (int) \Illuminate\Support\Facades\Cache::get('serik_images_active_jobs', 0),
            'should_dispatch_image_backfill' => SerikScheduler::shouldDispatchImageBackfill(),
            'should_dispatch_heavy_low' => SerikScheduler::shouldDispatchHeavyLow(),
            'long_running' => $longRunning,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT));

            return self::SUCCESS;
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

        $this->line('Failed jobs: ' . $failed);
        $this->line('Image workers (cache counter): ' . $payload['images_active_cache']);
        $this->line('Dispatch image backfill: ' . ($payload['should_dispatch_image_backfill'] ? 'yes' : 'no'));
        $this->line('Dispatch heavy LOW: ' . ($payload['should_dispatch_heavy_low'] ? 'yes' : 'no'));

        if ($longRunning !== []) {
            $this->warn('Long-running reserved jobs (>' . $longRunningAfter . 's):');
            $this->table(['ID', 'Queue', 'Reserved (sec)', 'Attempts', 'Age (sec)'], array_map(
                fn (array $row) => [$row['id'], $row['queue'], $row['reserved_for_sec'], $row['attempts'], $row['age_sec']],
                $longRunning
            ));
        }

        return self::SUCCESS;
    }
}
