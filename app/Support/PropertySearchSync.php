<?php

namespace App\Support;

use App\Jobs\SearchBatchJob;
use Botble\RealEstate\Models\Property;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Single source of truth for deferred Meilisearch property indexing.
 * Writers call schedule() only — never searchable() inline.
 *
 * Cache remains the hot pending set; DB checkpoints enable crash recovery.
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
        Log::debug('[PropertySearchSync] schedule', [
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
     * Bulk schedule many property IDs for deferred Meilisearch indexing (one lock, one job).
     *
     * @param  list<int>  $propertyIds
     */
    public function scheduleMany(array $propertyIds): void
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $propertyIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($ids === []) {
            return;
        }

        $dispatch = function () use ($ids): void {
            $this->markPendingMany($ids);
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
            $this->clearInflight($propertyIds);
            $remaining = $this->pendingCount();

            Log::debug('[PropertySearchSync] batch skipped (no searchable properties)', [
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
            $this->clearInflight($propertyIds);
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

            SerikAuditLog::event(SerikAuditLog::DOMAIN_SEARCH, 'index_failed', [
                'property_ids' => $propertyIds,
                'error' => $e->getMessage(),
            ], 'warning');

            throw $e;
        }

        $durationMs = round((microtime(true) - $started) * 1000, 2);
        $remaining = $this->pendingCount();

        Log::debug('[PropertySearchSync] batch indexed', [
            'batch_size' => $batchSize,
            'property_count' => $searchable->count(),
            'property_ids' => $indexedIds,
            'meilisearch_duration_ms' => $durationMs,
            'remaining_pending' => $remaining,
        ]);

        SerikAuditLog::event(SerikAuditLog::DOMAIN_SEARCH, 'batch_indexed', [
            'count' => $searchable->count(),
            'duration_ms' => $durationMs,
            'remaining' => $remaining,
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

    /**
     * Restore cache pending set from durable DB checkpoint (cache flush / crash recovery).
     *
     * @return array{restored: int, requeued_inflight: int}
     */
    public function recoverFromCheckpoint(): array
    {
        $restored = 0;
        $requeuedInflight = 0;

        if (! $this->checkpointsEnabled()) {
            return ['restored' => 0, 'requeued_inflight' => 0];
        }

        $staleMinutes = max(5, (int) config('serik.search_sync.inflight_stale_minutes', 30));

        try {
            $staleIds = DB::table('serik_search_sync_inflight')
                ->where('claimed_at', '<', now()->subMinutes($staleMinutes))
                ->pluck('property_id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            if ($staleIds !== []) {
                $this->requeue($staleIds);
                $requeuedInflight = count($staleIds);
            }

            $pendingIds = DB::table('serik_search_sync_pending')
                ->orderBy('property_id')
                ->limit(50000)
                ->pluck('property_id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            if ($pendingIds !== []) {
                Cache::lock(self::PENDING_LOCK_KEY, 30)->block(10, function () use ($pendingIds, &$restored): void {
                    /** @var array<int, bool> $pending */
                    $pending = Cache::get(self::PENDING_CACHE_KEY, []);
                    if (! is_array($pending)) {
                        $pending = [];
                    }
                    $before = count($pending);
                    foreach ($pendingIds as $id) {
                        if ($id > 0) {
                            $pending[$id] = true;
                        }
                    }
                    Cache::put(self::PENDING_CACHE_KEY, $pending, 86400);
                    $restored = max(0, count($pending) - $before);

                    if ($before === 0 && $pending !== []) {
                        SearchBatchJob::dispatch();
                    }
                });
            }
        } catch (Throwable $e) {
            Log::warning('[PropertySearchSync] recoverFromCheckpoint failed', ['error' => $e->getMessage()]);
        }

        SerikAuditLog::event(SerikAuditLog::DOMAIN_SEARCH, 'checkpoint_recover', [
            'restored' => $restored,
            'requeued_inflight' => $requeuedInflight,
        ]);

        return ['restored' => $restored, 'requeued_inflight' => $requeuedInflight];
    }

    private function markPending(int $propertyId): void
    {
        $this->markPendingMany([$propertyId]);
    }

    /**
     * @param  list<int>  $propertyIds
     */
    private function markPendingMany(array $propertyIds): void
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

            $wasEmpty = $pending === [];
            $pendingCountBefore = count($pending);

            foreach ($propertyIds as $propertyId) {
                $pending[(int) $propertyId] = true;
            }

            $pendingCountAfter = count($pending);
            Cache::put(self::PENDING_CACHE_KEY, $pending, 86400);
            $this->persistPendingCheckpoint($propertyIds, remove: false);
            $this->clearInflight($propertyIds);

            $searchBatchJobDispatched = false;
            if ($wasEmpty) {
                SearchBatchJob::dispatch();
                $searchBatchJobDispatched = true;
            }

            Log::debug('[PropertySearchSync] markPendingMany', [
                'scheduled_count' => count($propertyIds),
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
                Log::debug('[PropertySearchSync] claimNextBatch', [
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
            $this->persistPendingCheckpoint($ids, remove: true);
            $this->markInflight($ids);

            Log::debug('[PropertySearchSync] claimNextBatch', [
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
            $this->persistPendingCheckpoint($propertyIds, remove: false);
            $this->clearInflight($propertyIds);
        });
    }

    /**
     * @param  list<int>  $propertyIds
     */
    private function persistPendingCheckpoint(array $propertyIds, bool $remove): void
    {
        if (! $this->checkpointsEnabled() || $propertyIds === []) {
            return;
        }

        try {
            if ($remove) {
                DB::table('serik_search_sync_pending')->whereIn('property_id', $propertyIds)->delete();

                return;
            }

            $now = now();
            $rows = [];
            foreach ($propertyIds as $id) {
                $id = (int) $id;
                if ($id <= 0) {
                    continue;
                }
                $rows[] = [
                    'property_id' => $id,
                    'queued_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows !== []) {
                DB::table('serik_search_sync_pending')->upsert($rows, ['property_id'], ['updated_at']);
            }
        } catch (Throwable $e) {
            Log::debug('[PropertySearchSync] checkpoint write skipped', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param  list<int>  $propertyIds
     */
    private function markInflight(array $propertyIds): void
    {
        if (! $this->checkpointsEnabled() || $propertyIds === []) {
            return;
        }

        try {
            $now = now();
            $token = substr(bin2hex(random_bytes(8)), 0, 16);
            $rows = [];
            foreach ($propertyIds as $id) {
                $id = (int) $id;
                if ($id <= 0) {
                    continue;
                }
                $rows[] = [
                    'property_id' => $id,
                    'claimed_at' => $now,
                    'worker_token' => $token,
                ];
            }
            if ($rows !== []) {
                DB::table('serik_search_sync_inflight')->upsert($rows, ['property_id'], ['claimed_at', 'worker_token']);
            }
        } catch (Throwable $e) {
            Log::debug('[PropertySearchSync] inflight write skipped', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @param  list<int>  $propertyIds
     */
    private function clearInflight(array $propertyIds): void
    {
        if (! $this->checkpointsEnabled() || $propertyIds === []) {
            return;
        }

        try {
            DB::table('serik_search_sync_inflight')->whereIn('property_id', $propertyIds)->delete();
        } catch (Throwable) {
        }
    }

    private function checkpointsEnabled(): bool
    {
        static $enabled = null;
        if ($enabled !== null) {
            return $enabled;
        }

        try {
            $enabled = Schema::hasTable('serik_search_sync_pending')
                && Schema::hasTable('serik_search_sync_inflight');
        } catch (Throwable) {
            $enabled = false;
        }

        return $enabled;
    }

    /**
     * @param  Collection<int, Property>  $properties
     */
    private function indexCollection(Collection $properties): void
    {
        $propertyIds = $properties->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        Log::debug('[PropertySearchSync] searchableSync start', [
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

        Log::debug('[PropertySearchSync] searchableSync complete', [
            'indexed_count' => $properties->count(),
            'remaining_pending' => $this->pendingCount(),
        ]);
    }
}
