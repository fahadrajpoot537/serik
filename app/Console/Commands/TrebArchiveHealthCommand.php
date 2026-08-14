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

    protected $description = 'Show TREB archive sold-import monitoring (progress.json throughput + ETA; no placeholders)';

    public function handle(TrebArchiveImportService $service, TrebArchiveHealthMonitor $monitor): int
    {
        $progress = $service->readProgress();
        $payload = $this->option('benchmark')
            ? $monitor->benchmark($progress)
            : ['metrics' => $monitor->snapshot($progress)];

        if (! isset($payload['monitoring'])) {
            $payload['monitoring'] = $payload['metrics']['monitoring'] ?? null;
        }
        $payload['progress'] = $progress;
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
