<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Http\Controllers\API\PropertyController;
use Botble\RealEstate\Models\Property;
use Botble\Slug\Facades\SlugHelper;
use Botble\Slug\Models\Slug;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Theme\homzen\Supports\TrebPropertyHelper;

/**
 * Real-time TREB/AMP fallback when a listing is missing locally.
 *
 * Search order: local MySQL → live AMP (TRREB_AUTH) → cache miss marker.
 * Successful ingests schedule {@see \App\Jobs\ImportLiveTrebPropertyJob}.
 */
class LiveTrebPropertyFallbackService
{
    public const MISS_CACHE_SECONDS = 600;

    public const SEARCH_MISS_PREFIX = 'serik:treb-search-miss:';

    public const LIVE_PENDING_PREFIX = 'serik:treb-live-pending:';

    public function findLocalByListingKey(string $listingKey): ?Property
    {
        $listingKey = $this->normalizeListingKey($listingKey);

        if ($listingKey === '') {
            return null;
        }

        return Property::query()
            ->where('external_id', $listingKey)
            ->orWhere('external_id', strtolower($listingKey))
            ->first();
    }

    public function isListingKey(string $keyword): bool
    {
        return (bool) preg_match('/^[a-z]{1,2}\d{5,}$/i', trim($keyword));
    }

    public function normalizeListingKey(string $key): string
    {
        $key = strtoupper(trim($key));

        if ($key !== '' && preg_match('/^[A-Z]{1,2}\d{5,}$/', $key)) {
            return $key;
        }

        if (preg_match('/-([A-Za-z]\d{5,})$/', $key, $matches)) {
            return strtoupper($matches[1]);
        }

        return $key;
    }

    /**
     * Resolve a property slug for SSR detail pages (local first, then live ingest).
     */
    public function resolveSlugForRequest(string $slugKey): ?Slug
    {
        $listingKey = PropertySlugResolver::extractListingKey($slugKey);

        if ($listingKey === null) {
            return null;
        }

        $property = $this->findLocalByListingKey($listingKey);

        if ($property === null) {
            $property = $this->ingestByListingKey($listingKey);
        }

        if ($property === null) {
            return null;
        }

        return $this->slugForProperty($property);
    }

    /**
     * Search by MLS / address / postal code — returns one property when unambiguous.
     */
    public function searchAndIngestByKeyword(string $keyword): ?Property
    {
        $keyword = trim(preg_replace('/\s+/', ' ', $keyword) ?? '');

        if ($keyword === '') {
            return null;
        }

        if ($this->isSearchMissCached($keyword)) {
            return null;
        }

        if ($this->isListingKey($keyword)) {
            $key = $this->normalizeListingKey($keyword);
            $local = $this->findLocalByListingKey($key);

            if ($local) {
                return $local;
            }

            $ingested = $this->ingestByListingKey($key);

            if ($ingested === null) {
                $this->cacheSearchMiss($keyword);
            }

            return $ingested;
        }

        $postal = $this->parsePostalCode($keyword);

        if ($postal !== null) {
            $local = Property::query()
                ->where('zip_code', $postal['spaced'])
                ->orWhere('zip_code', $postal['compact'])
                ->orderByDesc('id')
                ->first();

            if ($local) {
                return $local;
            }

            $ingested = $this->ingestBestMatchFromAmpAddressFilter(
                "PostalCode eq '{$postal['spaced']}'",
                $keyword,
                null
            );

            if ($ingested === null) {
                $this->cacheSearchMiss($keyword);
            }

            return $ingested;
        }

        $parsed = $this->parseAddressSearchKeyword($keyword);

        if ($parsed === null) {
            return null;
        }

        $local = $this->findLocalByParsedAddress($parsed);

        if ($local) {
            return $local;
        }

        $ingested = $this->ingestByParsedAddress($parsed);

        if ($ingested === null) {
            $this->cacheSearchMiss($keyword);
        }

        return $ingested;
    }

