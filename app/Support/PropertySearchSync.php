<?php

namespace App\Support;

use App\Jobs\SearchBatchJob;
use Botble\RealEstate\Models\Property;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single source of truth for deferred Meilisearch property indexing.
 * Writers call schedule() only — never searchable() inline.
 */
final class PropertySearchSync
{
    public const PENDING_CACHE_KEY = 'serik:search_sync:pending';

    public const PENDING_LOCK_KEY = 'serik:search_sync:pending:lock';

    public const WORKER_LOCK_KEY = 'serik:search_sync:worker:lock';

    /**
     * Mark a property for deferred indexing and ensure the global batch worker runs.
     * Does not dispatch one job per property.
     */
    public function schedule(int $propertyId): void
    {
        Log::info('[PropertySearchSync] schedule', [
            'property_id' => $propertyId,
        ]);

        if ($propertyId <= 0) {
            return;
        }

        $dispatch = function () use ($propertyId): void {
            $this->markPending($propertyId);
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($dispatch);
        } else {
            $dispatch();
        }
    }

    /**
     * Drain one batch from the pending set. Called only by SearchBatchJob.
     *
     * @return array{
     *     batch_size: int,
     *     property_count: int,
     *     property_ids: list<int>,
     *     meilisearch_duration_ms: float,
     *     remaining_pending: int,
     * }
     */
    public function processNextBatch(): array
    {
        $batchSize = max(1, (int) config('serik.search_sync.batch_size', 25));
        $propertyIds = $this->claimNextBatch($batchSize);

        if ($propertyIds === []) {
            return [
                'batch_size' => $batchSize,
                'property_count' => 0,
                'property_ids' => [],
                'meilisearch_duration_ms' => 0.0,
                'remaining_pending' => $this->pendingCount(),
            ];
        }

        $properties = Property::query()
            ->whereIn('id', $propertyIds)
            ->get();

        $searchable = $properties->filter(static fn (Property $property): bool => $property->shouldBeSearchable());
        $indexedIds = $searchable->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        if ($searchable->isEmpty()) {
            $remaining = $this->pendingCount();

            Log::info('[PropertySearchSync] batch skipped (no searchable properties)', [
                'batch_size' => $batchSize,
                'property_count' => 0,
                'claimed_property_ids' => $propertyIds,
                'meilisearch_duration_ms' => 0.0,
                'remaining_pending' => $remaining,
            ]);

            return [
                'batch_size' => $batchSize,
                'property_count' => 0,
                'property_ids' => [],
                'meilisearch_duration_ms' => 0.0,
                'remaining_pending' => $remaining,
            ];
        }

        $started = microtime(true);

        try {
            $this->indexCollection($searchable);
        } catch (Throwable $e) {
            $this->requeue($propertyIds);

            Log::warning('[PropertySearchSync] batch index failed — IDs requeued', [
                'batch_size' => $batchSize,
                'property_count' => count($indexedIds),
                'property_ids' => $indexedIds,
                'claimed_property_ids' => $propertyIds,
                'remaining_pending' => $this->pendingCount(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        $durationMs = round((microtime(true) - $started) * 1000, 2);
        $remaining = $this->pendingCount();

        Log::info('[PropertySearchSync] batch indexed', [
            'batch_size' => $batchSize,
            'property_count' => $searchable->count(),
            'property_ids' => $indexedIds,
            'meilisearch_duration_ms' => $durationMs,
            'remaining_pending' => $remaining,
        ]);

        return [
            'batch_size' => $batchSize,
            'property_count' => $searchable->count(),
            'property_ids' => $indexedIds,
            'meilisearch_duration_ms' => $durationMs,
            'remaining_pending' => $remaining,
        ];
    }

    public function pendingCount(): int
    {
        /** @var array<int, bool> $pending */
        $pending = Cache::get(self::PENDING_CACHE_KEY, []);

        return is_array($pending) ? count($pending) : 0;
    }

    private function markPending(int $propertyId): void
    {
        Cache::lock(self::PENDING_LOCK_KEY, 10)->block(5, function () use ($propertyId): void {
            /** @var array<int, bool> $pending */
            $pending = Cache::get(self::PENDING_CACHE_KEY, []);
            if (! is_array($pending)) {
                $pending = [];
            }

            $wasEmpty = $pending === [];
            $pendingCountBefore = count($pending);
            $pending[$propertyId] = true;
            $pendingCountAfter = count($pending);
            Cache::put(self::PENDING_CACHE_KEY, $pending, 86400);

            $searchBatchJobDispatched = false;
            if ($wasEmpty) {
                SearchBatchJob::dispatch();
                $searchBatchJobDispatched = true;
            }

            Log::info('[PropertySearchSync] markPending', [
                'property_id' => $propertyId,
                'pending_count_before' => $pendingCountBefore,
                'pending_count_after' => $pendingCountAfter,
                'search_batch_job_dispatched' => $searchBatchJobDispatched,
            ]);
        });
    }

    /**
     * Atomically read and remove up to $batchSize pending property IDs.
     *
     * @return list<int>
     */
    private function claimNextBatch(int $batchSize): array
    {
        return Cache::lock(self::PENDING_LOCK_KEY, 30)->block(10, function () use ($batchSize): array {
            /** @var array<int, bool> $pending */
            $pending = Cache::get(self::PENDING_CACHE_KEY, []);
            if (! is_array($pending) || $pending === []) {
                Log::info('[PropertySearchSync] claimNextBatch', [
                    'claimed_property_ids' => [],
                    'claimed_count' => 0,
                    'remaining_pending_count' => 0,
                ]);

                return [];
            }

            $ids = [];
            foreach (array_keys($pending) as $id) {
                $id = (int) $id;
                if ($id <= 0) {
                    continue;
                }
                $ids[] = $id;
                unset($pending[$id]);
                if (count($ids) >= $batchSize) {
                    break;
                }
            }

            Cache::put(self::PENDING_CACHE_KEY, $pending, 86400);

            Log::info('[PropertySearchSync] claimNextBatch', [
                'claimed_property_ids' => $ids,
                'claimed_count' => count($ids),
                'remaining_pending_count' => count($pending),
            ]);

            return $ids;
        });
    }

    /**
     * @param  list<int>  $propertyIds
     */
    private function requeue(array $propertyIds): void
    {
        if ($propertyIds === []) {
            return;
        }

        Cache::lock(self::PENDING_LOCK_KEY, 10)->block(5, function () use ($propertyIds): void {
            /** @var array<int, bool> $pending */
            $pending = Cache::get(self::PENDING_CACHE_KEY, []);
            if (! is_array($pending)) {
                $pending = [];
            }

            foreach ($propertyIds as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $pending[$id] = true;
                }
            }

            Cache::put(self::PENDING_CACHE_KEY, $pending, 86400);
        });
    }

    /**
     * @param  Collection<int, Property>  $properties
     */
    private function indexCollection(Collection $properties): void
    {
        $propertyIds = $properties->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        Log::info('[PropertySearchSync] searchableSync start', [
            'collection_size' => $properties->count(),
            'property_ids' => $propertyIds,
        ]);

        $previous = config('scout.queue');
        config(['scout.queue' => false]);

        try {
            $properties->searchableSync();
        } finally {
            config(['scout.queue' => $previous]);
        }

        Log::info('[PropertySearchSync] searchableSync complete', [
            'indexed_count' => $properties->count(),
            'remaining_pending' => $this->pendingCount(),
        ]);
    }
}
