<?php

namespace App\Jobs;

use App\Services\Treb\TrebArchiveImportService;
use App\Support\SerikQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Enterprise sold-history archive worker (AUTH2).
 * Parallel-safe via TrebArchivePageAllocator leases (no global unique lock).
 */
class ProcessTrebArchiveImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;

    /** @var list<int> */
    public array $backoff;

    public int $timeout;

    public function __construct(
        public bool $reset = false,
        public bool $dryRun = false,
        public ?int $batchSize = null,
        public ?int $maxPages = null,
        public ?int $maxSeconds = null,
    ) {
        $this->onQueue(SerikQueue::imports());
        $this->tries = max(1, (int) config('treb.archive.job_tries', 5));
        $this->timeout = max(60, (int) config('treb.archive.job_timeout', 180));
        $backoff = config('treb.archive.job_backoff', [15, 45, 120, 300]);
        $this->backoff = is_array($backoff) && $backoff !== []
            ? array_values(array_map('intval', $backoff))
            : [15, 45, 120, 300];
    }

    public function handle(TrebArchiveImportService $service): void
    {
        @set_time_limit(0);

        if (! config('treb.archive.enabled', true)) {
            Log::channel('treb_archive')->info('[ProcessTrebArchiveImportJob] skipped — archive import disabled');

            return;
        }

        try {
            $result = $service->run(
                batchSize: $this->batchSize,
                dryRun: $this->dryRun,
                reset: $this->reset,
                maxPages: $this->maxPages,
                maxSeconds: $this->maxSeconds,
            );
        } catch (Throwable $e) {
            Log::channel('treb_archive')->error('[ProcessTrebArchiveImportJob] exception', [
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            throw $e;
        }

        Log::channel('treb_archive')->info('[ProcessTrebArchiveImportJob] finished', [
            'ok' => $result['ok'] ?? false,
            'fetched' => $result['fetched'] ?? 0,
            'imported' => $result['imported'] ?? 0,
            'updated' => $result['updated'] ?? 0,
            'pages' => $result['pages'] ?? 0,
            'has_more' => $result['has_more'] ?? false,
            'elapsed_ms' => $result['elapsed_ms'] ?? 0,
            'rows_per_sec' => (($result['elapsed_ms'] ?? 0) > 0)
                ? round(((int) ($result['fetched'] ?? 0)) / (((int) $result['elapsed_ms']) / 1000), 2)
                : 0,
            'attempt' => $this->attempts(),
        ]);

        if (! ($result['ok'] ?? false) && empty($result['skipped']) && empty($result['idle'])) {
            if (! empty($result['error'])) {
                throw new \RuntimeException((string) $result['error']);
            }
        }

        $hasMore = ! empty($result['has_more']) && empty($result['completed']) && empty($result['idle']);
        if ($hasMore && config('treb.archive.chain_when_more', true) && ! $this->dryRun) {
            $delay = max(0, (int) config('treb.archive.chain_delay_seconds', 1));
            // Cap in-flight chain depth via pending imports queue size.
            $maxParallel = max(1, (int) config('treb.archive.max_parallel_jobs', 4));
            $pending = 0;
            try {
                $pending = (int) \Illuminate\Support\Facades\DB::table('jobs')
                    ->where('queue', SerikQueue::imports())
                    ->count();
            } catch (Throwable) {
                $pending = 0;
            }
            if ($pending < $maxParallel) {
                self::dispatch(
                    reset: false,
                    dryRun: false,
                    batchSize: $this->batchSize,
                    maxPages: $this->maxPages,
                    maxSeconds: $this->maxSeconds,
                )->delay(now()->addSeconds($delay));
            }
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::channel('treb_archive')->error('[ProcessTrebArchiveImportJob] dead-letter / failed', [
            'error' => $e?->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
