<?php

namespace App\Jobs;

use App\Support\PropertySearchSync;
use App\Support\SerikQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Deferred Meilisearch index write for one property.
 * Collapses duplicate dispatches via ShouldBeUniqueUntilProcessing.
 */
class SearchSyncJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [15, 45, 120];

    public int $timeout = 180;

    public int $uniqueFor = 3600;

    public function __construct(
        public int $propertyId,
    ) {
        $this->onQueue(SerikQueue::search());
    }

    public function uniqueId(): string
    {
        return 'search-sync:' . $this->propertyId;
    }

    public function handle(PropertySearchSync $sync): void
    {
        try {
            $sync->syncBatchFor($this->propertyId);
        } catch (Throwable $e) {
            Log::warning('[SearchSyncJob] Meilisearch sync failed', [
                'property_id' => $this->propertyId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::error('[SearchSyncJob] Meilisearch sync permanently failed', [
            'property_id' => $this->propertyId,
            'error' => $e?->getMessage(),
        ]);
    }
}
