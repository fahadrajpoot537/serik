<?php

namespace App\Services\Treb;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Atomic AMP page leases so multiple imports workers never claim the same (year, skip / keyset cursor).
 * Upserts remain idempotent on external_id; leases only protect cursor advancement.
 */
final class TrebArchivePageAllocator
{
    public const CURSOR_LOCK = 'serik_treb_archive_cursor_lock';

    public function __construct(
        private readonly TrebArchiveImportService $service,
    ) {}

    /**
     * @return array{lease_id:string,year:int,skip:int,chunk:int,after_listing_key:?string}|null
     */
    public function claim(int $chunkSize): ?array
    {
        $chunkSize = max(10, min(500, $chunkSize));
        $leaseTtl = max(60, (int) config('treb.archive.lease_ttl_seconds', 180));

        return Cache::lock(self::CURSOR_LOCK, 20)->block(15, function () use ($chunkSize, $leaseTtl) {
            $progress = $this->service->readProgress();
            $this->purgeExpiredLeases($progress);
            $this->service->purgeIllegalReclaims($progress);

            if (! empty($progress['completed'])) {
                $this->service->writeProgress($progress);

                return null;
            }

            $toYear = (int) ($progress['to_year'] ?? (int) date('Y'));
            $year = (int) ($progress['year'] ?? $progress['from_year'] ?? $toYear);
            $skip = (int) ($progress['skip'] ?? 0);
            $pagingMode = (string) ($progress['paging_mode'] ?? 'skip');
            $afterKey = $this->service->normalizeListingKey($progress['after_listing_key'] ?? null);

            // after_listing_key is authoritative — never fall back to $skip pagination mid-year.
            if ($afterKey !== null) {
                $pagingMode = 'keyset';
                $progress['paging_mode'] = 'keyset';
                $skip = 0;
                $progress['skip'] = 0;
            }

            // Skip years already marked EOF.
            $yearEof = is_array($progress['year_eof'] ?? null) ? $progress['year_eof'] : [];
            while ($year <= $toYear && ! empty($yearEof[(string) $year])) {
                $year++;
                $skip = 0;
                $pagingMode = 'skip';
                $afterKey = null;
            }

            if ($year > $toYear) {
                $progress['completed'] = true;
                $progress['year'] = $toYear;
                $progress['skip'] = 0;
                $progress['paging_mode'] = 'skip';
                $progress['after_listing_key'] = null;
                $this->service->writeProgress($progress);

                return null;
            }

            // Prefer reclaimed pages from crashed workers first (skip-mode only).
            $reclaim = is_array($progress['reclaim_queue'] ?? null) ? $progress['reclaim_queue'] : [];
            while ($reclaim !== [] && $pagingMode !== 'keyset') {
                $item = array_shift($reclaim);
                $progress['reclaim_queue'] = array_values($reclaim);
                $claimYear = (int) ($item['year'] ?? $year);
                $claimSkip = (int) ($item['skip'] ?? 0);
                $claimChunk = max(10, min(500, (int) ($item['chunk'] ?? $chunkSize)));

                if (! empty($yearEof[(string) $claimYear])) {
                    continue;
                }

                // Illegal AMP skips must not be reclaimed — drop and continue.
                if ($this->service->skipWouldExceedAmpLimit($claimSkip, $claimChunk)) {
                    Log::channel('treb_archive')->info('Dropped reclaim past AMP $skip ceiling', [
                        'year' => $claimYear,
                        'skip' => $claimSkip,
                    ]);
                    continue;
                }

                $leaseId = (string) Str::uuid();
                $progress['leases'][$leaseId] = [
                    'year' => $claimYear,
                    'skip' => $claimSkip,
                    'chunk' => $claimChunk,
                    'after_listing_key' => null,
                    'expires_at' => now()->addSeconds($leaseTtl)->getTimestamp(),
                ];
                $this->service->writeProgress($progress);

                return [
                    'lease_id' => $leaseId,
                    'year' => $claimYear,
                    'skip' => $claimSkip,
                    'chunk' => $claimChunk,
                    'after_listing_key' => null,
                ];
            }
            $progress['reclaim_queue'] = array_values($reclaim);

            // Auto-switch to keyset before claiming an illegal $skip.
            if ($pagingMode !== 'keyset' && $this->service->skipWouldExceedAmpLimit($skip, $chunkSize)) {
                $pagingMode = 'keyset';
                $progress['paging_mode'] = 'keyset';
                // after_listing_key may still be null — worker bootstraps via service on claim miss.
                if ($afterKey === null) {
                    $fromBatch = $this->service->normalizeListingKey(
                        $progress['last_batch']['last_listing_key'] ?? null
                    );
                    if ($fromBatch !== null) {
                        $afterKey = $fromBatch;
                        $progress['after_listing_key'] = $afterKey;
                    }
                }
                $progress['skip'] = 0;
                $skip = 0;
            }

            $leaseId = (string) Str::uuid();
            $progress['year'] = $year;

            if ($pagingMode === 'keyset') {
                // Keyset leases do not pre-advance the global cursor (complete() updates after_listing_key).
                // Only one lease per year while in keyset mode (cursor is sequential).
                foreach ($progress['leases'] ?? [] as $existing) {
                    if (! is_array($existing)) {
                        continue;
                    }
                    if ((int) ($existing['year'] ?? -1) === $year) {
                        $this->service->writeProgress($progress);

                        return null;
                    }
                }

                $progress['paging_mode'] = 'keyset';
                $progress['skip'] = 0;
                $progress['leases'] = is_array($progress['leases'] ?? null) ? $progress['leases'] : [];
                $progress['leases'][$leaseId] = [
                    'year' => $year,
                    'skip' => 0,
                    'chunk' => $chunkSize,
                    'after_listing_key' => $afterKey,
                    'paging_mode' => 'keyset',
                    'expires_at' => now()->addSeconds($leaseTtl)->getTimestamp(),
                ];
                $this->service->writeProgress($progress);

                return [
                    'lease_id' => $leaseId,
                    'year' => $year,
                    'skip' => 0,
                    'chunk' => $chunkSize,
                    'after_listing_key' => $afterKey,
                ];
            }

            $progress['paging_mode'] = 'skip';
            $progress['skip'] = $skip + $chunkSize;
            $progress['leases'] = is_array($progress['leases'] ?? null) ? $progress['leases'] : [];
            $progress['leases'][$leaseId] = [
                'year' => $year,
                'skip' => $skip,
                'chunk' => $chunkSize,
                'after_listing_key' => null,
                'expires_at' => now()->addSeconds($leaseTtl)->getTimestamp(),
            ];
            $this->service->writeProgress($progress);

            return [
                'lease_id' => $leaseId,
                'year' => $year,
                'skip' => $skip,
                'chunk' => $chunkSize,
                'after_listing_key' => null,
            ];
        });
    }

