<?php

namespace App\Jobs;

use App\Support\SerikQueue;
use Botble\RealEstate\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Theme\homzen\Supports\TrebPropertyHelper;
use Throwable;

/**
 * HIGH lane: fetch address/listing history AFTER geocode (Bus::chain).
 * Caps sibling AMP imports so workers never stall for 10+ minutes.
 */
class SyncPropertyHistoryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 180, 600];

    public int $timeout = 90;

    public function __construct(
        public int $propertyId,
        public int $maxSiblings = 8
    ) {
        $this->onQueue(SerikQueue::high());
    }

    public function handle(): void
    {
        @set_time_limit(90);

        $property = Property::query()->find($this->propertyId);
        if (! $property) {
            return;
        }

        // Safety: never sync history if coords are still missing (chain should
        // already enforce this; this guards manual re-dispatch).
        $lat = (float) ($property->latitude ?? 0);
        if ($lat === 0.0) {
            Log::warning('[SyncPropertyHistoryJob] skipped — missing coords', [
                'property_id' => $this->propertyId,
            ]);

            return;
        }

        $listingKey = strtoupper(trim((string) $property->external_id));
        if ($listingKey === '') {
            return;
        }

        try {
            TrebPropertyHelper::syncAddressHistoryForListing(
                $listingKey,
                false,
                max(1, $this->maxSiblings)
            );
        } catch (Throwable $e) {
            Log::warning('[SyncPropertyHistoryJob] failed', [
                'property_id' => $this->propertyId,
                'listing_key' => $listingKey,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::error('[SyncPropertyHistoryJob] failed permanently', [
            'property_id' => $this->propertyId,
            'error' => $e?->getMessage(),
        ]);
    }
}
