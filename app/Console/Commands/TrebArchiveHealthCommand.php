<?php

namespace App\Console\Commands;

use App\Services\Treb\TrebArchiveHealthMonitor;
use App\Services\Treb\TrebArchiveImportService;
use Illuminate\Console\Command;

/**
 * Health + benchmark for the TREB archive sold import engine (read-only).
 */
class TrebArchiveHealthCommand extends Command
{
    protected $signature = 'serik:treb-archive-health
        {--benchmark : Include throughput estimates for 10k/100k/500k/1M}';

    protected $description = 'Show TREB archive sold-import health metrics (rows/sec, ETA, success rate)';

    public function handle(TrebArchiveImportService $service, TrebArchiveHealthMonitor $monitor): int
    {
        $progress = $service->readProgress();
        if ($this->option('benchmark')) {
            $payload = $monitor->benchmark($progress);
        } else {
            $payload = ['metrics' => $monitor->snapshot($progress)];
        }

        $payload['progress'] = $progress;
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