    /**
     * @param  array{imported?:int,updated?:int,skipped?:int,duplicates?:int,api_ms?:int,db_ms?:int,last_listing_key?:?string,used_keyset?:bool}  $stats
     */
    public function complete(string $leaseId, int $fetched, array $stats = []): void
    {
        Cache::lock(self::CURSOR_LOCK, 20)->block(15, function () use ($leaseId, $fetched, $stats) {
            $progress = $this->service->readProgress();
            $leases = is_array($progress['leases'] ?? null) ? $progress['leases'] : [];
            $cancelled = is_array($progress['cancelled_leases'] ?? null) ? $progress['cancelled_leases'] : [];

            if (! empty($cancelled[$leaseId])) {
                unset($progress['cancelled_leases'][$leaseId]);
                $this->service->writeProgress($progress);

                return;
            }

            $lease = $leases[$leaseId] ?? null;
            if (! is_array($lease)) {
                return;
            }

            $year = (int) ($lease['year'] ?? 0);
            $skip = (int) ($lease['skip'] ?? 0);
            $chunk = max(1, (int) ($lease['chunk'] ?? 1));
            $leaseIsKeyset = ((string) ($lease['paging_mode'] ?? '') === 'keyset')
                || ! empty($lease['after_listing_key']);
            $usedKeyset = ! empty($stats['used_keyset']) || $leaseIsKeyset
                || ($this->service->normalizeListingKey($progress['after_listing_key'] ?? null) !== null
                    && (string) ($progress['paging_mode'] ?? '') === 'keyset');
            $lastKey = $this->service->normalizeListingKey($stats['last_listing_key'] ?? null);

            unset($progress['leases'][$leaseId]);

            // Ignore stale skip-mode completions while keyset pagination is active.
            if (! $leaseIsKeyset && (string) ($progress['paging_mode'] ?? '') === 'keyset'
                && $this->service->normalizeListingKey($progress['after_listing_key'] ?? null) !== null) {
                $progress['last_error'] = 'ignored_stale_skip_lease';
                $progress['last_run_at'] = now()->toIso8601String();
                $progress['skip'] = 0;
                $this->service->writeProgress($progress);

                return;
            }

            $progress['total_fetched'] = (int) ($progress['total_fetched'] ?? 0) + max(0, $fetched);
            $progress['total_imported'] = (int) ($progress['total_imported'] ?? 0) + (int) ($stats['imported'] ?? 0);
            $progress['total_updated'] = (int) ($progress['total_updated'] ?? 0) + (int) ($stats['updated'] ?? 0);
            $progress['total_skipped'] = (int) ($progress['total_skipped'] ?? 0) + (int) ($stats['skipped'] ?? 0);
            $progress['last_error'] = null;
            $progress['last_run_at'] = now()->toIso8601String();
            $progress['last_batch'] = [
                'year' => $year,
                'skip' => $skip,
                'fetched' => $fetched,
                'imported' => (int) ($stats['imported'] ?? 0),
                'updated' => (int) ($stats['updated'] ?? 0),
                'skipped' => (int) ($stats['skipped'] ?? 0),
                'duplicates' => (int) ($stats['duplicates'] ?? 0),
                'api_ms' => (int) ($stats['api_ms'] ?? 0),
                'db_ms' => (int) ($stats['db_ms'] ?? 0),
                'lease_id' => $leaseId,
                'last_listing_key' => $lastKey,
                'used_keyset' => $usedKeyset,
            ];

            $yearEnded = $fetched < $chunk;

            if ($usedKeyset) {
                $progress['paging_mode'] = 'keyset';
                $progress['skip'] = 0;
                if ($yearEnded || $lastKey === null) {
                    $progress['year_eof'] = is_array($progress['year_eof'] ?? null) ? $progress['year_eof'] : [];
                    $progress['year_eof'][(string) $year] = true;
                    $progress['after_listing_key'] = null;
                    $progress['paging_mode'] = 'skip';
                    $this->cancelNewerSameYearLeases($progress, $year, $skip);
                    $this->advanceYearCursor($progress, $year);
                } else {
                    $progress['after_listing_key'] = $lastKey;
                    $progress['year'] = $year;
                }
            } elseif ($yearEnded) {
                $progress['year_eof'] = is_array($progress['year_eof'] ?? null) ? $progress['year_eof'] : [];
                $progress['year_eof'][(string) $year] = true;
                $progress['after_listing_key'] = null;
                $progress['paging_mode'] = 'skip';
                $this->cancelNewerSameYearLeases($progress, $year, $skip);
                $this->advanceYearCursor($progress, $year);
            } else {
                // Still in skip mode — remember last key so we can switch to keyset at the ceiling.
                if ($lastKey !== null) {
                    $progress['after_listing_key'] = $lastKey;
                }
                $nextSkip = (int) ($progress['skip'] ?? ($skip + $chunk));
                if ($this->service->skipWouldExceedAmpLimit($nextSkip, $chunk) && $lastKey !== null) {
                    $progress['paging_mode'] = 'keyset';
                    $progress['after_listing_key'] = $lastKey;
                    $progress['skip'] = 0;
                    $progress['year'] = $year;
                }
            }

            // Global completed when all years EOF and no leases left.
            if ($this->allYearsComplete($progress) && empty($progress['leases']) && empty($progress['reclaim_queue'])) {
                $progress['completed'] = true;
            }

            $this->service->writeProgress($progress);
        });
    }

