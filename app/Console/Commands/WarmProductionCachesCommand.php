<?php

namespace App\Console\Commands;

use App\Jobs\WarmProductionCachesJob;
use App\Support\ProductionCacheWarmer;
use App\Support\SerikQueue;
use Illuminate\Console\Command;

class WarmProductionCachesCommand extends Command
{
    protected $signature = 'serik:cache:warm
        {--dispatch : Queue WarmProductionCachesJob on cache-refresh (preferred in production)}
        {--light : Lighter warm (map + popular places only)}
        {--skip-homepage : Skip homepage warmer steps}
        {--json : Output JSON only}';

    protected $description = 'Background-safe warm of homepage, map, Ontario SEO, and place-search caches';

    public function handle(): int
    {
        $light = (bool) $this->option('light');
        $includeHomepage = ! (bool) $this->option('skip-homepage');

        if ($this->option('dispatch')) {
            WarmProductionCachesJob::dispatch($light, $includeHomepage)
                ->onQueue(SerikQueue::cacheRefresh());
            $this->info('Dispatched WarmProductionCachesJob on queue=' . SerikQueue::cacheRefresh()
                . ' light=' . ($light ? '1' : '0'));

            return self::SUCCESS;
        }

        $timings = $light
            ? ProductionCacheWarmer::warmLight()
            : ProductionCacheWarmer::warm($includeHomepage);
        $totalMs = round(array_sum(array_column($timings, 'ms')), 2);

        if ($this->option('json')) {
            $this->line(json_encode([
                'total_ms' => $totalMs,
                'steps' => $timings,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Production caches warmed in ' . $totalMs . ' ms');
        $this->table(
            ['Step', 'ms', 'Detail'],
            array_map(static fn (array $row): array => [
                $row['step'],
                $row['ms'],
                $row['detail'],
            ], $timings)
        );

        return self::SUCCESS;
    }
}
