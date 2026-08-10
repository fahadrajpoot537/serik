<?php

namespace App\Jobs;

use App\Support\ProductionCacheWarmer;
use App\Support\SerikQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Background cache warm on the isolated cache-refresh lane (never blocks HTTP).
 */
class WarmProductionCachesJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public int $timeout = 240;

    public int $uniqueFor = 900;

    public function __construct(
        public bool $light = false,
        public bool $includeHomepage = true,
    ) {
        $this->onQueue(SerikQueue::cacheRefresh());
    }

    public function uniqueId(): string
    {
        return 'serik-warm-production-caches-' . ($this->light ? 'light' : 'full');
    }

    public function handle(): void
    {
        $timings = $this->light
            ? ProductionCacheWarmer::warmLight()
            : ProductionCacheWarmer::warm($this->includeHomepage);

        $totalMs = round(array_sum(array_column($timings, 'ms')), 2);
        $errors = count(array_filter($timings, static fn (array $t): bool => str_starts_with($t['detail'], 'error:')));

        Log::info('[WarmProductionCachesJob] complete', [
            'light' => $this->light,
            'steps' => count($timings),
            'total_ms' => $totalMs,
            'errors' => $errors,
        ]);
    }
}
