<?php

namespace App\Jobs;

use App\Support\SerikQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Run a long Artisan command on the LOW queue so schedule:run stays &lt;2s.
 */
class RunArtisanOnLowQueueJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 7200;

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public string $artisanCommand,
        public array $parameters = [],
    ) {
        $this->onQueue(SerikQueue::low());
    }

    public function handle(): void
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        Log::info('[RunArtisanOnLowQueueJob] start', ['command' => $this->artisanCommand]);
        Artisan::call($this->artisanCommand, $this->parameters);
        Log::info('[RunArtisanOnLowQueueJob] done', [
            'command' => $this->artisanCommand,
            'output' => mb_substr(Artisan::output(), 0, 2000),
        ]);
    }

    public function failed(?Throwable $e): void
    {
        Log::error('[RunArtisanOnLowQueueJob] failed', [
            'command' => $this->artisanCommand,
            'error' => $e?->getMessage(),
        ]);
    }
}