    public function fail(string $leaseId): void
    {
        Cache::lock(self::CURSOR_LOCK, 20)->block(15, function () use ($leaseId) {
            $progress = $this->service->readProgress();
            $leases = is_array($progress['leases'] ?? null) ? $progress['leases'] : [];
            $lease = $leases[$leaseId] ?? null;
            if (! is_array($lease)) {
                return;
            }

            unset($progress['leases'][$leaseId]);
            $claimSkip = (int) ($lease['skip'] ?? 0);
            $claimChunk = (int) ($lease['chunk'] ?? 200);
            $claimYear = (int) ($lease['year'] ?? 0);

            // Never reclaim illegal AMP skips — that caused the permanent stall loop.
            if ($this->service->skipWouldExceedAmpLimit($claimSkip, $claimChunk)) {
                $progress['last_error'] = 'lease_failed_skip_ceiling';
                $progress['last_run_at'] = now()->toIso8601String();
                $this->service->purgeIllegalReclaims($progress, $claimYear);
                $this->service->writeProgress($progress);

                return;
            }

            $progress['reclaim_queue'] = is_array($progress['reclaim_queue'] ?? null) ? $progress['reclaim_queue'] : [];
            $progress['reclaim_queue'][] = [
                'year' => $claimYear,
                'skip' => $claimSkip,
                'chunk' => $claimChunk,
            ];
            $progress['last_error'] = 'lease_failed_requeued';
            $progress['last_run_at'] = now()->toIso8601String();
            $this->service->writeProgress($progress);
        });
    }

