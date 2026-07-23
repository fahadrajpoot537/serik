<?php

namespace App\Jobs;

use App\Queue\Middleware\LimitConcurrentImageJobs;
use App\Support\SerikQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queue worker entry point for listing image persistence.
 * All dispatching must go through ListingImagePipeline.
 */
class PersistTrebImagesJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 60, 120];

    public int $timeout = 300;

    public int $uniqueFor = 3600;

    public function __construct(
        public int $propertyId,
    ) {
        $this->onQueue(SerikQueue::images());
    }

    /**
     * @internal Called only from ListingImagePipeline::enqueue().
     */
    public static function enqueue(int $propertyId): void
    {
        self::dispatch($propertyId);
    }

    public function uniqueId(): string
    {
        return 'persist-treb-images:' . $this->propertyId;
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new LimitConcurrentImageJobs];
    }

    public function handle(): void
    {
        // Image processing disabled — drain legacy queue rows without work.
    }
}
