<?php

namespace App\Support;

use App\Jobs\SearchSyncJob;
use Botble\RealEstate\Models\Property;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single source of truth for deferred Meilisearch property indexing.
 * Image persistence and other writers schedule sync here — never call searchable() inline.
 */
final class PropertySearchSync
{
    public const PENDING_CACHE_KEY = 'serik:search_sync:pending';

    private const PENDING_LOCK_KEY = 'serik:search_sync:pending:lock';

    /**
     * Queue a deferred index update. Duplicate schedules for the same property collapse
     * to one SearchSyncJob while it is waiting in the queue.
     */
    public function schedule(int $propertyId): void
    {
        if ($propertyId <= 0) {
            return;
        }

        $dispatch = function () use ($propertyId): void {
            $this->markPending($propertyId);
            SearchSyncJob::dispatch($propertyId);
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($dispatch);
        } else {
            $dispatch();
        }
    }

    /**
     * Index the trigger property plus any other pending properties (batched).
     */
    public function syncBatchFor(int $propertyId): void
    {
        $propertyIds = $this->selectBatch($propertyId);

        if ($propertyIds === []) {
            return;
        }

        $properties = Property::query()
            ->whereIn('id', $propertyIds)
            ->get();

        $searchable = $properties->filter(static fn (Property $property): bool => $property->shouldBeSearchable());

        if ($searchable->isEmpty()) {
            $this->releasePending($propertyIds);

            return;
        }

        try {
            $this->indexCollection($searchable);
            $this->releasePending($propertyIds);
        } catch (Throwable $e) {
            Log::warning('[PropertySearchSync] batch index failed — pending IDs retained for retry', [
                'property_ids' => $propertyIds,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        Log::info('[PropertySearchSync] indexed batch', [
            'trigger_property_id' => $propertyId,
            'indexed_count' => $searchable->count(),
            'property_ids' => $searchable->pluck('id')->all(),
        ]);
    }

    /**
     * @return list<int>
     */
    private function selectBatch(int $mustIncludeId): array
    {
        $lock = Cache::lock(self::PENDING_LOCK_KEY, 30);

        if (! $lock->get()) {
            return [$mustIncludeId];
        }

        try {
            /** @var array<int, bool> $pending */
            $pending = Cache::get(self::PENDING_CACHE_KEY, []);
            if (! is_array($pending)) {
                $pending = [];
            }

            $pending[$mustIncludeId] = true;
            Cache::put(self::PENDING_CACHE_KEY, $pending, 86400);

            $batchSize = max(1, (int) config('serik.search_sync.batch_size', 25));
            $selected = [$mustIncludeId];

            foreach (array_keys($pending) as $id) {
                $id = (int) $id;
                if ($id <= 0 || $id === $mustIncludeId) {
                    continue;
                }
                if (count($selected) >= $batchSize) {
                    break;
                }
                $selected[] = $id;
            }

            return array_values(array_unique($selected));
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  list<int>  $propertyIds
     */
    private function releasePending(array $propertyIds): void
    {
        if ($propertyIds === []) {
            return;
        }

        $lock = Cache::lock(self::PENDING_LOCK_KEY, 10);
        $lock->block(5, function () use ($propertyIds): void {
            /** @var array<int, bool> $pending */
            $pending = Cache::get(self::PENDING_CACHE_KEY, []);
            if (! is_array($pending)) {
                $pending = [];
            }

            foreach ($propertyIds as $id) {
                unset($pending[(int) $id]);
            }

            Cache::put(self::PENDING_CACHE_KEY, $pending, 86400);
        });
    }

    private function markPending(int $propertyId): void
    {
        $lock = Cache::lock(self::PENDING_LOCK_KEY, 10);
        $lock->block(5, function () use ($propertyId): void {
            /** @var array<int, bool> $pending */
            $pending = Cache::get(self::PENDING_CACHE_KEY, []);
            if (! is_array($pending)) {
                $pending = [];
            }

            $pending[$propertyId] = true;
            Cache::put(self::PENDING_CACHE_KEY, $pending, 86400);
        });
    }

    /**
     * @param  Collection<int, Property>  $properties
     */
    private function indexCollection(Collection $properties): void
    {
        $previous = config('scout.queue');
        config(['scout.queue' => false]);

        try {
            $properties->searchableSync();
        } catch (Throwable $e) {
            Log::warning('[PropertySearchSync] batch index failed', [
                'property_ids' => $properties->pluck('id')->all(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            config(['scout.queue' => $previous]);
        }
    }
}
