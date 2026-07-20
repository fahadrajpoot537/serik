<?php

namespace App\Jobs;

use App\Services\Geocoding\GeocodingManager;
use App\Support\GeocodeState;
use App\Support\SerikQueue;
use Botble\RealEstate\Http\Controllers\API\PropertyController;
use Botble\RealEstate\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Geocode one property (HIGH live chain, or LOW via properties:geocode).
 * Prefers configured provider (GEOCODING_PROVIDER); falls back to Nominatim/borrow path.
 */
class GeocodePropertyJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 60, 120, 300, 600];

    public int $timeout = 120;

    public int $uniqueFor = 300;

    public function __construct(
        public int $propertyId,
        public bool $useLowQueue = false,
    ) {
        $this->onQueue($useLowQueue ? SerikQueue::low() : SerikQueue::high());
    }

    public function uniqueId(): string
    {
        return 'geocode-property-' . $this->propertyId;
    }

    public function handle(GeocodingManager $geocoder, PropertyController $controller): void
    {
        @set_time_limit(0);

        $lock = Cache::lock(GeocodeState::propertyLockKey($this->propertyId), 180);
        if (! $lock->get()) {
            $this->release(20);

            return;
        }

        try {
            $property = Property::query()->find($this->propertyId);
            if (! $property) {
                return;
            }

            $lat = (float) ($property->latitude ?? 0);
            $lng = (float) ($property->longitude ?? 0);
            if ($lat !== 0.0 && $lng !== 0.0) {
                GeocodeState::markDone($this->propertyId);

                return;
            }

            if ($this->isPermanentlyFailed()) {
                GeocodeState::markFailed($this->propertyId, true);
                $this->fail(new \RuntimeException(
                    'Permanent geocode failure for property ' . $this->propertyId
                ));

                return;
            }

            GeocodeState::markProcessing($this->propertyId);

            $done = false;

            if ($geocoder->isConfigured()) {
                try {
                    $done = $this->geocodeWithProvider($property, $geocoder);
                } catch (Throwable $e) {
                    if (str_contains($e->getMessage(), 'OVER_QUERY_LIMIT')) {
                        $this->release(60);

                        return;
                    }
                    Log::channel('geocoding')->warning('Provider geocode exception, falling back', [
                        'property_id' => $this->propertyId,
                        'provider' => $geocoder->providerName(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (! $done) {
                $result = $controller->geocode([$this->propertyId], 1);
                $body = json_decode($result->getContent(), true) ?: [];

                if (! empty($body['locked']) || ! empty($body['skipped'])) {
                    GeocodeState::markQueued($this->propertyId);
                    $this->release(30);

                    return;
                }
            }

            $property->refresh();
            $lat = (float) ($property->latitude ?? 0);
            if ($lat === 0.0) {
                if ($this->isPermanentlyFailed()) {
                    GeocodeState::markFailed($this->propertyId, true);
                    $this->fail(new \RuntimeException(
                        'Permanent geocode failure for property ' . $this->propertyId
                    ));

                    return;
                }

                throw new \RuntimeException(
                    'Geocode did not produce coordinates for property ' . $this->propertyId
                );
            }

            GeocodeState::markDone($this->propertyId);
        } finally {
            optional($lock)->release();
        }
    }

    public function failed(?Throwable $e): void
    {
        GeocodeState::markFailed($this->propertyId);
        Log::channel('geocoding')->error('[GeocodePropertyJob] failed permanently', [
            'property_id' => $this->propertyId,
            'error' => $e?->getMessage(),
        ]);
    }

    private function geocodeWithProvider(Property $property, GeocodingManager $geocoder): bool
    {
        $address = $geocoder->buildAddress($property);
        $result = $geocoder->geocode($property);

        if ($result === null) {
            Log::channel('geocoding')->info('Provider geocode unsuccessful', [
                'property_id' => $this->propertyId,
                'provider' => $geocoder->providerName(),
                'address' => $address,
                'status' => 'ZERO_RESULTS',
            ]);

            return false;
        }

        $ok = \App\Support\GeocodePersistence::apply(
            $property,
            $result,
            $result['provider'] ?? $geocoder->providerName()
        );

        if (! $ok) {
            return false;
        }

        Log::channel('geocoding')->info('Provider geocode success', [
            'property_id' => $this->propertyId,
            'provider' => $result['provider'] ?? $geocoder->providerName(),
            'address' => $result['searched_address'] ?? $address,
            'status' => $result['status'] ?? 'OK',
            'lat' => $result['lat'] ?? null,
            'lng' => $result['lng'] ?? null,
        ]);

        // Keep Meili map pins in sync (same as Nominatim path).
        try {
            $previous = config('scout.queue');
            config(['scout.queue' => false]);
            if ($property->shouldBeSearchable()) {
                $property->searchable();
            }
            config(['scout.queue' => $previous]);
        } catch (Throwable $e) {
            Log::warning('[GeocodePropertyJob] Meili sync failed: ' . $e->getMessage());
        }

        return true;
    }

    private function isPermanentlyFailed(): bool
    {
        if (! Schema::hasTable('re_geocode_queue')) {
            return false;
        }

        return DB::table('re_geocode_queue')
            ->where('property_id', $this->propertyId)
            ->where('permanent_fail', 1)
            ->exists();
    }
}
