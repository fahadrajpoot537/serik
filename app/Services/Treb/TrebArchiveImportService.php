<?php

namespace App\Services\Treb;

use App\Support\PropertySearchSync;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Isolated TREB Archive (AUTH2) sold-data importer — enterprise bulk path.
 *
 * Does NOT use TRREB_AUTH / TRREB_AUTH1.
 * Does NOT call existing TREB/VOW/IDX sync services or PropertyController importers.
 * Does NOT index Meilisearch inline (queues PropertySearchSync instead).
 */
class TrebArchiveImportService
{
    public const PROGRESS_PATH = 'treb-archive/progress.json';

    public const LOCK_KEY = 'serik_treb_archive_import_lock';

    private const DEFAULT_BATCH = 200;

    private const ARCHIVE_YEARS = 14;

    /** AMP hard limit: $skip cannot retrieve past 100,000 records (error 1108). */
    public const AMP_MAX_SKIP = 100000;

    private const SOLD_STATUS_FILTER = "(MlsStatus eq 'Sold' or MlsStatus eq 'Sold Conditional' or MlsStatus eq 'Sold Conditional Escape' or MlsStatus eq 'Leased' or MlsStatus eq 'Leased Conditional')";

    private const EXCLUDED_SUBTYPES = [
        'Industrial',
        'Commercial Retail',
        'Business',
        'Farm',
        'Store W/Apt/Offc',
        'Investment',
    ];

    /** @var list<string> */
    private const UPSERT_UPDATE_COLUMNS = [
        'name',
        'location',
        'PropertySubType',
        'description',
        'price',
        'ClosePrice',
        'zip_code',
        'MlsStatus',
        'TransactionType',
        'broker',
        'number_bedroom',
        'BedroomsBelowGrade',
        'number_bathroom',
        'moderation_status',
        'status',
        'close_date',
        'listing_contract_date',
        'purchase_contract_date',
        'listing_modified_at',
        'updated_at',
    ];

