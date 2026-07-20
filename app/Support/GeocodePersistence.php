<?php

namespace App\Support;

use Botble\RealEstate\Models\Property;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Persist geocode coordinates onto re_properties with audited logging.
 * Never overwrites existing non-zero latitude/longitude.
 */
class GeocodePersistence
{
    /**
     * @param  array{
     *   lat: float|int|string,
     *   lng: float|int|string,
     *   formatted_address?: string|null,
     *   location_type?: string|null,
     *   provider?: string|null,
     *   status?: string|null,
     *   searched_address?: string|null,
     *   relevance?: float|null
     * }  $result
     */
    public static function apply(Property $property, array $result, ?string $provider = null): bool
    {
        $propertyId = (int) $property->getKey();
        $newLat = (float) ($result['lat'] ?? 0);
        $newLng = (float) ($result['lng'] ?? 0);
        $provider = $provider
            ?: (string) ($result['provider'] ?? '')
            ?: null;

        if ($newLat === 0.0 && $newLng === 0.0) {
            Log::channel('geocoding')->warning('GeocodePersistence refused empty coordinates', [
                'property_id' => $propertyId,
                'provider' => $provider,
            ]);

            return false;
        }

        $oldLat = (float) ($property->latitude ?? 0);
        $oldLng = (float) ($property->longitude ?? 0);

        Log::channel('geocoding')->info('GeocodePersistence before save', [
            'property_id' => $propertyId,
            'old_latitude' => $oldLat,
            'old_longitude' => $oldLng,
            'new_latitude' => $newLat,
            'new_longitude' => $newLng,
            'provider' => $provider,
            'status' => $result['status'] ?? null,
            'address' => $result['searched_address'] ?? null,
        ]);

        // Already has valid coords — do not overwrite.
        if ($oldLat !== 0.0 && $oldLng !== 0.0) {
            Log::channel('geocoding')->info('GeocodePersistence skipped — coords already present', [
                'property_id' => $propertyId,
                'latitude' => $oldLat,
                'longitude' => $oldLng,
            ]);

            return true;
        }

        $payload = [
            'latitude' => $newLat,
            'longitude' => $newLng,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('re_properties', 'google_formatted_address')) {
            $payload['google_formatted_address'] = $result['formatted_address'] ?? null;
        }
        if (Schema::hasColumn('re_properties', 'google_location_type')) {
            $payload['google_location_type'] = $result['location_type'] ?? null;
        }
        if (Schema::hasColumn('re_properties', 'geocoding_provider') && $provider) {
            $payload['geocoding_provider'] = $provider;
        }
        if (Schema::hasColumn('re_properties', 'geocoded_at')) {
            $payload['geocoded_at'] = now();
        }

        $rowsAffected = 0;
        $sqlLog = [];

        try {
            DB::beginTransaction();

            /** @var object|null $fresh */
            $fresh = DB::table('re_properties')
                ->where('id', $propertyId)
                ->lockForUpdate()
                ->first(['id', 'latitude', 'longitude']);

            if (! $fresh) {
                DB::rollBack();
                Log::channel('geocoding')->error('GeocodePersistence property missing during lock', [
                    'property_id' => $propertyId,
                ]);

                return false;
            }

            $curLat = (float) ($fresh->latitude ?? 0);
            $curLng = (float) ($fresh->longitude ?? 0);
            if ($curLat !== 0.0 && $curLng !== 0.0) {
                DB::commit();
                $property->refresh();
                Log::channel('geocoding')->info('GeocodePersistence skipped — coords already present (locked)', [
                    'property_id' => $propertyId,
                    'latitude' => $curLat,
                    'longitude' => $curLng,
                ]);

                return true;
            }

            DB::flushQueryLog();
            DB::enableQueryLog();

            // Query builder bypasses Eloquent $fillable/$guarded entirely.
            $rowsAffected = DB::table('re_properties')
                ->where('id', $propertyId)
                ->where(function ($q) {
                    $q->whereNull('latitude')
                        ->orWhere('latitude', 0)
                        ->orWhere('latitude', '0');
                })
                ->update($payload);

            $sqlLog = DB::getQueryLog();
            DB::disableQueryLog();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            try {
                DB::disableQueryLog();
            } catch (Throwable) {
                // ignore
            }

            Log::channel('geocoding')->error('GeocodePersistence transaction failed', [
                'property_id' => $propertyId,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return false;
        }

        $property->refresh();

        $freshLat = (float) ($property->latitude ?? 0);
        $freshLng = (float) ($property->longitude ?? 0);
        $ok = $freshLat !== 0.0 && $freshLng !== 0.0;

        Log::channel('geocoding')->info('GeocodePersistence after save', [
            'property_id' => $propertyId,
            'rows_affected' => $rowsAffected,
            'success' => $ok,
            'fresh_latitude' => $freshLat,
            'fresh_longitude' => $freshLng,
            'fresh_provider' => $property->geocoding_provider ?? null,
            'fresh_geocoded_at' => (string) ($property->geocoded_at ?? ''),
            'sql' => $sqlLog,
        ]);

        if ($rowsAffected === 0 && ! $ok) {
            Log::channel('geocoding')->error('GeocodePersistence update returned 0 rows and coords still missing', [
                'property_id' => $propertyId,
                'payload' => $payload,
            ]);
        }

        if (! $ok) {
            Log::channel('geocoding')->error('GeocodePersistence failed — model still has zero coordinates', [
                'property_id' => $propertyId,
                'rows_affected' => $rowsAffected,
                'payload_lat' => $newLat,
                'payload_lng' => $newLng,
            ]);
        }

        return $ok;
    }
}
