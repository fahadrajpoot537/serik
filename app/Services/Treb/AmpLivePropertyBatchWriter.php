<?php

namespace App\Services\Treb;

use App\Support\HomepageFeaturedCache;
use App\Support\HomepageFragmentCache;
use App\Support\PropertySearchSync;
use App\Support\RealEstateCountCache;
use App\Support\ShortcodeRenderCache;
use Botble\RealEstate\Models\Property;
use Botble\Slug\Facades\SlugHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Theme\homzen\Supports\TrebPropertyHelper;
use Throwable;

/**
 * Batched AMP live-listing writer for SyncLive / importRecentModifiedAmpListings.
 *
 * Mirrors PropertyController::saveAmpPropertyItem field mapping, then uses
 * DB::table('re_properties')->upsert() like TrebArchiveImportService, and
 * re-applies Eloquent side effects (slugs, search sync, homepage cache bump,
 * local property-history snapshots) in batch form.
 */
final class AmpLivePropertyBatchWriter
{
    /** @var list<string> */
    public const UPDATE_COLUMNS = [
        'name',
        'PropertySubType',
        'description',
        'content',
        'location',
        'number_bedroom',
        'number_bathroom',
        'number_floor',
        'BedroomsBelowGrade',
        'broker',
        'square',
        'price',
        'currency_id',
        'is_featured',
        'featured_priority',
        'status',
        'moderation_status',
        'expire_date',
        'auto_renew',
        'never_expired',
        'TransactionType',
        'MlsStatus',
        'image_val',
        'zip_code',
        'ParkingSpaces',
        'CoveredSpaces',
        'Basement',
        'ClosePrice',
        'listing_contract_date',
        'listing_modified_at',
        'close_date',
        'purchase_contract_date',
        'created_at',
        'updated_at',
        'private_notes',
    ];

    /** @var list<string> */
    private const HISTORY_SIGNIFICANT = ['price', 'ClosePrice', 'MlsStatus', 'status', 'close_date'];

    public function __construct(
        private readonly PropertySearchSync $searchSync,
    ) {
    }

    public static function enabled(): bool
    {
        return (bool) config('serik.sync.batched_upsert', false);
    }

