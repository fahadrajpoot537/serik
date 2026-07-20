<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Row-level geocoding state machine for re_properties.
 * Does not change AMP/Nominatim business logic — only tracking columns.
 */
final class GeocodeState
{
    public const PENDING = 'pending';

    public const QUEUED = 'queued';

    public const PROCESSING = 'processing';

    public const DONE = 'done';

    public const FAILED = 'failed';

    public static function enabled(): bool
    {
        return Schema::hasColumn('re_properties', 'geocoding_status');
    }

    public static function markQueued(int $propertyId): void
    {
        self::update($propertyId, [
            'geocoding_status' => self::QUEUED,
            'geocode_queued_at' => now(),
        ]);
    }

    public static function markQueuedMany(array $propertyIds): void
    {
        if (! self::enabled() || $propertyIds === []) {
            return;
        }

        DB::table('re_properties')
            ->whereIn('id', $propertyIds)
            ->update([
                'geocoding_status' => self::QUEUED,
                'geocode_queued_at' => now(),
            ]);
    }

    public static function markProcessing(int $propertyId): void
    {
        if (! self::enabled()) {
            return;
        }

        DB::table('re_properties')->where('id', $propertyId)->update([
            'geocoding_status' => self::PROCESSING,
            'geocode_started_at' => now(),
            'geocode_attempts' => DB::raw('geocode_attempts + 1'),
        ]);
    }

    public static function markDone(int $propertyId): void
    {
        self::update($propertyId, [
            'geocoding_status' => self::DONE,
            'geocode_completed_at' => now(),
            'geocode_failed_at' => null,
        ]);
    }

    public static function markFailed(int $propertyId, bool $permanent = false): void
    {
        self::update($propertyId, [
            'geocoding_status' => self::FAILED,
            'geocode_failed_at' => now(),
        ]);
    }

    public static function markPending(int $propertyId): void
    {
        self::update($propertyId, [
            'geocoding_status' => self::PENDING,
            'geocode_queued_at' => null,
            'geocode_started_at' => null,
            'geocode_failed_at' => null,
        ]);
    }

    public static function markPendingMany(array $propertyIds): void
    {
        if (! self::enabled() || $propertyIds === []) {
            return;
        }

        DB::table('re_properties')
            ->whereIn('id', $propertyIds)
            ->update([
                'geocoding_status' => self::PENDING,
                'geocode_queued_at' => null,
                'geocode_started_at' => null,
                'geocode_failed_at' => null,
            ]);
    }

    /**
     * Shared lock key so HIGH and LOW lanes never geocode the same property
     * at the same time (ShouldBeUnique is per-class; this covers cross-lane).
     */
    public static function propertyLockKey(int $propertyId): string
    {
        return 'serik:geocode:property:' . $propertyId;
    }

    private static function update(int $propertyId, array $attributes): void
    {
        if (! self::enabled() || $propertyId <= 0) {
            return;
        }

        try {
            DB::table('re_properties')->where('id', $propertyId)->update($attributes);
        } catch (\Throwable) {
            // best-effort — never break geocode/import path
        }
    }
}
