<?php

namespace App\Jobs;

use App\Support\SerikQueue;
use Botble\RealEstate\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Theme\homzen\Supports\TrebPropertyHelper;
use Throwable;

/**
 * HIGH lane: fetch address/listing history AFTER geocode (Bus::chain).
 * Caps sibling AMP imports and wall-clock so workers never stall for 10+ minutes.
 * Listing-history API dispatches this job (never AMP inside the IIS request).
 */
class SyncPropertyHistoryJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 180, 600];

    public int $timeout = 90;

    /** Unique lock held until the job starts processing (dedupe concurrent dispatches). */
    public int $uniqueFor = 180;

    public function __construct(
        public int $propertyId,
        public int $maxSiblings = 8,
        public bool $requireCoordinates = true,
    ) {
        $this->onQueue(SerikQueue::high());
    }

    public function uniqueId(): string
    {
        return 'history-sync-' . $this->propertyId;
    }

    public function handle(): void
    {
        @set_time_limit(90);

        $started = microtime(true);
        $attempt = (int) ($this->attempts() ?: 1);
        $uuid = null;
        try {
            $uuid = $this->job?->uuid();
        } catch (Throwable) {
            $uuid = null;
        }

        $op = 'load_property';
        $listingKey = '';

        Log::info('[SyncPropertyHistoryJob] start', [
            'uuid' => $uuid,
            'job_uuid' => $uuid,
            'property_id' => $this->propertyId,
            'attempt' => $attempt,
            'max_siblings' => $this->maxSiblings,
            'require_coordinates' => $this->requireCoordinates,
        ]);

        try {
            $property = Property::query()->select(['id', 'external_id', 'latitude', 'longitude'])->find($this->propertyId);
            if (! $property) {
                Log::info('[SyncPropertyHistoryJob] skipped — property missing', [
                    'job_uuid' => $uuid,
                    'property_id' => $this->propertyId,
                    'attempt' => $attempt,
                    'duration_ms' => $this->durationMs($started),
                ]);

                return;
            }

            $lat = (float) ($property->latitude ?? 0);
            if ($this->requireCoordinates && $lat === 0.0) {
                Log::warning('[SyncPropertyHistoryJob] skipped — missing coords', [
                    'job_uuid' => $uuid,
                    'property_id' => $this->propertyId,
                    'attempt' => $attempt,
                    'duration_ms' => $this->durationMs($started),
                ]);

                return;
            }

            $listingKey = strtoupper(trim((string) $property->external_id));
            if ($listingKey === '') {
                Log::info('[SyncPropertyHistoryJob] skipped — empty listing key', [
                    'job_uuid' => $uuid,
                    'property_id' => $this->propertyId,
                    'attempt' => $attempt,
                    'duration_ms' => $this->durationMs($started),
                ]);

                return;
            }

            $op = 'acquire_lock';
            $queuedKey = 'serik:history-sync-queued:' . $listingKey;
            $lock = Cache::lock('serik:history-sync:' . $listingKey, 180);
            if (! $lock->get()) {
                Log::info('[SyncPropertyHistoryJob] skipped — lock held', [
                    'job_uuid' => $uuid,
                    'property_id' => $this->propertyId,
                    'listing_key' => $listingKey,
                    'attempt' => $attempt,
                    'duration_ms' => $this->durationMs($started),
                ]);

                return;
            }

            try {
                $op = 'sync_address_history';
                // Stay under job timeout (90s). Windows does not enforce pcntl job timeout.
                $stats = TrebPropertyHelper::syncAddressHistoryForListing(
                    $listingKey,
                    false,
                    max(1, $this->maxSiblings),
                    75
                );

                Log::info('[SyncPropertyHistoryJob] complete', [
                    'job_uuid' => $uuid,
                    'property_id' => $this->propertyId,
                    'listing_key' => $listingKey,
                    'attempt' => $attempt,
                    'duration_ms' => $this->durationMs($started),
                    'amp_found' => $stats['amp_found'] ?? null,
                    'imported' => $stats['imported'] ?? null,
                    'updated' => $stats['updated'] ?? null,
                    'skipped' => $stats['skipped'] ?? null,
                    'history_rows' => $stats['history_rows'] ?? null,
                    'timed_out' => $stats['timed_out'] ?? false,
                    'last_op' => $stats['last_op'] ?? $op,
                ]);
            } finally {
                optional($lock)->release();
                Cache::forget($queuedKey);
            }
        } catch (Throwable $e) {
            Log::warning('[SyncPropertyHistoryJob] failed', [
                'uuid' => $uuid,
                'job_uuid' => $uuid,
                'property_id' => $this->propertyId,
                'listing_key' => $listingKey,
                'attempt' => $attempt,
                'duration' => $this->durationMs($started),
                'duration_ms' => $this->durationMs($started),
                'operation' => $op,
                'exception_class' => $e::class,
                'exception' => $e->getMessage(),
                'exception_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::error('[SyncPropertyHistoryJob] failed permanently', [
            'property_id' => $this->propertyId,
            'exception_class' => $e ? $e::class : null,
            'error' => $e?->getMessage(),
        ]);
    }

    private function durationMs(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
