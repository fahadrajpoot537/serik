<?php

namespace App\Jobs;

use App\Support\SerikQueue;
use Botble\RealEstate\Http\Controllers\API\PropertyController;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Services\LiveTrebPropertyFallbackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Theme\homzen\Supports\TrebPropertyHelper;
use Throwable;

/**
 * Completes background import for a listing first seen via live TREB fallback:
 * hydration, media, history, Meilisearch, coordinates.
 */
class ImportLiveTrebPropertyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public int $timeout = 180;

    public function __construct(public string $listingKey)
    {
        $this->listingKey = strtoupper(trim($listingKey));
        $this->onQueue(SerikQueue::high());
    }

    public function handle(): void
    {
        @set_time_limit(180);

        $listingKey = $this->listingKey;
        $started = microtime(true);

        if ($listingKey === '') {
            return;
        }

        $property = Property::query()->where('external_id', $listingKey)->first();

        if ($property === null) {
            app()->instance('serik.live_treb_fallback', true);
            try {
                $property = app(PropertyController::class)->ingestListingFromAmp($listingKey, false, false);
            } finally {
                app()->forgetInstance('serik.live_treb_fallback');
            }
        }

        if ($property === null) {
            Log::warning('[ImportLiveTrebPropertyJob] listing not available locally or in TREB', [
                'listing_key' => $listingKey,
            ]);

            return;
        }

        $hydration = app(PropertyController::class)->ensureListingHydrated($listingKey);

        try {
            TrebPropertyHelper::getPropertyImages($listingKey, $property->image_val ?? null, true);
        } catch (Throwable $e) {
            Log::warning('[ImportLiveTrebPropertyJob] image warm failed', [
                'listing_key' => $listingKey,
                'error' => $e->getMessage(),
            ]);
        }

        $property->refresh();

        PersistTrebImagesJob::dispatch((int) $property->id, true);

        if ((float) ($property->latitude ?? 0) !== 0.0) {
            SyncPropertyHistoryJob::dispatch((int) $property->id);
        }

        try {
            $property->searchable();
        } catch (Throwable $e) {
            Log::warning('[ImportLiveTrebPropertyJob] meilisearch index failed', [
                'listing_key' => $listingKey,
                'error' => $e->getMessage(),
            ]);
        }

        Cache::forget(LiveTrebPropertyFallbackService::LIVE_PENDING_PREFIX . $listingKey);
        Cache::forget('serik:import-live-dispatched:' . $listingKey);

        Log::info('[ImportLiveTrebPropertyJob] complete', [
            'listing_key' => $listingKey,
            'property_id' => $property->id,
            'duration_ms' => round((microtime(true) - $started) * 1000),
            'hydration' => $hydration,
        ]);
    }

    public function failed(?Throwable $e): void
    {
        Log::error('[ImportLiveTrebPropertyJob] failed permanently', [
            'listing_key' => $this->listingKey,
            'error' => $e?->getMessage(),
        ]);
    }
}
