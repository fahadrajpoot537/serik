<?php

namespace App\Services\Treb;

use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Models\Property;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Isolated TREB Archive (AUTH2) sold-data importer.
 *
 * Does NOT use TRREB_AUTH / TRREB_AUTH1.
 * Does NOT call existing TREB/VOW/IDX sync services or PropertyController importers.
 */
class TrebArchiveImportService
{
    public const PROGRESS_PATH = 'treb-archive/progress.json';

    public const LOCK_KEY = 'serik_treb_archive_import_lock';

    private const DEFAULT_BATCH = 40;

    private const ARCHIVE_YEARS = 14;

    private const SOLD_STATUS_FILTER = "(MlsStatus eq 'Sold' or MlsStatus eq 'Sold Conditional' or MlsStatus eq 'Sold Conditional Escape' or MlsStatus eq 'Leased' or MlsStatus eq 'Leased Conditional')";

    private const EXCLUDED_SUBTYPES = [
        'Industrial',
        'Commercial Retail',
        'Business',
        'Farm',
        'Store W/Apt/Offc',
        'Investment',
    ];

    /**
     * @return array<string, mixed>
     */
    public function run(int $batchSize = self::DEFAULT_BATCH, bool $dryRun = false, bool $reset = false): array
    {
        $started = microtime(true);
        $log = Log::channel('treb_archive');

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
                'progress' => $progress,
            ];
        }

        $batchSize = max(10, min(100, $batchSize));
        $year = (int) ($progress['year'] ?? $progress['from_year']);
        $skip = (int) ($progress['skip'] ?? 0);
        $toYear = (int) ($progress['to_year'] ?? (int) date('Y'));

        $log->info('Archive batch started', [
            'year' => $year,
            'skip' => $skip,
            'batch' => $batchSize,
            'dry_run' => $dryRun,
        ]);

        try {
            $payload = $this->fetchArchivePage($token, $year, $skip, $batchSize);
        } catch (Throwable $e) {
            $progress['last_error'] = $e->getMessage();
            $progress['last_run_at'] = now()->toIso8601String();
            $this->writeProgress($progress);
            $log->error('Archive API failed; progress kept for next run.', [
                'year' => $year,
                'skip' => $skip,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'progress' => $progress,
                'elapsed_ms' => (int) round((microtime(true) - $started) * 1000),
            ];
        }

        $rows = is_array($payload['value'] ?? null) ? $payload['value'] : [];
        $fetched = count($rows);

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        if ($fetched > 0 && ! $dryRun) {
            [$imported, $updated, $skipped] = $this->persistRows($rows);
        } elseif ($fetched > 0 && $dryRun) {
            $imported = $fetched;
        }

        $hasMore = $fetched >= $batchSize;
        $elapsedMs = (int) round((microtime(true) - $started) * 1000);

        if ($dryRun) {
            $log->info('Archive dry-run batch finished (progress not advanced)', [
                'year' => $year,
                'skip' => $skip,
                'fetched' => $fetched,
                'elapsed_ms' => $elapsedMs,
            ]);

            unset($rows, $payload);

            return [
                'ok' => true,
                'dry_run' => true,
                'fetched' => $fetched,
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'progress' => $progress,
                'elapsed_ms' => $elapsedMs,
            ];
        }

        if ($hasMore) {
            $progress['skip'] = $skip + $batchSize;
        } else {
            // Advance calendar year window.
            $nextYear = $year + 1;
            if ($nextYear > $toYear) {
                $progress['completed'] = true;
                $progress['skip'] = 0;
                $progress['year'] = $year;
            } else {
                $progress['year'] = $nextYear;
                $progress['skip'] = 0;
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
            'next_year' => $progress['year'] ?? $year,
            'next_skip' => $progress['skip'] ?? 0,
            'has_more' => $hasMore,
        ];

        $this->writeProgress($progress);

        $log->info('Archive batch finished', [
            'year' => $year,
            'fetched' => $fetched,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'next_year' => $progress['year'] ?? null,
            'next_skip' => $progress['skip'] ?? null,
            'completed' => (bool) ($progress['completed'] ?? false),
            'elapsed_ms' => $elapsedMs,
        ]);

        // Free batch memory promptly.
        unset($rows, $payload);

        return [
            'ok' => true,
            'fetched' => $fetched,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'progress' => $progress,
            'elapsed_ms' => $elapsedMs,
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

    /**
     * @return array<string, mixed>
     */
    private function fetchArchivePage(string $token, int $year, int $skip, int $top): array
    {
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
            ->timeout(45)
            ->connectTimeout(10)
            ->withOptions([
                'verify' => false,
            ])
            ->get($url);

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

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{0:int,1:int,2:int} imported, updated, skipped
     */
    private function persistRows(array $rows): array
    {
        $imported = 0;
        $updated = 0;
        $skipped = 0;

        $keys = [];
        foreach ($rows as $item) {
            $key = strtoupper((string) ($item['ListingKey'] ?? ''));
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        $existingByKey = $keys === []
            ? collect()
            : Property::query()
                ->whereIn('external_id', array_values(array_unique($keys)))
                ->get()
                ->keyBy(fn (Property $p): string => strtoupper((string) $p->external_id));

        $prevHistory = null;
        if (class_exists(\Botble\RealEstate\Supports\PropertyHistoryRecorder::class)) {
            $prevHistory = \Botble\RealEstate\Supports\PropertyHistoryRecorder::$enabled;
            \Botble\RealEstate\Supports\PropertyHistoryRecorder::$enabled = false;
        }

        try {
            Property::withoutSyncingToSearch(function () use ($rows, $existingByKey, &$imported, &$updated, &$skipped): void {
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

                    try {
                        $existing = $existingByKey->get($listingKey);
                        $isNew = $existing === null;
                        $property = $existing ?? new Property(['external_id' => $listingKey]);

                        if ($isNew) {
                            $property->unique_id = 'AR' . strtoupper(substr(md5($listingKey), 0, 10));
                            $property->author_id = 1;
                            $property->author_type = 'Botble\ACL\Models\User';
                            $property->latitude = 0;
                            $property->longitude = 0;
                        }

                        $listPrice = (float) ($item['ListPrice'] ?? 0);
                        $closePrice = (float) ($item['ClosePrice'] ?? 0);
                        $closeDate = $this->parseDate($item['CloseDate'] ?? null);
                        $purchaseDate = $this->parseDate($item['PurchaseContractDate'] ?? null);
                        $modified = $this->parseDate($item['ModificationTimestamp'] ?? null);
                        $contract = $this->parseDate($item['ListingContractDate'] ?? $item['OriginalEntryTimestamp'] ?? null);

                        $property->fill([
                            'name' => $item['UnparsedAddress'] ?? $property->name,
                            'location' => $item['UnparsedAddress'] ?? $property->location,
                            'PropertySubType' => $item['PropertySubType'] ?? $property->PropertySubType ?? 'sell',
                            'description' => $item['PublicRemarks'] ?? $property->description ?? '',
                            'price' => $listPrice > 0 ? $listPrice : ($property->price ?? 0),
                            'ClosePrice' => $closePrice > 0 ? $closePrice : ($property->ClosePrice ?? 0),
                            'zip_code' => $item['PostalCode'] ?? $property->zip_code,
                            'MlsStatus' => $item['MlsStatus'] ?? $property->MlsStatus,
                            'TransactionType' => $item['TransactionType'] ?? $property->TransactionType ?? '',
                            'broker' => $item['ListOfficeName'] ?? $property->broker,
                            'number_bedroom' => (int) ($item['BedroomsAboveGrade'] ?? $item['BedroomsTotal'] ?? $property->number_bedroom ?? 0),
                            'BedroomsBelowGrade' => (int) ($item['BedroomsBelowGrade'] ?? $property->BedroomsBelowGrade ?? 0),
                            'number_bathroom' => (int) ($item['BathroomsTotalInteger'] ?? $property->number_bathroom ?? 0),
                            'moderation_status' => ModerationStatusEnum::APPROVED,
                            'status' => $property->status ?? 'published',
                            'close_date' => $closeDate?->toDateString() ?? $property->close_date,
                            'listing_contract_date' => $contract?->toDateString() ?? $property->listing_contract_date,
                        ]);

                        if ($purchaseDate || $closeDate || $modified) {
                            $property->updated_at = ($purchaseDate ?? $closeDate ?? $modified);
                        }

                        $property->save();

                        if ($isNew) {
                            $imported++;
                        } else {
                            $updated++;
                        }
                    } catch (Throwable $e) {
                        $skipped++;
                        Log::channel('treb_archive')->warning('Archive row persist failed', [
                            'listing' => $listingKey,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
        } finally {
            if ($prevHistory !== null) {
                \Botble\RealEstate\Supports\PropertyHistoryRecorder::$enabled = $prevHistory;
            }
        }

        return [$imported, $updated, $skipped];
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
        $path = storage_path('app/' . self::PROGRESS_PATH);
        if (! is_readable($path)) {
            $fresh = $this->freshProgress();
            $this->writeProgress($fresh);

            return $fresh;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? array_merge($this->freshProgress(), $decoded) : $this->freshProgress();
    }

    /**
     * @param  array<string, mixed>  $progress
     */
    public function writeProgress(array $progress): void
    {
        $dir = storage_path('app/treb-archive');
        File::ensureDirectoryExists($dir);
        file_put_contents(
            storage_path('app/' . self::PROGRESS_PATH),
            json_encode($progress, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function freshProgress(): array
    {
        $toYear = (int) date('Y');
        $fromYear = $toYear - (self::ARCHIVE_YEARS - 1);

        return [
            'from_year' => $fromYear,
            'to_year' => $toYear,
            'year' => $fromYear,
            'skip' => 0,
            'completed' => false,
            'total_fetched' => 0,
            'total_imported' => 0,
            'total_updated' => 0,
            'total_skipped' => 0,
            'last_run_at' => null,
            'last_error' => null,
            'last_batch' => null,
        ];
    }
}
