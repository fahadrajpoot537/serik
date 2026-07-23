<?php

namespace App\Jobs;

use App\Support\GeocodeState;
use App\Support\SerikQueue;
use Botble\RealEstate\Http\Controllers\API\PropertyController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * HIGH lane: AMP recent import → parallel image + geocode/history per property.
 */
class SyncLiveJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    public int $uniqueFor = 120;

    public function __construct(
        public bool $force = false,
        public int $days = 2,
        public int $pages = 2,
        public int $maxSeconds = 40,
        public int $maxNew = 25,
        public int $pageSize = 100,
    ) {
        $this->onQueue(SerikQueue::high());
    }

    public function uniqueId(): string
    {
        return 'serik-sync-live';
    }

    public function handle(PropertyController $controller): void
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');

        $lock = Cache::lock('serik_sync_live_lock', 150);
        if (! $this->force && ! $lock->get()) {
            Log::info('[SyncLiveJob] skipped — already running');

            return;
        }

        if ($this->force) {
            Cache::forget('amp_recent_lock');
            try {
                Cache::lock('serik_sync_live_lock')->forceRelease();
            } catch (Throwable) {
                //
            }
            if (! $lock->get()) {
                $lock = null;
            }
        }

        $started = microtime(true);
        $geocoded = 0;
        $historyQueued = 0;
        $imagesQueued = 0;
        $newIds = [];
        $imageWorkIds = [];
        $high = SerikQueue::high();

        try {
            $response = $controller->importRecentModifiedAmpListings(
                $this->days,
                $this->pages,
                $this->maxSeconds,
                $this->maxNew,
                $this->pageSize
            );
            $payload = json_decode($response->getContent(), true) ?: [];

            $newIds = array_values(array_unique(array_filter(array_map(
                'intval',
                $payload['new_id_list'] ?? []
            ))));

            $imageWorkIds = array_values(array_unique(array_filter(array_map(
                'intval',
                $payload['image_work_id_list'] ?? $newIds
            ))));

            $imagesQueued = count($imageWorkIds);

            Log::info('[SyncLiveJob] import', [
                'status' => $payload['status'] ?? null,
                'pages' => $payload['pages'] ?? 0,
                'created' => $payload['created'] ?? 0,
                'updated' => $payload['updated'] ?? 0,
                'unchanged' => $payload['unchanged'] ?? 0,
                'new_ids' => count($newIds),
                'image_work_ids' => count($imageWorkIds),
                'stopped_early' => $payload['stopped_early'] ?? false,
                'error' => $payload['error'] ?? null,
            ]);

            Cache::put('serik_sync_live_last_result', [
                'status' => $payload['status'] ?? null,
                'pages' => $payload['pages'] ?? 0,
                'created' => $payload['created'] ?? 0,
                'updated' => $payload['updated'] ?? 0,
                'unchanged' => $payload['unchanged'] ?? 0,
                'new_ids' => count($newIds),
                'image_work_ids' => count($imageWorkIds),
                'stopped_early' => $payload['stopped_early'] ?? false,
                'error' => $payload['error'] ?? null,
                'at' => now()->toDateTimeString(),
            ], 600);

            if ($newIds === [] && $imageWorkIds === []) {
                return;
            }

            $needGeo = DB::table('re_properties')
                ->whereIn('id', $newIds)
                ->where(function ($q) {
                    $q->where('latitude', 0)->orWhereNull('latitude')->orWhere('latitude', '0');
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($needGeo !== []) {
                GeocodeState::markQueuedMany($needGeo);

                try {
                    $geoRes = $controller->geocode($needGeo, count($needGeo));
                    $geoBody = json_decode($geoRes->getContent(), true) ?: [];
                    Log::info('[SyncLiveJob] geocode batch', $geoBody);
                } catch (Throwable $e) {
                    Log::warning('[SyncLiveJob] geocode batch failed: ' . $e->getMessage());
                }
            }

            $geocoded = (int) DB::table('re_properties')
                ->whereIn('id', $newIds)
                ->where('latitude', '!=', 0)
                ->whereNotNull('latitude')
                ->where('latitude', '!=', '0')
                ->count();

            // Property sync, geocode, and search indexing run independently of images.

            // Geocode + history chain for brand-new listings only.
            foreach ($newIds as $propertyId) {
                try {
                    GeocodeState::markQueued($propertyId);

                    $geoJob = (new GeocodePropertyJob($propertyId))->allOnQueue($high);
                    $histJob = (new SyncPropertyHistoryJob($propertyId, 8))->onQueue($high);

                    Bus::chain([$geoJob, $histJob])
                        ->onQueue($high)
                        ->catch(function (Throwable $e) use ($propertyId) {
                            Log::error('[SyncLiveJob] chain failed', [
                                'property_id' => $propertyId,
                                'error' => $e->getMessage(),
                            ]);
                        })
                        ->dispatch();

                    $historyQueued++;
                } catch (Throwable $e) {
                    Log::warning('[SyncLiveJob] geocode chain skip #' . $propertyId . ': ' . $e->getMessage());
                }
            }

            Log::info('[SyncLiveJob] complete', [
                'seconds' => round(microtime(true) - $started, 1),
                'imported' => count($newIds),
                'images_queued' => $imagesQueued,
                'geocoded' => $geocoded,
                'history_queued' => $historyQueued,
            ]);
        } finally {
            optional($lock)->release();
            Cache::forget('amp_recent_lock');
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::error('[SyncLiveJob] failed permanently', ['error' => $e?->getMessage()]);
        Cache::forget('amp_recent_lock');
        try {
            Cache::lock('serik_sync_live_lock')->forceRelease();
        } catch (Throwable) {
            //
        }
    }
}