    /**
     * Process one or more AMP pages until pages/time budget is exhausted.
     *
     * @return array<string, mixed>
     */
    public function run(
        ?int $batchSize = null,
        bool $dryRun = false,
        bool $reset = false,
        ?int $maxPages = null,
        ?int $maxSeconds = null,
    ): array {
        $started = microtime(true);
        $log = Log::channel('treb_archive');
        $memStart = memory_get_usage(true);

        $token = $this->archiveToken();
        if ($token === null) {
            $log->warning('Archive import skipped: TREB_AUTH2 / TRREB_AUTH2 is empty.');

            return [
                'ok' => false,
                'skipped' => true,
                'reason' => 'missing_treb_auth2',
            ];
        }

        if ($reset) {
            $this->writeProgress($this->freshProgress());
            Cache::forget((string) config('treb.archive.progress_cache_key', 'serik_treb_archive_progress'));
            Cache::forget(TrebArchiveHealthMonitor::METRICS_KEY);
            Cache::forget(TrebArchiveHealthMonitor::WINDOW_KEY);
            $log->info('Archive progress reset.');
        }

        $progress = $this->readProgress();
        if (! empty($progress['completed'])) {
            $log->info('Archive import already completed; idle.', [
                'from_year' => $progress['from_year'] ?? null,
                'to_year' => $progress['to_year'] ?? null,
            ]);

            return [
                'ok' => true,
                'completed' => true,
                'idle' => true,
                'has_more' => false,
                'progress' => $progress,
            ];
        }

        $batchSize = $this->resolveChunkSize($batchSize);
        $maxPages = max(1, $maxPages ?? (int) config('treb.archive.pages_per_job', 15));
        $maxSeconds = max(5, $maxSeconds ?? (int) config('treb.archive.max_seconds_per_job', 90));
        $useLeases = (bool) config('treb.archive.parallel_enabled', true) && ! $dryRun;
        $allocator = $useLeases ? app(TrebArchivePageAllocator::class) : null;
        $monitor = app(TrebArchiveHealthMonitor::class);

        $totals = [
            'fetched' => 0,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'duplicates' => 0,
            'pages' => 0,
            'api_ms' => 0,
            'db_ms' => 0,
        ];
        $hasMore = false;
        $lastError = null;

        for ($page = 0; $page < $maxPages; $page++) {
            if ((microtime(true) - $started) >= $maxSeconds) {
                $hasMore = true;
                $log->info('Archive job time budget reached; yielding.', [
                    'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
                    'max_seconds' => $maxSeconds,
                    'pages_done' => $totals['pages'],
                ]);
                break;
            }

            $this->respectRateLimit();

            $leaseId = null;
            $year = 0;
            $skip = 0;
            $pageChunk = $batchSize;
            $afterListingKey = null;

            if ($allocator) {
                $claim = $allocator->claim($batchSize);
                if ($claim === null) {
                    $progress = $this->readProgress();
                    // Completed, or a peer holds the keyset/skip lease — peer/scheduler continues.
                    $hasMore = empty($progress['completed']) && empty($progress['leases']);
                    break;
                }
                $leaseId = $claim['lease_id'];
                $year = $claim['year'];
                $skip = $claim['skip'];
                $pageChunk = $claim['chunk'];
                $afterListingKey = $this->normalizeListingKey($claim['after_listing_key'] ?? null);

                // Keyset lease without a cursor must bootstrap — never refetch $skip=0 for that year.
                $snap = $this->readProgress();
                if ($afterListingKey === null && (string) ($snap['paging_mode'] ?? 'skip') === 'keyset') {
                    $boot = $this->bootstrapKeysetCursor($token, $year, $pageChunk, null);
                    if ($boot['eof'] ?? false) {
                        $allocator->abandonLeaseForSkipCeiling($leaseId);
                        $this->markYearEofAndAdvance($year);
                        $hasMore = empty($this->readProgress()['completed']);
                        continue;
                    }
                    $afterListingKey = $this->normalizeListingKey($boot['after_listing_key'] ?? null);
                    if ($afterListingKey === null) {
                        $allocator->abandonLeaseForSkipCeiling($leaseId);
                        $lastError = 'keyset_bootstrap_missing_listing_key';
                        $hasMore = true;
                        break;
                    }
                    $skip = 0;
                }
            } else {
                $progress = $this->readProgress();
                if (! empty($progress['completed'])) {
                    $hasMore = false;
                    break;
                }
                $year = (int) ($progress['year'] ?? $progress['from_year']);
                $afterListingKey = $this->normalizeListingKey($progress['after_listing_key'] ?? null);
                $pagingMode = (string) ($progress['paging_mode'] ?? 'skip');
                if ($pagingMode === 'keyset' && $afterListingKey !== null) {
                    $skip = 0;
                } else {
                    $skip = (int) ($progress['skip'] ?? 0);
                    if ($this->skipWouldExceedAmpLimit($skip, $pageChunk)) {
                        // Switch to keyset before requesting an illegal $skip.
                        $boot = $this->bootstrapKeysetCursor($token, $year, $pageChunk, $afterListingKey);
                        if ($boot['eof'] ?? false) {
                            $this->markYearEofAndAdvance($year);
                            $hasMore = ! empty($this->readProgress()['completed']) ? false : true;
                            continue;
                        }
                        $afterListingKey = $boot['after_listing_key'] ?? null;
                        $skip = 0;
                    }
                }
            }

            $log->info('Archive batch started', [
                'year' => $year,
                'skip' => $skip,
                'batch' => $pageChunk,
                'after_listing_key' => $afterListingKey,
                'paging_mode' => $afterListingKey ? 'keyset' : 'skip',
                'lease_id' => $leaseId,
                'page_in_job' => $page + 1,
                'dry_run' => $dryRun,
                'memory_mb' => round(memory_get_usage(true) / 1048576, 2),
            ]);

            $apiStarted = microtime(true);
            try {
                $payload = $this->fetchArchivePage($token, $year, $skip, $pageChunk, $afterListingKey);
                $this->onApiSuccess((int) round((microtime(true) - $apiStarted) * 1000));
            } catch (Throwable $e) {
                $this->onApiFailure($e);
                $lastError = $e->getMessage();

                if ($this->isAmpSkipLimitError($lastError)) {
                    $log->warning('Archive hit AMP $skip ceiling; switching to ListingKey keyset pagination.', [
                        'year' => $year,
                        'skip' => $skip,
                        'lease_id' => $leaseId,
                        'error' => $lastError,
                    ]);

                    if ($leaseId && $allocator) {
                        $allocator->abandonLeaseForSkipCeiling($leaseId);
                    }

                    $boot = $this->bootstrapKeysetCursor($token, $year, $pageChunk, $afterListingKey);
                    if ($boot['eof'] ?? false) {
                        $this->markYearEofAndAdvance($year);
                    }

                    $monitor->record([
                        'fetched' => $totals['fetched'],
                        'imported' => $totals['imported'],
                        'updated' => $totals['updated'],
                        'skipped' => $totals['skipped'],
                        'api_ms' => $totals['api_ms'],
                        'db_ms' => $totals['db_ms'],
                        'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
                        'pages' => $totals['pages'],
                        'success' => false,
                    ]);

                    // Soft-fail with has_more so the chain/scheduler continues in keyset mode.
                    return [
                        'ok' => true,
                        'recovered_skip_ceiling' => true,
                        'error' => $lastError,
                        'has_more' => empty($this->readProgress()['completed']),
                        'progress' => $this->readProgress(),
                        'fetched' => $totals['fetched'],
                        'imported' => $totals['imported'],
                        'updated' => $totals['updated'],
                        'skipped' => $totals['skipped'],
                        'pages' => $totals['pages'],
                        'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
                        'memory_mb' => round(memory_get_usage(true) / 1048576, 2),
                    ];
                }

                if ($leaseId && $allocator) {
                    $allocator->fail($leaseId);
                } else {
                    $progress = $this->readProgress();
                    $progress['last_error'] = $lastError;
                    $progress['last_run_at'] = now()->toIso8601String();
                    $this->writeProgress($progress);
                }
                $log->error('Archive API failed; progress kept for next run.', [
                    'year' => $year,
                    'skip' => $skip,
                    'lease_id' => $leaseId,
                    'error' => $lastError,
                    'api_ms' => (int) round((microtime(true) - $apiStarted) * 1000),
                ]);

                $monitor->record([
                    'fetched' => $totals['fetched'],
                    'imported' => $totals['imported'],
                    'updated' => $totals['updated'],
                    'skipped' => $totals['skipped'],
                    'api_ms' => $totals['api_ms'],
                    'db_ms' => $totals['db_ms'],
                    'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
                    'pages' => $totals['pages'],
                    'success' => false,
                ]);

                return [
                    'ok' => false,
                    'error' => $lastError,
                    'has_more' => true,
                    'progress' => $this->readProgress(),
                    'fetched' => $totals['fetched'],
                    'imported' => $totals['imported'],
                    'updated' => $totals['updated'],
                    'skipped' => $totals['skipped'],
                    'pages' => $totals['pages'],
                    'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
                    'memory_mb' => round(memory_get_usage(true) / 1048576, 2),
                ];
            }

            $apiMs = (int) round((microtime(true) - $apiStarted) * 1000);
            $totals['api_ms'] += $apiMs;

            $rows = is_array($payload['value'] ?? null) ? $payload['value'] : [];
            $fetched = count($rows);
            $totals['fetched'] += $fetched;
            $totals['pages']++;

            $imported = 0;
            $updated = 0;
            $skipped = 0;
            $duplicates = 0;
            $dbMs = 0;

            if ($fetched > 0 && ! $dryRun) {
                $dbStarted = microtime(true);
                [$imported, $updated, $skipped, $duplicates] = $this->persistRowsBulk($rows);
                $dbMs = (int) round((microtime(true) - $dbStarted) * 1000);
                $totals['db_ms'] += $dbMs;
                $totals['imported'] += $imported;
                $totals['updated'] += $updated;
                $totals['skipped'] += $skipped;
                $totals['duplicates'] += $duplicates;
            } elseif ($fetched > 0 && $dryRun) {
                $imported = $fetched;
            }

            $pageHasMore = $fetched >= $pageChunk;
            $lastListingKey = $this->lastListingKeyFromRows($rows);
            $stats = [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'duplicates' => $duplicates,
                'api_ms' => $apiMs,
                'db_ms' => $dbMs,
                'last_listing_key' => $lastListingKey,
                'used_keyset' => $afterListingKey !== null,
            ];

            if ($dryRun) {
                $log->info('Archive dry-run page finished (progress not advanced)', [
                    'year' => $year,
                    'skip' => $skip,
                    'fetched' => $fetched,
                    'api_ms' => $apiMs,
                ]);
                unset($rows, $payload);
                $hasMore = $pageHasMore;

                break;
            }

            if ($leaseId && $allocator) {
                if ($allocator->isCancelled($leaseId)) {
                    // Data upsert already applied (safe); skip cursor mutation.
                    $allocator->complete($leaseId, $fetched, $stats);
                } else {
                    $allocator->complete($leaseId, $fetched, $stats);
                }
                $progress = $this->readProgress();
                $hasMore = empty($progress['completed']);
            } else {
                $progress = $this->readProgress();
                if ($afterListingKey !== null || ($lastListingKey && $this->skipWouldExceedAmpLimit($skip + $pageChunk, $pageChunk))) {
                    // Keyset cursor (or crossing AMP skip ceiling).
                    $progress['paging_mode'] = 'keyset';
                    $progress['after_listing_key'] = $lastListingKey;
                    $progress['skip'] = 0;
                    if ($pageHasMore && $lastListingKey) {
                        $hasMore = true;
                    } else {
                        $toYear = (int) ($progress['to_year'] ?? (int) date('Y'));
                        $progress['year_eof'] = is_array($progress['year_eof'] ?? null) ? $progress['year_eof'] : [];
                        $progress['year_eof'][(string) $year] = true;
                        $progress['after_listing_key'] = null;
                        $progress['paging_mode'] = 'skip';
                        $nextYear = $year + 1;
                        if ($nextYear > $toYear) {
                            $progress['completed'] = true;
                            $progress['skip'] = 0;
                            $progress['year'] = $year;
                            $hasMore = false;
                        } else {
                            $progress['year'] = $nextYear;
                            $progress['skip'] = 0;
                            $hasMore = true;
                        }
                    }
                } elseif ($pageHasMore) {
                    $nextSkip = $skip + $pageChunk;
                    if ($this->skipWouldExceedAmpLimit($nextSkip, $pageChunk) && $lastListingKey) {
                        $progress['paging_mode'] = 'keyset';
                        $progress['after_listing_key'] = $lastListingKey;
                        $progress['skip'] = 0;
                    } else {
                        $progress['skip'] = $nextSkip;
                        $progress['paging_mode'] = 'skip';
                        $progress['after_listing_key'] = $lastListingKey;
                    }
                    $hasMore = true;
                } else {
                    $toYear = (int) ($progress['to_year'] ?? (int) date('Y'));
                    $nextYear = $year + 1;
                    $progress['paging_mode'] = 'skip';
                    $progress['after_listing_key'] = null;
                    if ($nextYear > $toYear) {
                        $progress['completed'] = true;
                        $progress['skip'] = 0;
                        $progress['year'] = $year;
                        $hasMore = false;
                    } else {
                        $progress['year'] = $nextYear;
                        $progress['skip'] = 0;
                        $hasMore = true;
                    }
                }

                $progress['total_fetched'] = (int) ($progress['total_fetched'] ?? 0) + $fetched;
                $progress['total_imported'] = (int) ($progress['total_imported'] ?? 0) + $imported;
                $progress['total_updated'] = (int) ($progress['total_updated'] ?? 0) + $updated;
                $progress['total_skipped'] = (int) ($progress['total_skipped'] ?? 0) + $skipped;
                $progress['last_error'] = null;
                $progress['last_run_at'] = now()->toIso8601String();
                $progress['last_batch'] = [
                    'year' => $year,
                    'skip' => $skip,
                    'fetched' => $fetched,
                    'imported' => $imported,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'duplicates' => $duplicates,
                    'api_ms' => $apiMs,
                    'db_ms' => $dbMs,
                    'last_listing_key' => $lastListingKey,
                    'next_year' => $progress['year'] ?? $year,
                    'next_skip' => $progress['skip'] ?? 0,
                    'has_more' => $hasMore,
                ];
                $this->writeProgress($progress);
            }

            $progressAfter = $this->readProgress();
            $log->info('Archive batch finished', [
                'year' => $year,
                'fetched' => $fetched,
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
                'duplicates' => $duplicates,
                'api_ms' => $apiMs,
                'db_ms' => $dbMs,
                'lease_id' => $leaseId,
                'next_year' => $progressAfter['year'] ?? null,
                'next_skip' => $progressAfter['skip'] ?? null,
                'completed' => (bool) ($progressAfter['completed'] ?? false),
                'memory_mb' => round(memory_get_usage(true) / 1048576, 2),
            ]);

            unset($rows, $payload);

            if (! $hasMore || ! empty($progressAfter['completed'])) {
                break;
            }
        }

        $elapsedMs = (int) round((microtime(true) - $started) * 1000);
        $progress = $this->readProgress();

        $monitor->record([
            'fetched' => $totals['fetched'],
            'imported' => $totals['imported'],
            'updated' => $totals['updated'],
            'skipped' => $totals['skipped'],
            'api_ms' => $totals['api_ms'],
            'db_ms' => $totals['db_ms'],
            'elapsed_ms' => $elapsedMs,
            'pages' => $totals['pages'],
            'success' => $lastError === null,
        ]);

        $log->info('Archive run summary', [
            'pages' => $totals['pages'],
            'fetched' => $totals['fetched'],
            'imported' => $totals['imported'],
            'updated' => $totals['updated'],
            'skipped' => $totals['skipped'],
            'duplicates' => $totals['duplicates'],
            'api_ms' => $totals['api_ms'],
            'db_ms' => $totals['db_ms'],
            'elapsed_ms' => $elapsedMs,
            'has_more' => $hasMore && empty($progress['completed']),
            'rows_per_sec' => $elapsedMs > 0 ? round($totals['fetched'] / ($elapsedMs / 1000), 2) : 0,
            'memory_delta_mb' => round((memory_get_usage(true) - $memStart) / 1048576, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
        ]);

        return [
            'ok' => $lastError === null,
            'error' => $lastError,
            'fetched' => $totals['fetched'],
            'imported' => $totals['imported'],
            'updated' => $totals['updated'],
            'skipped' => $totals['skipped'],
            'duplicates' => $totals['duplicates'],
            'pages' => $totals['pages'],
            'api_ms' => $totals['api_ms'],
            'db_ms' => $totals['db_ms'],
            'has_more' => $hasMore && empty($progress['completed']),
            'completed' => (bool) ($progress['completed'] ?? false),
            'progress' => $progress,
            'elapsed_ms' => $elapsedMs,
            'memory_mb' => round(memory_get_usage(true) / 1048576, 2),
            'dry_run' => $dryRun,
            'health' => $monitor->snapshot($progress),
        ];
    }

    private function archiveToken(): ?string
    {
        $token = config('treb.auth2');
        if (! is_string($token)) {
            return null;
        }

        $token = trim($token, " \t\n\r\0\x0B\"'");

        return $token !== '' ? $token : null;
    }

    private function archiveEndpoint(): string
    {
        $url = (string) config('treb.archive_odata_url', 'https://query.ampre.ca/odata/Property');

        return rtrim($url, '?&');
    }

    private function resolveChunkSize(?int $batchSize): int
    {
        if ($batchSize !== null) {
            return $this->normalizeChunkSize($batchSize);
        }

        $base = (int) config('treb.archive.chunk_size', self::DEFAULT_BATCH);
        if (! config('treb.archive.adaptive_chunk', true)) {
            return $this->normalizeChunkSize($base);
        }

        $lastApiMs = (int) Cache::get('serik_treb_archive_last_api_ms', 1000);
        $backoffKey = (string) config('treb.archive.backoff_key', 'serik_treb_archive_rate_backoff_ms');
        $backoff = (int) Cache::get($backoffKey, (int) config('treb.archive.min_sleep_ms', 50));
        $minSleep = (int) config('treb.archive.min_sleep_ms', 50);

        if ($backoff > max(1000, $minSleep * 4) || $lastApiMs > 5000) {
            $base = (int) floor($base * 0.5);
        } elseif ($lastApiMs > 0 && $lastApiMs < 800 && $backoff <= $minSleep) {
            $base = (int) ceil($base * 1.25);
        }

        return $this->normalizeChunkSize($base);
    }

    private function normalizeChunkSize(?int $batchSize): int
    {
        $configured = $batchSize ?? (int) config('treb.archive.chunk_size', self::DEFAULT_BATCH);

        return max(10, min(500, $configured));
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchArchivePage(
        string $token,
        int $year,
        int $skip,
        int $top,
        ?string $afterListingKey = null
    ): array {
        $start = sprintf('%04d-01-01', $year);
        $next = sprintf('%04d-01-01', $year + 1);

        $residential = [];
        foreach (self::EXCLUDED_SUBTYPES as $subtype) {
            $escaped = str_replace("'", "''", $subtype);
            $residential[] = "PropertySubType ne '{$escaped}'";
        }
        $residentialFilter = $residential !== [] ? ' and ' . implode(' and ', $residential) : '';

        // AMP Property $filter cannot use CloseDate / PurchaseContractDate.
        $filter = "ModificationTimestamp ge {$start}T00:00:00Z"
            . " and ModificationTimestamp lt {$next}T00:00:00Z"
            . ' and ' . self::SOLD_STATUS_FILTER
            . $residentialFilter;

        $afterListingKey = $this->normalizeListingKey($afterListingKey);
        $useKeyset = $afterListingKey !== null;
        if ($useKeyset) {
            // Keyset pagination past AMP's $skip=100000 ceiling (error 1108).
            $escapedKey = str_replace("'", "''", $afterListingKey);
            $filter .= " and ListingKey gt '{$escapedKey}'";
            $skip = 0;
        } elseif ($this->skipWouldExceedAmpLimit($skip, $top)) {
            throw new \RuntimeException(
                'Archive HTTP 400: {"error":{"code":"1108","message":"Invalid values for $skip and $top: total count would exceed AMP $skip limit of '
                . self::AMP_MAX_SKIP . '"}}'
            );
        }

        $select = 'ListingKey,UnparsedAddress,PropertySubType,PublicRemarks,PrivateRemarks,'
            . 'BedroomsTotal,BedroomsAboveGrade,BedroomsBelowGrade,BathroomsTotalInteger,LivingAreaRange,'
            . 'ListPrice,ClosePrice,PostalCode,OriginalEntryTimestamp,ModificationTimestamp,'
            . 'TransactionType,MlsStatus,ListOfficeName,ListingContractDate,CloseDate,PurchaseContractDate';

        $url = $this->archiveEndpoint()
            . '?$filter=' . rawurlencode($filter)
            . '&$orderby=' . rawurlencode('ListingKey asc')
            . '&$select=' . rawurlencode($select)
            . '&$top=' . $top
            . '&$skip=' . max(0, $skip);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])
            ->timeout((int) config('treb.archive.http_timeout', 45))
            ->connectTimeout((int) config('treb.archive.http_connect_timeout', 10))
            ->withOptions([
                'verify' => false,
            ])
            ->get($url);

        if ($response->status() === 429) {
            throw new \RuntimeException('Archive HTTP 429: rate limited by TREB/AMP');
        }

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Archive HTTP ' . $response->status() . ': ' . Str::limit((string) $response->body(), 400)
            );
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new \RuntimeException('Archive response was not JSON.');
        }

        if (! empty($json['error']['message'])) {
            throw new \RuntimeException((string) $json['error']['message']);
        }

        return $json;
    }

    public function skipWouldExceedAmpLimit(int $skip, int $top): bool
    {
        $skip = max(0, $skip);
        $top = max(1, $top);

        // AMP: $skip is limited to 100,000; $top+$skip must stay within that window.
        return $skip >= self::AMP_MAX_SKIP || ($skip + $top) > self::AMP_MAX_SKIP;
    }

    public function isAmpSkipLimitError(string $message): bool
    {
        $m = strtolower($message);

        return str_contains($m, '1108')
            || (str_contains($m, '$skip') && str_contains($m, 'invalid'))
            || str_contains($m, 'amp $skip limit');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function lastListingKeyFromRows(array $rows): ?string
    {
        for ($i = count($rows) - 1; $i >= 0; $i--) {
            $key = $this->normalizeListingKey($rows[$i]['ListingKey'] ?? null);
            if ($key !== null) {
                return $key;
            }
        }

        return null;
    }

    public function normalizeListingKey(mixed $key): ?string
    {
        if (! is_string($key) && ! is_numeric($key)) {
            return null;
        }
        $key = strtoupper(trim((string) $key));

        return $key !== '' ? $key : null;
    }

    /**
     * Bootstrap keyset cursor near the AMP $skip ceiling so import can continue.
     *
     * @return array{after_listing_key:?string,eof:bool}
     */
    public function bootstrapKeysetCursor(
        string $token,
        int $year,
        int $chunk,
        ?string $knownAfterKey = null
    ): array {
        $knownAfterKey = $this->normalizeListingKey($knownAfterKey);
        if ($knownAfterKey !== null) {
            $progress = $this->readProgress();
            $progress['paging_mode'] = 'keyset';
            $progress['after_listing_key'] = $knownAfterKey;
            $progress['skip'] = 0;
            $progress['year'] = $year;
            $progress['last_error'] = null;
            $progress['last_run_at'] = now()->toIso8601String();
            $this->purgeIllegalReclaims($progress, $year);
            $this->writeProgress($progress);

            return ['after_listing_key' => $knownAfterKey, 'eof' => false];
        }

        $chunk = $this->normalizeChunkSize($chunk);
        // Highest legal $skip for this chunk.
        $boundarySkip = max(0, self::AMP_MAX_SKIP - $chunk);

        try {
            $payload = $this->fetchArchivePage($token, $year, $boundarySkip, $chunk, null);
            $rows = is_array($payload['value'] ?? null) ? $payload['value'] : [];
            $lastKey = $this->lastListingKeyFromRows($rows);

            $progress = $this->readProgress();
            $progress['year'] = $year;
            $progress['skip'] = 0;
            $progress['last_run_at'] = now()->toIso8601String();
            $this->purgeIllegalReclaims($progress, $year);

            if ($lastKey === null || $rows === []) {
                // Nothing left at the boundary — treat year as complete.
                $progress['paging_mode'] = 'skip';
                $progress['after_listing_key'] = null;
                $this->writeProgress($progress);

                return ['after_listing_key' => null, 'eof' => true];
            }

            $progress['paging_mode'] = 'keyset';
            $progress['after_listing_key'] = $lastKey;
            $progress['last_error'] = null;
            $progress['last_batch'] = [
                'year' => $year,
                'skip' => $boundarySkip,
                'fetched' => count($rows),
                'bootstrap_keyset' => true,
                'last_listing_key' => $lastKey,
            ];
            $this->writeProgress($progress);

            // Persist boundary page rows so we don't lose them when leaping to keyset.
            if ($rows !== []) {
                [$imported, $updated, $skipped] = $this->persistRowsBulk($rows);
                $progress = $this->readProgress();
                $progress['total_fetched'] = (int) ($progress['total_fetched'] ?? 0) + count($rows);
                $progress['total_imported'] = (int) ($progress['total_imported'] ?? 0) + $imported;
                $progress['total_updated'] = (int) ($progress['total_updated'] ?? 0) + $updated;
                $progress['total_skipped'] = (int) ($progress['total_skipped'] ?? 0) + $skipped;
                $this->writeProgress($progress);
            }

            return ['after_listing_key' => $lastKey, 'eof' => false];
        } catch (Throwable $e) {
            Log::channel('treb_archive')->error('Archive keyset bootstrap failed', [
                'year' => $year,
                'boundary_skip' => $boundarySkip,
                'error' => $e->getMessage(),
            ]);

            // Last resort: advance year so the importer never stays wedged.
            return ['after_listing_key' => null, 'eof' => true];
        }
    }

    /**
     * @param  array<string, mixed>  $progress
     */
    public function purgeIllegalReclaims(array &$progress, ?int $year = null): void
    {
        $reclaim = is_array($progress['reclaim_queue'] ?? null) ? $progress['reclaim_queue'] : [];
        $kept = [];
        foreach ($reclaim as $item) {
            if (! is_array($item)) {
                continue;
            }
            $itemYear = (int) ($item['year'] ?? 0);
            $itemSkip = (int) ($item['skip'] ?? 0);
            $itemChunk = max(10, (int) ($item['chunk'] ?? 200));
            if ($year !== null && $itemYear === $year && $this->skipWouldExceedAmpLimit($itemSkip, $itemChunk)) {
                continue;
            }
            if ($this->skipWouldExceedAmpLimit($itemSkip, $itemChunk)) {
                continue;
            }
            $kept[] = $item;
        }
        $progress['reclaim_queue'] = array_values($kept);
    }

    public function markYearEofAndAdvance(int $year): void
    {
        $progress = $this->readProgress();
        $toYear = (int) ($progress['to_year'] ?? (int) date('Y'));
        $progress['year_eof'] = is_array($progress['year_eof'] ?? null) ? $progress['year_eof'] : [];
        $progress['year_eof'][(string) $year] = true;
        $progress['paging_mode'] = 'skip';
        $progress['after_listing_key'] = null;
        $progress['skip'] = 0;
        $progress['last_error'] = null;
        $progress['last_run_at'] = now()->toIso8601String();
        $this->purgeIllegalReclaims($progress, $year);

        if ($year + 1 > $toYear) {
            $progress['completed'] = true;
            $progress['year'] = $year;
        } else {
            $progress['year'] = $year + 1;
            $progress['completed'] = false;
        }

        // Drop active leases for the EOF year past the ceiling.
        $leases = is_array($progress['leases'] ?? null) ? $progress['leases'] : [];
        foreach ($leases as $id => $lease) {
            if (! is_array($lease)) {
                unset($leases[$id]);
                continue;
            }
            if ((int) ($lease['year'] ?? -1) === $year) {
                unset($leases[$id]);
            }
        }
        $progress['leases'] = $leases;
        $this->writeProgress($progress);

        Log::channel('treb_archive')->info('Archive year advanced past AMP skip ceiling / EOF', [
            'eof_year' => $year,
            'next_year' => $progress['year'] ?? null,
            'completed' => $progress['completed'] ?? false,
        ]);
    }

    /**
     * Bulk upsert — no Eloquent create/save in loops.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{0:int,1:int,2:int,3:int} imported, updated, skipped, duplicates
     */
    private function persistRowsBulk(array $rows): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $duplicates = 0;
        $now = now()->toDateTimeString();
        $payloadByKey = [];

        foreach ($rows as $item) {
            $listingKey = strtoupper((string) ($item['ListingKey'] ?? ''));
            if ($listingKey === '') {
                $skipped++;

                continue;
            }

            $subtype = rtrim((string) ($item['PropertySubType'] ?? ''));
            if ($subtype !== '' && in_array($subtype, self::EXCLUDED_SUBTYPES, true)) {
                $skipped++;

                continue;
            }

            if (isset($payloadByKey[$listingKey])) {
                $duplicates++;
            }

            $listPrice = (float) ($item['ListPrice'] ?? 0);
            $closePrice = (float) ($item['ClosePrice'] ?? 0);
            $closeDate = $this->parseDate($item['CloseDate'] ?? null);
            $purchaseDate = $this->parseDate($item['PurchaseContractDate'] ?? null);
            $modified = $this->parseDate($item['ModificationTimestamp'] ?? null);
            $contract = $this->parseDate($item['ListingContractDate'] ?? $item['OriginalEntryTimestamp'] ?? null);
            $touchAt = ($purchaseDate ?? $closeDate ?? $modified)?->toDateTimeString() ?? $now;

            $payloadByKey[$listingKey] = [
                'external_id' => $listingKey,
                'unique_id' => 'AR' . strtoupper(substr(md5($listingKey), 0, 10)),
                'author_id' => 1,
                'author_type' => 'Botble\\ACL\\Models\\User',
                'latitude' => 0,
                'longitude' => 0,
                'name' => $item['UnparsedAddress'] ?? null,
                'location' => $item['UnparsedAddress'] ?? null,
                'PropertySubType' => $item['PropertySubType'] ?? 'sell',
                'description' => $item['PublicRemarks'] ?? '',
                'price' => $listPrice > 0 ? $listPrice : 0,
                'ClosePrice' => $closePrice > 0 ? $closePrice : 0,
                'zip_code' => $item['PostalCode'] ?? null,
                'MlsStatus' => $item['MlsStatus'] ?? null,
                'TransactionType' => $item['TransactionType'] ?? '',
                'broker' => $item['ListOfficeName'] ?? null,
                'number_bedroom' => (int) ($item['BedroomsAboveGrade'] ?? $item['BedroomsTotal'] ?? 0),
                'BedroomsBelowGrade' => (int) ($item['BedroomsBelowGrade'] ?? 0),
                'number_bathroom' => (int) ($item['BathroomsTotalInteger'] ?? 0),
                'moderation_status' => ModerationStatusEnum::APPROVED,
                'status' => 'published',
                'close_date' => $closeDate?->toDateString(),
                'listing_contract_date' => $contract?->toDateString(),
                'purchase_contract_date' => $purchaseDate?->toDateString(),
                'listing_modified_at' => $modified?->toDateTimeString(),
                'created_at' => $now,
                'updated_at' => $touchAt,
            ];
        }

        if ($payloadByKey === []) {
            return [0, 0, $skipped, $duplicates];
        }

        $keys = array_keys($payloadByKey);

        $existingKeys = DB::table('re_properties')
            ->whereIn('external_id', $keys)
            ->pluck('external_id')
            ->map(static fn ($k): string => strtoupper((string) $k))
            ->all();
        $existingSet = array_fill_keys($existingKeys, true);

        foreach ($keys as $key) {
            if (isset($existingSet[$key])) {
                $updated++;
            } else {
                $imported++;
            }
        }

        $prevHistory = null;
        if (class_exists(\Botble\RealEstate\Supports\PropertyHistoryRecorder::class)) {
            $prevHistory = \Botble\RealEstate\Supports\PropertyHistoryRecorder::$enabled;
            \Botble\RealEstate\Supports\PropertyHistoryRecorder::$enabled = false;
        }

        try {
            DB::transaction(function () use ($payloadByKey): void {
                $upsertChunk = max(50, min(500, (int) config('treb.archive.upsert_chunk', 250)));
                foreach (array_chunk(array_values($payloadByKey), $upsertChunk) as $chunk) {
                    DB::table('re_properties')->upsert(
                        $chunk,
                        ['external_id'],
                        self::UPSERT_UPDATE_COLUMNS
                    );
                }
            });
        } catch (Throwable $e) {
            Log::channel('treb_archive')->error('Archive bulk upsert failed', [
                'error' => $e->getMessage(),
                'rows' => count($payloadByKey),
            ]);

            throw $e;
        } finally {
            if ($prevHistory !== null) {
                \Botble\RealEstate\Supports\PropertyHistoryRecorder::$enabled = $prevHistory;
            }
        }

        if (config('treb.archive.queue_search_index', true)) {
            $this->queueSearchForKeys($keys);
        }

        return [$imported, $updated, $skipped, $duplicates];
    }

    /**
     * @param  list<string>  $keys
     */
    private function queueSearchForKeys(array $keys): void
    {
        if ($keys === []) {
            return;
        }

        try {
            $ids = DB::table('re_properties')
                ->whereIn('external_id', $keys)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->values()
                ->all();

            if ($ids === []) {
                return;
            }

            app(PropertySearchSync::class)->scheduleMany($ids);
        } catch (Throwable $e) {
            Log::channel('treb_archive')->warning('Archive search queue schedule failed', [
                'error' => $e->getMessage(),
                'keys' => count($keys),
            ]);
        }
    }

    private function respectRateLimit(): void
    {
        $key = (string) config('treb.archive.backoff_key', 'serik_treb_archive_rate_backoff_ms');
        $sleepMs = (int) Cache::get($key, (int) config('treb.archive.min_sleep_ms', 50));
        $sleepMs = max(
            (int) config('treb.archive.min_sleep_ms', 50),
            min((int) config('treb.archive.max_sleep_ms', 5000), $sleepMs)
        );

        if ($sleepMs > 0) {
            usleep($sleepMs * 1000);
        }
    }

    private function onApiSuccess(int $apiMs = 0): void
    {
        $key = (string) config('treb.archive.backoff_key', 'serik_treb_archive_rate_backoff_ms');
        $min = (int) config('treb.archive.min_sleep_ms', 50);
        $current = (int) Cache::get($key, $min);
        $next = max($min, (int) floor($current * 0.7));
        Cache::put($key, $next, (int) config('treb.archive.backoff_ttl_seconds', 600));
        if ($apiMs > 0) {
            Cache::put('serik_treb_archive_last_api_ms', $apiMs, 3600);
        }
    }

    private function onApiFailure(Throwable $e): void
    {
        $key = (string) config('treb.archive.backoff_key', 'serik_treb_archive_rate_backoff_ms');
        $min = (int) config('treb.archive.min_sleep_ms', 50);
        $max = (int) config('treb.archive.max_sleep_ms', 5000);
        $current = (int) Cache::get($key, $min);
        $isRateLimit = str_contains(strtolower($e->getMessage()), '429')
            || str_contains(strtolower($e->getMessage()), 'rate limit');
        $factor = $isRateLimit ? 3 : 2;
        $next = min($max, max($min * 2, $current * $factor));
        Cache::put($key, $next, (int) config('treb.archive.backoff_ttl_seconds', 600));
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function readProgress(): array
    {
        $cacheKey = (string) config('treb.archive.progress_cache_key', 'serik_treb_archive_progress');
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['year'])) {
            return array_merge($this->freshProgress(), $cached);
        }

        $path = storage_path('app/' . self::PROGRESS_PATH);
        $decoded = \App\Support\SerikAtomicFile::readJson($path);
        if (is_array($decoded)) {
            return array_merge($this->freshProgress(), $decoded);
        }

        if (! is_readable($path)) {
            $fresh = $this->freshProgress();
            $this->writeProgress($fresh);

            return $fresh;
        }

        // Unreadable/corrupt primary — try bak already handled by SerikAtomicFile.
        $fresh = $this->freshProgress();
        Log::channel('treb_archive')->warning('Archive progress corrupt or unreadable; starting from fresh template keys with merge');

        return $fresh;
    }

    /**
     * @param  array<string, mixed>  $progress
     */
    public function writeProgress(array $progress): void
    {
        $dir = storage_path('app/treb-archive');
        File::ensureDirectoryExists($dir);
        $path = storage_path('app/' . self::PROGRESS_PATH);

        $ok = \App\Support\SerikAtomicFile::writeJson($path, $progress);
        if (! $ok) {
            // Last-resort non-atomic write (keeps importer moving if rename fails).
            file_put_contents(
                $path,
                json_encode($progress, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            Log::channel('treb_archive')->warning('Archive progress atomic write failed; used fallback');
        }

        $cacheKey = (string) config('treb.archive.progress_cache_key', 'serik_treb_archive_progress');
        Cache::put($cacheKey, $progress, 86400 * 30);

        \App\Support\SerikAuditLog::event(
            \App\Support\SerikAuditLog::DOMAIN_IMPORTS,
            'checkpoint',
            [
                'year' => $progress['year'] ?? null,
                'skip' => $progress['skip'] ?? null,
                'completed' => $progress['completed'] ?? false,
                'total_imported' => $progress['total_imported'] ?? null,
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function freshProgress(): array
    {
        $years = max(1, (int) config('treb.archive.years', self::ARCHIVE_YEARS));
        $toYear = (int) date('Y');
        $fromYear = $toYear - ($years - 1);

        return [
            'from_year' => $fromYear,
            'to_year' => $toYear,
            'year' => $fromYear,
            'skip' => 0,
            'paging_mode' => 'skip',
            'after_listing_key' => null,
            'completed' => false,
            'total_fetched' => 0,
            'total_imported' => 0,
            'total_updated' => 0,
            'total_skipped' => 0,
            'last_run_at' => null,
            'last_error' => null,
            'last_batch' => null,
            'leases' => [],
            'reclaim_queue' => [],
            'cancelled_leases' => [],
            'year_eof' => [],
        ];
    }
}