    /**
     * @param  list<array<string, mixed>>  $items  AMP Property rows (already filtered for changed)
     * @return array{created: int, updated: int, skipped: int, new_ids: list<int>, search_ids: list<int>}
     */
    public function write(array $items): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'new_ids' => [],
            'search_ids' => [],
        ];

        if ($items === []) {
            return $result;
        }

        $keys = [];
        foreach ($items as $item) {
            $key = strtoupper(trim((string) ($item['ListingKey'] ?? '')));
            if ($key !== '') {
                $keys[] = $key;
            }
        }
        $keys = array_values(array_unique($keys));

        $existing = $keys === []
            ? collect()
            : DB::table('re_properties')
                ->whereIn('external_id', $keys)
                ->get()
                ->keyBy(static fn (object $row): string => strtoupper((string) $row->external_id));

        $rows = [];
        $newKeys = [];
        $updateKeys = [];
        $historyPlans = [];

        foreach ($items as $item) {
            $key = strtoupper(trim((string) ($item['ListingKey'] ?? '')));
            if ($key === '') {
                $result['skipped']++;

                continue;
            }

            $current = $existing->get($key);
            $isNew = $current === null;
            $row = $this->mapItemToRow($item, $current);
            if ($row === null) {
                $result['skipped']++;

                continue;
            }

            $rows[$key] = $row;
            if ($isNew) {
                $newKeys[] = $key;
                $historyPlans[$key] = ['is_new' => true, 'before' => null];
            } else {
                $updateKeys[] = $key;
                $historyPlans[$key] = ['is_new' => false, 'before' => $current];
            }
        }

        if ($rows === []) {
            return $result;
        }

        $chunk = max(25, min(250, (int) config('serik.sync.upsert_chunk', 100)));

        try {
            DB::transaction(function () use ($rows, $chunk): void {
                foreach (array_chunk(array_values($rows), $chunk) as $batch) {
                    DB::table('re_properties')->upsert(
                        $batch,
                        ['external_id'],
                        self::UPDATE_COLUMNS
                    );
                }
            });
        } catch (Throwable $e) {
            Log::error('[AmpLivePropertyBatchWriter] upsert failed', [
                'error' => $e->getMessage(),
                'rows' => count($rows),
            ]);

            throw $e;
        }

        $idByKey = DB::table('re_properties')
            ->whereIn('external_id', array_keys($rows))
            ->pluck('id', 'external_id')
            ->mapWithKeys(static fn ($id, $key) => [strtoupper((string) $key) => (int) $id])
            ->all();

        foreach ($newKeys as $key) {
            $id = $idByKey[$key] ?? 0;
            if ($id > 0) {
                $result['new_ids'][] = $id;
                $result['created']++;
            }
        }
        foreach ($updateKeys as $key) {
            if (($idByKey[$key] ?? 0) > 0) {
                $result['updated']++;
            }
        }

        $this->createSlugsForNew($newKeys, $idByKey, $rows);
        $this->recordHistorySnapshots($historyPlans, $idByKey, $rows);

        $searchIds = array_values(array_filter(array_map(
            static fn (string $key): int => (int) ($idByKey[$key] ?? 0),
            array_keys($rows)
        )));
        $result['search_ids'] = $searchIds;
        $result['new_ids'] = array_values(array_unique($result['new_ids']));

        if ($searchIds !== []) {
            try {
                $this->searchSync->scheduleMany($searchIds);
            } catch (Throwable $e) {
                Log::warning('[AmpLivePropertyBatchWriter] search schedule failed: ' . $e->getMessage());
            }
        }

        $this->bumpHomepageCachesOnce();

        return $result;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    public function mapItemToRow(array $item, ?object $existing): ?array
    {
        $listingKey = trim((string) ($item['ListingKey'] ?? ''));
        if ($listingKey === '') {
            return null;
        }

        $contractDate = $this->parseAmpDate($item['ListingContractDate'] ?? $item['OriginalEntryTimestamp'] ?? null);
        $modifiedDate = $this->parseAmpDate($item['ModificationTimestamp'] ?? $item['PriceChangeTimestamp'] ?? null);
        $purchaseDate = $this->parseAmpDate($item['PurchaseContractDate'] ?? null);
        $closeDate = $this->parseAmpDate($item['CloseDate'] ?? null);
        $soldDate = $purchaseDate ?? $closeDate ?? $modifiedDate;

        $mediaUrl = ! empty($item['Media']) ? ($item['Media'][0]['MediaURL'] ?? null) : null;
        $existingImage = $existing->image_val ?? null;
        $existingCreated = $existing->created_at ?? null;
        $existingViews = isset($existing->views) ? (int) $existing->views : 0;
        $existingLat = isset($existing->latitude) ? (float) $existing->latitude : 0.0;
        $existingLng = isset($existing->longitude) ? (float) $existing->longitude : 0.0;

        $expire = $item['ExpirationDate'] ?? null;
        if (empty($expire)) {
            $expire = now()->addYear()->toDateTimeString();
        } else {
            $expireParsed = $this->parseAmpDate($expire);
            $expire = $expireParsed ?? now()->addYear()->toDateTimeString();
        }

        $basement = $item['Basement'] ?? '0';
        if (is_array($basement)) {
            $basement = implode(', ', $basement);
        }

        $isNew = $existing === null;

        return [
            'external_id' => $listingKey,
            'unique_id' => $isNew ? $this->generateUniqueId() : (string) ($existing->unique_id ?? $this->generateUniqueId()),
            'author_id' => $isNew ? 1 : (int) ($existing->author_id ?? 1),
            'author_type' => $isNew ? 'Botble\ACL\Models\User' : (string) ($existing->author_type ?? 'Botble\ACL\Models\User'),
            'name' => (string) ($item['UnparsedAddress'] ?? ''),
            'PropertySubType' => (string) ($item['PropertySubType'] ?? 'sell'),
            'description' => (string) ($item['PublicRemarks'] ?? ''),
            'content' => (string) (($item['PublicRemarks'] ?? '') . '<br>' . ($item['PrivateRemarks'] ?? '')),
            'location' => (string) ($item['UnparsedAddress'] ?? ''),
            'number_bedroom' => $this->extractMainBedrooms($item),
            'number_bathroom' => (int) ($item['BathroomsTotalInteger'] ?? 0),
            'number_floor' => $this->extractNumberFloor($item),
            'BedroomsBelowGrade' => (string) max(0, (int) ($item['BedroomsBelowGrade'] ?? 0)),
            'broker' => $item['ListOfficeName'] ?? null,
            'square' => is_array($item['LivingAreaRange'] ?? null)
                ? null
                : $this->normalizeSquare($item['LivingAreaRange'] ?? null),
            'price' => (float) ($item['ListPrice'] ?? 0),
            'currency_id' => 1,
            'is_featured' => 0,
            'featured_priority' => 0,
            'status' => (($item['StandardStatus'] ?? '') === 'Active') ? 'selling' : 'draft',
            'moderation_status' => 'approved',
            'expire_date' => $expire,
            'auto_renew' => 1,
            'never_expired' => 1,
            'TransactionType' => trim((string) ($item['TransactionType'] ?? '')) ?: 'For Sale',
            'MlsStatus' => (string) ($item['MlsStatus'] ?? 'Active'),
            // Preserve coords on update (same as Eloquent firstOrNew path).
            'latitude' => $isNew ? 0.0 : ($existingLat ?: 0.0),
            'longitude' => $isNew ? 0.0 : ($existingLng ?: 0.0),
            'image_val' => $mediaUrl ?: $existingImage,
            'zip_code' => $item['PostalCode'] ?? null,
            'views' => $existingViews,
            'ParkingSpaces' => (string) (int) ($item['ParkingSpaces'] ?? 0),
            'CoveredSpaces' => (int) ($item['CoveredSpaces'] ?? 0),
            'Basement' => (string) $basement,
            'ClosePrice' => (int) ($item['ClosePrice'] ?? 0),
            'listing_contract_date' => $contractDate,
            'listing_modified_at' => $modifiedDate,
            'close_date' => $soldDate ?? $closeDate,
            'purchase_contract_date' => $soldDate ?? $purchaseDate,
            'created_at' => $contractDate ?? $existingCreated ?? now()->toDateTimeString(),
            'updated_at' => $modifiedDate ?? now()->toDateTimeString(),
            'private_notes' => (string) ($item['PrivateRemarks'] ?? ''),
            'period' => $isNew ? 'month' : (string) ($existing->period ?? 'month'),
            'geocoding_status' => $isNew ? 'pending' : (string) ($existing->geocoding_status ?? 'pending'),
            'country_id' => $isNew ? 1 : (int) ($existing->country_id ?? 1),
        ];
    }

    /**
     * @param  list<string>  $newKeys
     * @param  array<string, int>  $idByKey
     * @param  array<string, array<string, mixed>>  $rows
     */
    private function createSlugsForNew(array $newKeys, array $idByKey, array $rows): void
    {
        if ($newKeys === [] || ! class_exists(SlugHelper::class)) {
            return;
        }

        foreach ($newKeys as $key) {
            $id = $idByKey[$key] ?? 0;
            if ($id <= 0) {
                continue;
            }

            try {
                $property = Property::query()->find($id);
                if (! $property) {
                    continue;
                }

                $slug = Str::slug((string) ($rows[$key]['name'] ?? '')) . '-' . strtolower($key);
                SlugHelper::createSlug($property, $slug);
            } catch (Throwable $e) {
                Log::warning('[AmpLivePropertyBatchWriter] slug failed', [
                    'external_id' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array<string, array{is_new: bool, before: ?object}>  $plans
     * @param  array<string, int>  $idByKey
     * @param  array<string, array<string, mixed>>  $rows
     */
    private function recordHistorySnapshots(array $plans, array $idByKey, array $rows): void
    {
        if (! Schema::hasTable('re_property_history')) {
            return;
        }

        foreach ($plans as $key => $plan) {
            $id = $idByKey[$key] ?? 0;
            $row = $rows[$key] ?? null;
            if ($id <= 0 || $row === null) {
                continue;
            }

            try {
                if ($plan['is_new']) {
                    DB::table('re_property_history')->insert([
                        'property_id' => $id,
                        'external_id' => $row['external_id'],
                        'event' => 'listed',
                        'price' => is_numeric($row['price'] ?? null) ? (float) $row['price'] : null,
                        'close_price' => is_numeric($row['ClosePrice'] ?? null) ? (float) $row['ClosePrice'] : null,
                        'mls_status' => $row['MlsStatus'] ?? null,
                        'transaction_type' => $row['TransactionType'] ?? null,
                        'status' => $row['status'] ?? null,
                        'listing_contract_date' => $row['listing_contract_date'] ?? null,
                        'listing_modified_at' => $row['listing_modified_at'] ?? null,
                        'close_date' => $row['close_date'] ?? null,
                        'purchase_contract_date' => $row['purchase_contract_date'] ?? null,
                        'changed' => null,
                        'snapshot' => json_encode($this->historySnapshot($row)),
                        'source' => 'amp',
                        'recorded_at' => now(),
                    ]);

                    continue;
                }

                $before = $plan['before'];
                if (! $before) {
                    continue;
                }

                $changed = [];
                foreach (['price', 'ClosePrice', 'MlsStatus', 'TransactionType', 'status', 'close_date', 'purchase_contract_date', 'listing_contract_date', 'listing_modified_at'] as $field) {
                    $old = $before->{$field} ?? null;
                    $new = $row[$field] ?? null;
                    if ($this->valuesDiffer($old, $new)) {
                        $changed[$field] = ['old' => $old, 'new' => $new];
                    }
                }

                if ($changed === [] || empty(array_intersect(array_keys($changed), self::HISTORY_SIGNIFICANT))) {
                    continue;
                }

                $mls = (string) ($row['MlsStatus'] ?? '');
                $event = isset($changed['MlsStatus'])
                    ? (str_contains($mls, 'Sold') ? 'sold' : (str_contains($mls, 'Leased') ? 'leased' : 'status_change'))
                    : (isset($changed['price']) || isset($changed['ClosePrice']) ? 'price_change' : 'updated');

                DB::table('re_property_history')->insert([
                    'property_id' => $id,
                    'external_id' => $row['external_id'],
                    'event' => $event,
                    'price' => is_numeric($row['price'] ?? null) ? (float) $row['price'] : null,
                    'close_price' => is_numeric($row['ClosePrice'] ?? null) ? (float) $row['ClosePrice'] : null,
                    'mls_status' => $row['MlsStatus'] ?? null,
                    'transaction_type' => $row['TransactionType'] ?? null,
                    'status' => $row['status'] ?? null,
                    'listing_contract_date' => $row['listing_contract_date'] ?? null,
                    'listing_modified_at' => $row['listing_modified_at'] ?? null,
                    'close_date' => $row['close_date'] ?? null,
                    'purchase_contract_date' => $row['purchase_contract_date'] ?? null,
                    'changed' => json_encode($changed),
                    'snapshot' => json_encode($this->historySnapshot($row)),
                    'source' => 'amp',
                    'recorded_at' => now(),
                ]);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function historySnapshot(array $row): array
    {
        return [
            'price' => $row['price'] ?? null,
            'ClosePrice' => $row['ClosePrice'] ?? null,
            'MlsStatus' => $row['MlsStatus'] ?? null,
            'TransactionType' => $row['TransactionType'] ?? null,
            'status' => $row['status'] ?? null,
            'close_date' => $row['close_date'] ?? null,
            'purchase_contract_date' => $row['purchase_contract_date'] ?? null,
            'listing_contract_date' => $row['listing_contract_date'] ?? null,
            'listing_modified_at' => $row['listing_modified_at'] ?? null,
            'name' => $row['name'] ?? null,
            'zip_code' => $row['zip_code'] ?? null,
            'number_bedroom' => $row['number_bedroom'] ?? null,
            'number_bathroom' => $row['number_bathroom'] ?? null,
        ];
    }

    private function bumpHomepageCachesOnce(): void
    {
        try {
            HomepageFeaturedCache::bump();
            RealEstateCountCache::bump();
            ShortcodeRenderCache::bumpPropertyDependents();
            HomepageFragmentCache::bumpPropertyDependents();
        } catch (Throwable $e) {
            Log::debug('[AmpLivePropertyBatchWriter] homepage cache bump skipped: ' . $e->getMessage());
        }
    }

    private function valuesDiffer(mixed $a, mixed $b): bool
    {
        if ($a === null || $b === null) {
            return $a !== $b;
        }
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a !== (float) $b;
        }

        return (string) $a !== (string) $b;
    }

    private function parseAmpDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeSquare(mixed $value): ?string
    {
        if (! $value || is_array($value)) {
            return null;
        }

        $value = trim((string) $value);
        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $value)) {
            return $value;
        }

        return TrebPropertyHelper::normalizeSquareStorage($value);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function extractMainBedrooms(array $item): int
    {
        if (isset($item['BedroomsAboveGrade']) && is_numeric($item['BedroomsAboveGrade'])) {
            return max(0, (int) $item['BedroomsAboveGrade']);
        }

        $total = (int) ($item['BedroomsTotal'] ?? 0);
        $below = max(0, (int) ($item['BedroomsBelowGrade'] ?? 0));

        if ($total > 0 && $total >= $below) {
            return $total - $below;
        }

        return max(0, $total);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function extractNumberFloor(array $item): int
    {
        $candidates = [
            $item['StoriesTotal'] ?? null,
            $item['Levels'] ?? null,
            is_array($item['ArchitecturalStyle'] ?? null)
                ? implode(' ', $item['ArchitecturalStyle'])
                : ($item['ArchitecturalStyle'] ?? null),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                return max(0, (int) $candidate);
            }
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }
            if (preg_match('/(\d+(?:\.\d+)?)\s*-\s*storey/i', $candidate, $m)) {
                return max(0, (int) round((float) $m[1]));
            }
            if (preg_match('/(\d+(?:\.\d+)?)\s*(?:storey|story|floor)/i', $candidate, $m)) {
                return max(0, (int) round((float) $m[1]));
            }
        }

        return 0;
    }

    private function generateUniqueId(int $length = 10): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

        return substr(str_shuffle($chars), 0, $length);
    }
}