    /**
     * Drop a lease that hit AMP 1108 without requeueing the illegal skip.
     */
    public function abandonLeaseForSkipCeiling(string $leaseId): void
    {
        Cache::lock(self::CURSOR_LOCK, 20)->block(15, function () use ($leaseId) {
            $progress = $this->service->readProgress();
            $leases = is_array($progress['leases'] ?? null) ? $progress['leases'] : [];
            unset($leases[$leaseId]);
            $progress['leases'] = $leases;
            $progress['last_error'] = 'amp_skip_ceiling_abandoned';
            $progress['last_run_at'] = now()->toIso8601String();
            $this->service->purgeIllegalReclaims($progress);
            $this->service->writeProgress($progress);
        });
    }

    public function isCancelled(string $leaseId): bool
    {
        $progress = $this->service->readProgress();
        $cancelled = is_array($progress['cancelled_leases'] ?? null) ? $progress['cancelled_leases'] : [];

        return ! empty($cancelled[$leaseId]);
    }

    /**
     * @param  array<string, mixed>  $progress
     */
    private function advanceYearCursor(array &$progress, int $year): void
    {
        $toYear = (int) ($progress['to_year'] ?? (int) date('Y'));
        if ((int) ($progress['year'] ?? $year) === $year) {
            if ($year + 1 > $toYear) {
                $progress['completed'] = true;
                $progress['skip'] = 0;
            } else {
                $progress['year'] = $year + 1;
                $progress['skip'] = 0;
                $progress['paging_mode'] = 'skip';
                $progress['after_listing_key'] = null;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $progress
     */
    private function cancelNewerSameYearLeases(array &$progress, int $year, int $skip): void
    {
        foreach ($progress['leases'] as $id => $other) {
            if (! is_array($other)) {
                continue;
            }
            if ((int) ($other['year'] ?? -1) === $year && (int) ($other['skip'] ?? -1) > $skip) {
                $progress['cancelled_leases'][$id] = true;
                unset($progress['leases'][$id]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $progress
     */
    private function purgeExpiredLeases(array &$progress): void
    {
        $leases = is_array($progress['leases'] ?? null) ? $progress['leases'] : [];
        $now = time();
        $reclaim = is_array($progress['reclaim_queue'] ?? null) ? $progress['reclaim_queue'] : [];

        foreach ($leases as $id => $lease) {
            if (! is_array($lease)) {
                unset($leases[$id]);
                continue;
            }
            $expires = (int) ($lease['expires_at'] ?? 0);
            if ($expires > 0 && $expires < $now) {
                $claimSkip = (int) ($lease['skip'] ?? 0);
                $claimChunk = (int) ($lease['chunk'] ?? 200);
                // Expired keyset leases: do not reclaim as skip; cursor stays on after_listing_key.
                if (! empty($lease['after_listing_key'])) {
                    unset($leases[$id]);
                    continue;
                }
                if (! $this->service->skipWouldExceedAmpLimit($claimSkip, $claimChunk)) {
                    $reclaim[] = [
                        'year' => (int) ($lease['year'] ?? 0),
                        'skip' => $claimSkip,
                        'chunk' => $claimChunk,
                    ];
                }
                unset($leases[$id]);
            }
        }

        $progress['leases'] = $leases;
        $progress['reclaim_queue'] = array_values($reclaim);
    }

    /**
     * @param  array<string, mixed>  $progress
     */
    private function allYearsComplete(array $progress): bool
    {
        $from = (int) ($progress['from_year'] ?? 0);
        $to = (int) ($progress['to_year'] ?? 0);
        $eof = is_array($progress['year_eof'] ?? null) ? $progress['year_eof'] : [];
        if ($from <= 0 || $to < $from) {
            return false;
        }
        for ($y = $from; $y <= $to; $y++) {
            if (empty($eof[(string) $y])) {
                return false;
            }
        }

        return true;
    }
}
