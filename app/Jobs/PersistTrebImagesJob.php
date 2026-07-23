<?php

namespace App\Jobs;

use App\Support\SerikQueue;
use App\Support\TrebImagePersistence;
use App\Support\TrebImageStore;
use Botble\RealEstate\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Persist TREB cover (and optional gallery) WebP for one listing on the LOW queue.
 * Reuses TrebImagePersistence — same path as serik:treb-images-webp.
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

    public int $timeout = 120;

    public int $uniqueFor = 300;

    public function __construct(
        public int $propertyId,
        public bool $withGallery = false,
    ) {
        $this->onQueue(SerikQueue::low());
    }

    public function uniqueId(): string
    {
        return 'persist-treb-images:' . $this->propertyId . ($this->withGallery ? ':gallery' : '');
    }

    public function handle(TrebImagePersistence $persistence, TrebImageStore $store): void
    {
        @set_time_limit(0);

        $property = Property::query()->find($this->propertyId);

        if ($property === null) {
            return;
        }

        $listingKey = strtoupper(trim((string) $property->external_id));

        if ($listingKey === '') {
            return;
        }

        if ($store->storedWebpExists($property->image_val) && ! $this->withGallery) {
            return;
        }

        if ($this->withGallery) {
            $images = is_array($property->images) ? $property->images : [];
            $galleryComplete = $images !== []
                && collect($images)->every(fn ($path) => $store->storedWebpExists(is_string($path) ? $path : null));

            if ($store->storedWebpExists($property->image_val) && $galleryComplete) {
                return;
            }
        }

        try {
            $persistence->persistForProperty($property, $this->withGallery);
        } catch (Throwable $e) {
            Log::warning('[PersistTrebImagesJob] failed', [
                'property_id' => $this->propertyId,
                'listing_key' => $listingKey,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