    /**
     * Fetch listing from AMP, persist minimally, queue full import.
     */
    public function ingestByListingKey(
        string $listingKey,
        bool $indexNow = true,
        bool $geocodeNow = false
    ): ?Property {
        $listingKey = $this->normalizeListingKey($listingKey);

        if ($listingKey === '' || ! $this->isListingKey($listingKey)) {
            return null;
        }

        $local = $this->findLocalByListingKey($listingKey);

        if ($local) {
            return $local;
        }

        if (Cache::get('serik:amp-miss:' . $listingKey)) {
            return null;
        }

        $started = microtime(true);

        try {
            return $this->withLiveFallbackEnabled(function () use ($listingKey, $indexNow, $geocodeNow, $started) {
                $property = app(PropertyController::class)->ingestListingFromAmp($listingKey, $indexNow, $geocodeNow);

                if ($property) {
                    Cache::put(self::LIVE_PENDING_PREFIX . $listingKey, 1, 3600);
                    $this->scheduleBackgroundImport($listingKey);

                    Log::info('Live TREB fallback ingested listing', [
                        'source' => 'treb',
                        'listing_key' => $listingKey,
                        'property_id' => $property->id,
                        'duration_ms' => round((microtime(true) - $started) * 1000),
                    ]);
                }

                return $property;
            });
        } catch (\Throwable $e) {
            Log::warning('Live TREB fallback ingest failed', [
                'listing_key' => $listingKey,
                'duration_ms' => round((microtime(true) - $started) * 1000),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function scheduleBackgroundImport(string $listingKey): void
    {
        $listingKey = $this->normalizeListingKey($listingKey);

        if ($listingKey === '') {
            return;
        }

        if (Cache::has('serik:import-live-dispatched:' . $listingKey)) {
            return;
        }

        Cache::put('serik:import-live-dispatched:' . $listingKey, 1, 300);

        if (class_exists(\App\Jobs\ImportLiveTrebPropertyJob::class)) {
            \App\Jobs\ImportLiveTrebPropertyJob::dispatch($listingKey);
        }
    }

    /**
     * @return array<int, string>
     */
    public function ingestAddressSearchHits(array $parsed, int $limit = 10): array
    {
        $streetNumber = trim((string) ($parsed['street_number'] ?? ''));
        $streetName = trim((string) ($parsed['street_name'] ?? ''));

        if ($streetNumber === '' || $streetName === '') {
            return [];
        }

        $unit = trim((string) ($parsed['unit_number'] ?? ''));
        $escapedNumber = str_replace("'", "''", $streetNumber);
        $escapedName = str_replace("'", "''", $streetName);
        $filter = "StreetNumber eq '{$escapedNumber}' and startswith(StreetName,'{$escapedName}')";

        if ($unit !== '') {
            $escapedUnit = str_replace("'", "''", $unit);
            $filter .= " and UnitNumber eq '{$escapedUnit}'";
        }

        $hits = $this->fetchAmpPropertyHits($filter, max(5, min(25, $limit * 2)));

        $ids = [];

        foreach (array_slice($hits, 0, $limit) as $item) {
            $key = strtoupper(trim((string) ($item['ListingKey'] ?? '')));

            if ($key === '') {
                continue;
            }

            if ($unit !== '' && ! $this->unitMatches($item, $unit)) {
                continue;
            }

            $existingId = DB::table('re_properties')->where('external_id', $key)->value('id');

            if ($existingId) {
                $ids[] = (int) $existingId;

                continue;
            }

            $ingested = $this->ingestByListingKey($key, true, false);

            if ($ingested) {
                $ids[] = (int) $ingested->id;
            }
        }

        return $ids;
    }

    protected function ingestByParsedAddress(array $parsed): ?Property
    {
        $ids = $this->ingestAddressSearchHits($parsed, 1);

        if ($ids === []) {
            return null;
        }

        return Property::query()->find($ids[0]);
    }

    protected function ingestBestMatchFromAmpAddressFilter(
        string $odataFilter,
        string $keyword,
        ?array $parsed
    ): ?Property {
        $hits = $this->fetchAmpPropertyHits($odataFilter, 15);

        if ($hits === []) {
            return null;
        }

        if ($parsed) {
            $hits = array_values(array_filter(
                $hits,
                fn (array $item) => $this->addressMatchesParsedSearch(
                    (string) ($item['UnparsedAddress'] ?? ''),
                    $parsed
                )
            ));
        }

        if (count($hits) > 1 && $parsed && empty($parsed['unit_number'])) {
            // Multiple units at same address — do not guess; caller should show list.
            return null;
        }

        $item = $hits[0] ?? null;
        $key = strtoupper(trim((string) ($item['ListingKey'] ?? '')));

        if ($key === '') {
            return null;
        }

        return $this->ingestByListingKey($key);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchAmpPropertyHits(string $odataFilter, int $top): array
    {
        $select = 'ListingKey,UnparsedAddress,UnitNumber,StreetNumber,StreetName,StreetSuffix,ListPrice,ClosePrice,'
            . 'BedroomsTotal,BathroomsTotalInteger,PropertySubType,MlsStatus,TransactionType,ListingContractDate,'
            . 'ModificationTimestamp,StandardStatus,PostalCode,City';

        $url = 'https://query.ampre.ca/odata/Property?'
            . '$filter=' . rawurlencode($odataFilter)
            . '&$orderby=' . rawurlencode('ListingContractDate desc')
            . '&$top=' . max(1, min(25, $top))
            . '&$select=' . rawurlencode($select);

        $unique = [];

        return $this->withLiveFallbackEnabled(function () use ($url, &$unique) {
            foreach (['live', 'historical'] as $profile) {
                $response = TrebPropertyHelper::ampRequest($url, 12, 2, 'liveTrebAddressSearch', null, $profile);

                if (! $response['ok']) {
                    continue;
                }

                foreach ($response['data']['value'] ?? [] as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $key = strtoupper(trim((string) ($item['ListingKey'] ?? '')));

                    if ($key === '') {
                        continue;
                    }

                    $unique[$key] = $item;
                }
            }

            return array_values($unique);
        });
    }

    protected function findLocalByParsedAddress(array $parsed): ?Property
    {
        $streetNumber = trim((string) ($parsed['street_number'] ?? ''));
        $streetName = trim((string) ($parsed['street_name'] ?? ''));
        $unit = trim((string) ($parsed['unit_number'] ?? ''));

        if ($streetNumber === '' || $streetName === '') {
            return null;
        }

        $query = Property::query()
            ->where('name', 'like', $streetNumber . '%')
            ->where('name', 'like', '%' . $streetName . '%')
            ->orderByDesc('id');

        if ($unit !== '') {
            $query->where(function ($q) use ($unit) {
                $q->where('name', 'like', '%' . $unit . ' -%')
                    ->orWhere('name', 'like', '%' . $unit . '-%')
                    ->orWhere('name', 'like', '%Unit ' . $unit . '%');
            });
        }

        return $query->first();
    }

    /**
     * @return array{street_number:string,street_part:string,street_name:string,unit_number?:string}|null
     */
    public function parseAddressSearchKeyword(string $keyword): ?array
    {
        $keyword = trim(preg_replace('/\s+/', ' ', $keyword) ?? '');

        if ($keyword === '') {
            return null;
        }

        if (str_contains($keyword, ',')) {
            $keyword = trim(explode(',', $keyword, 2)[0]);
        }

        if (str_contains($keyword, ' - ')) {
            $keyword = trim(explode(' - ', $keyword, 2)[0]);
        }

        $keyword = trim(preg_replace('/\b(ON|Ontario|QC|Quebec|BC|AB|MB|SK|NS|NB|NL|PE|YT|NT|NU)\b.*$/i', '', $keyword) ?? $keyword);

        $unitNumber = null;

        if (preg_match('/^(\d{1,5}[A-Za-z]?|PH\d+|TH\d+|[A-Za-z]{1,3}-?\d+)\s+(\d+[A-Za-z]?)\s+(.+)$/i', $keyword, $unitMatch)) {
            $unitNumber = TrebPropertyHelper::normalizeUnitToken($unitMatch[1]);
            $keyword = trim($unitMatch[2] . ' ' . $unitMatch[3]);
        }

        if (! preg_match('/^(\d+[A-Za-z]?)\s+(.+)$/', $keyword, $matches)) {
            return null;
        }

        $streetNumber = $matches[1];
        $rest = trim($matches[2]);
        $tokens = preg_split('/\s+/', $rest) ?: [];
        $streetTokens = [];

        foreach ($tokens as $token) {
            $streetTokens[] = $token;

            if (TrebPropertyHelper::isStreetSuffixWord($token)) {
                break;
            }

            if (count($streetTokens) >= 2) {
                break;
            }
        }

        if ($streetTokens === []) {
            return null;
        }

        $streetPart = implode(' ', $streetTokens);
        $streetName = '';

        foreach ($streetTokens as $token) {
            if (! TrebPropertyHelper::isStreetSuffixWord($token)) {
                $streetName = $token;
                break;
            }
        }

        if ($streetName === '') {
            $streetName = $streetTokens[0];
        }

        $parsed = [
            'street_number' => $streetNumber,
            'street_part' => $streetPart,
            'street_name' => $streetName,
        ];

        if ($unitNumber !== null && $unitNumber !== '') {
            $parsed['unit_number'] = $unitNumber;
        }

        return $parsed;
    }

    /**
     * @return array{compact:string,spaced:string}|null
     */
    public function parsePostalCode(string $keyword): ?array
    {
        $compact = strtoupper(preg_replace('/\s+/', '', trim($keyword)) ?? '');

        if (! preg_match('/^[A-Z]\d[A-Z]\d[A-Z]\d$/', $compact)) {
            return null;
        }

        return [
            'compact' => $compact,
            'spaced' => substr($compact, 0, 3) . ' ' . substr($compact, 3),
        ];
    }

    public function addressMatchesParsedSearch(string $address, array $parsed): bool
    {
        $address = strtolower(trim($address));

        if ($address === '') {
            return false;
        }

        $number = strtolower(trim((string) ($parsed['street_number'] ?? '')));
        $name = strtolower(trim((string) ($parsed['street_name'] ?? '')));

        if ($number === '' || $name === '') {
            return false;
        }

        if (! preg_match('/\b' . preg_quote($number, '/') . '\b/', $address)) {
            return false;
        }

        return (bool) preg_match('/\b' . preg_quote($name, '/') . '\b/', $address);
    }

    protected function unitMatches(array $item, string $unit): bool
    {
        $itemUnit = TrebPropertyHelper::normalizeUnitToken((string) ($item['UnitNumber'] ?? ''));

        return strcasecmp($itemUnit, TrebPropertyHelper::normalizeUnitToken($unit)) === 0;
    }

    protected function slugForProperty(Property $property): Slug
    {
        $existing = Slug::query()
            ->where('reference_type', Property::class)
            ->where('reference_id', $property->getKey())
            ->first();

        if ($existing) {
            return $existing;
        }

        $slugKey = Str::slug($property->name ?? 'property') . '-' . strtolower((string) $property->external_id);

        return SlugHelper::createSlug($property, $slugKey);
    }

    protected function isSearchMissCached(string $keyword): bool
    {
        return (bool) Cache::get(self::SEARCH_MISS_PREFIX . md5(strtolower(trim($keyword))));
    }

    protected function cacheSearchMiss(string $keyword): void
    {
        Cache::put(self::SEARCH_MISS_PREFIX . md5(strtolower(trim($keyword))), 1, self::MISS_CACHE_SECONDS);
    }

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    protected function withLiveFallbackEnabled(callable $callback)
    {
        app()->instance('serik.live_treb_fallback', true);

        try {
            return $callback();
        } finally {
            app()->forgetInstance('serik.live_treb_fallback');
        }
    }
}
