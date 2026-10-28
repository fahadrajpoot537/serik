<?php

namespace App\Services\HomeValuation;

use App\Support\AddressNormalizer;
use App\Support\SerikMediaUrl;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Services\LiveTrebPropertyFallbackService;
use Botble\RealEstate\Services\PropertySearchService;
use Botble\RealEstate\Supports\PropertyFulltextSearch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Theme\homzen\Supports\TrebPropertyHelper;
use Throwable;

/**
 * Deterministic MLS sold-comparable valuation.
 *
 * Groq is never used for the numeric estimate — only optional explanation.
 */
class HomeValuationService
{
    private const SOLD_STATUSES = ['Sold', 'Sold Conditional', 'Sold Conditional Escape'];

    private const ACTIVE_STATUSES = ['New', 'Active', 'Ext', 'Extension', 'Price Change', 'Previous Status', 'Active Under Contract'];

    public function __construct(
        private readonly GroqValuationExplainer $explainer = new GroqValuationExplainer,
    ) {}

    /**
     * @param  array{
     *   address: string,
     *   bedrooms?: int,
     *   bathrooms?: int,
     *   sqft?: float|int|string|null,
     *   property_type?: string,
     *   include_ai?: bool
     * }  $input
     * @return array<string, mixed>
     */
    public function evaluate(array $input): array
    {
        $address = trim((string) ($input['address'] ?? ''));
        $beds = max(0, (int) ($input['bedrooms'] ?? 0));
        $baths = max(0, (int) ($input['bathrooms'] ?? 0));
        $type = trim((string) ($input['property_type'] ?? ''));
        $userSqft = $this->valuationSqft($input['sqft'] ?? null);
        $includeAi = (bool) ($input['include_ai'] ?? true);

        if (mb_strlen($address) < 3) {
            return $this->fail('Please enter a property address.', 'invalid_input');
        }

        $subjectResolution = $this->resolveSubject($address);
        if (($subjectResolution['status'] ?? '') === 'ambiguous_address') {
            return $subjectResolution;
        }

        $subject = $subjectResolution['subject'] ?? null;
        if (! is_array($subject)) {
            return $this->fail(
                'We could not match this address to a listing with enough detail. Pick a suggestion or add bedrooms, bathrooms, square footage, and property type, then try again.',
                'no_subject'
            );
        }

        // Form overrides only when the user actually provided values — never replace
        // MLS subject facts with blank/default Semi-Detached + 0 beds.
        if ($beds > 0) {
            $subject['beds'] = $beds;
        }
        if ($baths > 0) {
            $subject['baths'] = $baths;
        }
        if ($userSqft > 0) {
            $subject['sqft'] = $userSqft;
        }
        if ($type !== '') {
            $subjectType = trim((string) ($subject['type'] ?? ''));
            if ($subjectType === '' || $beds > 0 || $baths > 0 || $userSqft > 0) {
                $subject['type'] = $type;
            }
        }

        // Live AMP ingest often lands with lat/lng=0 — geocode so sold-comp search can run.
        $subject = $this->ensureSubjectCoordinates($subject);

        $subjectFamily = $this->typeFamily((string) ($subject['type'] ?? ''));
        if (in_array($subjectFamily, ['other'], true)
            && preg_match('/\b(land|farm|vacant)\b/i', (string) ($subject['type'] ?? ''))) {
            // Land / farm — sold-comp condo/house AVM is not reliable.
            $history = $this->listingHistoryForSubject($subject);

            return [
                'ok' => false,
                'status' => 'unsupported_type',
                'message' => 'This listing looks like land/farm/vacant land. Automated sold-comp estimates are for residential homes and condos. Please request a local agent review.',
                'subject' => $this->publicSubject($subject),
                'listing_history' => $history,
                'comparables' => [],
                'comps_count' => 0,
                'evaluated_at' => now()->toIso8601String(),
                'disclaimer' => (string) config('home_valuation.disclaimer'),
            ];
        }

        $compSearch = $this->findSoldComparables($subject);
        $valuation = null;

        $asking = null;
        $soldCount = count($compSearch['comps']);
        $soldRadius = (float) ($compSearch['radius_km'] ?? 99);
        if ($soldCount >= (int) config('home_valuation.min_comps', 3)) {
            $valuation = $this->calculateFromSoldComps($subject, $compSearch);
            // Skip asking fetch when local solds already price the home — saves a geo query.
            $needAskingWiden = $soldCount < 6 || $soldRadius > 2.5;
            if ($needAskingWiden) {
                $asking = $this->findAskingComparables(
                    $subject,
                    min(3.5, max(1.25, $soldRadius)),
                    (int) ($compSearch['months'] ?? 12)
                );
                $valuation = $this->widenHighWithAsking($valuation, $subject, $asking);
            }
        } else {
            $asking = $this->findAskingComparables($subject, $compSearch['radius_km'] ?? 5.0, $compSearch['months'] ?? 18);
            if (count($asking['comps']) >= (int) config('home_valuation.min_comps', 3)) {
                $valuation = $this->calculateFromAskingComps($subject, $asking);
            }
        }

        $history = $this->listingHistoryForSubject($subject);

        if ($valuation === null) {
            $valuation = [
                'ok' => false,
                'status' => 'insufficient_data',
                'basis' => null,
                'message' => 'Not enough verified nearby sold comparables (or active listings) were found to produce a reliable estimate. A local agent review is recommended.',
                'comps_count' => count($compSearch['comps']),
                'search' => [
                    'radius_km' => $compSearch['radius_km'] ?? null,
                    'months' => $compSearch['months'] ?? null,
                    'expanded' => (bool) ($compSearch['expanded'] ?? false),
                    'mode' => $compSearch['mode'] ?? null,
                ],
                'subject' => $this->publicSubject($subject),
                'comparables' => [],
                'listing_history' => $history,
                'evaluated_at' => now()->toIso8601String(),
                'disclaimer' => (string) config('home_valuation.disclaimer'),
            ];
        } else {
            $valuation['listing_history'] = $history;
            $valuation['days_on_market'] = $this->predictDaysOnMarket(
                $valuation['comparables'] ?? [],
                (float) ($valuation['estimate'] ?? 0)
            );
        }

        // Active For Sale neighbours as property cards (UI) — not the sold-comp table.
        $cardRadius = (float) ($compSearch['radius_km'] ?? 3.5);
        if ($asking !== null && isset($asking['radius_km'])) {
            $cardRadius = (float) $asking['radius_km'];
        }
        $valuation['for_sale'] = $this->nearbyForSaleCards(
            $subject,
            min(4.0, max(1.5, $cardRadius)),
            8
        );

        if ($includeAi) {
            $ai = $this->explainer->explain($valuation);
            $valuation['explanation'] = $ai['text'];
            $valuation['ai'] = [
                'used' => ($ai['status'] ?? '') === 'ok',
                'status' => $ai['status'] ?? 'unknown',
            ];
        } else {
            $valuation['explanation'] = null;
            $valuation['ai'] = ['used' => false, 'status' => 'skipped'];
        }

        return $valuation;
    }

    /**
     * Pure calculator for tests (synthetic comps only).
     *
     * @param  array<string, mixed>  $subject
     * @param  list<array<string, mixed>>  $comps
     * @return array<string, mixed>
     */
    public function calculateFromSyntheticSoldComps(array $subject, array $comps, array $searchMeta = []): array
    {
        return $this->calculateFromSoldComps($subject, [
            'comps' => $comps,
            'radius_km' => $searchMeta['radius_km'] ?? 2.0,
            'months' => $searchMeta['months'] ?? 12,
            'expanded' => (bool) ($searchMeta['expanded'] ?? false),
            'mode' => 'synthetic',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveSubject(string $address): array
    {
        $parsed = AddressNormalizer::parseQuery($address);
        $rows = $this->suggestRows($address, $parsed);

        if ($parsed && ! empty($parsed['street_number']) && ! empty($parsed['street_name'])) {
            $matched = array_values(array_filter(
                $rows,
                fn (array $row) => AddressNormalizer::addressMatches(
                    (string) ($row['match_address'] ?? $row['address'] ?? ''),
                    $parsed
                )
            ));
            if ($matched !== []) {
                $rows = $matched;
            }
        }

        if ($rows === []) {
            return ['status' => 'no_subject', 'subject' => null];
        }

        $uniqueAddresses = [];
        foreach ($rows as $row) {
            $key = mb_strtolower(trim((string) ($row['address'] ?? '')));
            if ($key !== '') {
                $uniqueAddresses[$key] = true;
            }
        }

        $queryUnit = $this->extractUnitToken($address);
        $queryHasUnit = $queryUnit !== null || $this->addressSpecifiesUnit($address);
        $unitKeys = [];
        foreach (array_keys($uniqueAddresses) as $key) {
            $unit = $this->extractUnitToken($key);
            if ($unit !== null) {
                $unitKeys[$unit] = true;
            }
        }
        $multiUnitBuilding = count($unitKeys) > 1;

        // When the user picked a specific unit, narrow to that unit only.
        if ($queryUnit !== null) {
            $unitRows = array_values(array_filter(
                $rows,
                fn (array $row) => $this->extractUnitToken((string) ($row['address'] ?? '')) === $queryUnit
            ));
            if ($unitRows !== []) {
                $rows = $unitRows;
                $uniqueAddresses = [];
                foreach ($rows as $row) {
                    $key = mb_strtolower(trim((string) ($row['address'] ?? '')));
                    if ($key !== '') {
                        $uniqueAddresses[$key] = true;
                    }
                }
                $multiUnitBuilding = false;
            }
        }

        // Condo/building: several units matched and the user did not specify a unit.
        // Vague query with many distinct streets also needs a pick.
        // Same civic # in different cities (London vs Barrie) also needs a pick.
        $hasCivic = is_array($parsed) && ! empty($parsed['street_number']);
        $needsPick = (! $queryHasUnit && $multiUnitBuilding)
            || (! $hasCivic && count($uniqueAddresses) > 3)
            || ($hasCivic && count($uniqueAddresses) > 1 && empty($parsed['municipality']));

        if ($needsPick) {
            // Prefer one suggestion per unit so the picker is usable.
            $suggestionRows = [];
            $seenUnits = [];
            foreach ($rows as $row) {
                $addr = (string) ($row['address'] ?? '');
                $unit = $this->extractUnitToken($addr) ?? mb_strtolower(trim($addr));
                if (isset($seenUnits[$unit])) {
                    continue;
                }
                $seenUnits[$unit] = true;
                $suggestionRows[] = $row;
                if (count($suggestionRows) >= 8) {
                    break;
                }
            }

            return [
                'ok' => false,
                'status' => 'ambiguous_address',
                'message' => $multiUnitBuilding
                    ? 'This building has multiple units. Please select your unit from the list.'
                    : 'Several addresses matched. Please choose a specific suggestion from the list.',
                'suggestions' => array_values(array_map(
                    static fn (array $r) => ['address' => $r['address'] ?? null],
                    $suggestionRows
                )),
            ];
        }

        // Prefer non-lease rows with usable price, then any row with coords.
        usort($rows, static function (array $a, array $b): int {
            $score = static function (array $r): int {
                $s = 0;
                $status = strtolower((string) ($r['mls_status'] ?? ''));
                if (! str_contains($status, 'leas')) {
                    $s += 20;
                }
                if ((float) ($r['close_price'] ?? 0) >= 50000 || (float) ($r['list_price'] ?? 0) >= 50000) {
                    $s += 10;
                }
                if ((float) ($r['latitude'] ?? 0) != 0.0 && (float) ($r['longitude'] ?? 0) != 0.0) {
                    $s += 15;
                }

                return $s;
            };

            return $score($b) <=> $score($a);
        });

        return ['status' => 'ok', 'subject' => $rows[0]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function suggestRows(string $keyword, ?array $parsed): array
    {
        // Direct MLS# (e.g. X12378459)
        if (preg_match('/^[A-Za-z]\d{5,12}$/', trim($keyword))) {
            $byKey = DB::table('re_properties')
                ->select($this->subjectColumns())
                ->where('moderation_status', 'approved')
                ->where('external_id', strtoupper(trim($keyword)))
                ->limit(3)
                ->get();
            if ($byKey->isNotEmpty()) {
                $out = [];
                foreach ($byKey as $item) {
                    $out[] = $this->mapSubjectRow($item);
                }

                return $this->uniqueSubjects($out, null);
            }
        }

        // Meili / FULLTEXT first — `name LIKE` on re_properties is a multi-second table scan.
        $ids = [];
        $meiliHealthy = Cache::get('serik_meili_health_v2');
        if ($meiliHealthy) {
            try {
                $opts = [
                    'limit' => $parsed ? 24 : 14,
                    'offset' => 0,
                    'residential_only' => false,
                ];
                $meiliKeyword = $keyword;
                if ($parsed) {
                    $opts['street_number'] = $parsed['street_number'];
                    $opts['matching_strategy'] = 'all';
                    $meiliKeyword = AddressNormalizer::meiliQuery($parsed);
                }
                $found = app(PropertySearchService::class)->searchIds($meiliKeyword, $opts);
                if (is_array($found)) {
                    $ids = array_values(array_filter(array_map('intval', $found)));
                }
            } catch (Throwable $e) {
                Log::warning('Home valuation Meili suggest failed', ['message' => $e->getMessage()]);
            }
        }

        if ($ids !== []) {
            $mapped = $this->mapSubjectRowsByIds($ids, $parsed);
            if ($mapped !== []) {
                return $mapped;
            }
        }

        $fulltext = $this->fulltextSubjectRows($keyword, $parsed);
        if ($fulltext !== []) {
            return $fulltext;
        }

        // Skip name LIKE / exact name — `name` is FULLTEXT-only; equality/prefix are table scans (~20s+).
        if ($parsed && ! empty($parsed['street_number']) && ! empty($parsed['street_name'])) {
            try {
                $ingestedIds = app(LiveTrebPropertyFallbackService::class)
                    ->ingestAddressSearchHits($parsed, 8);
                if ($ingestedIds !== []) {
                    $mapped = $this->mapSubjectRowsByIds($ingestedIds, $parsed);
                    if ($mapped !== []) {
                        return $mapped;
                    }
                }
            } catch (Throwable $e) {
                Log::warning('Home valuation AMP subject ingest failed', ['message' => $e->getMessage()]);
            }

            return [];
        }

        return $this->exactNameSubjectRows($keyword, $parsed);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exactNameSubjectRows(string $address, ?array $parsed): array
    {
        $address = trim($address);
        if ($address === '') {
            return [];
        }

        // Equality only (no leading LIKE) — used as last resort after Meili/FULLTEXT.
        $items = DB::table('re_properties')
            ->select($this->subjectColumns())
            ->where('moderation_status', 'approved')
            ->where('name', $address)
            ->orderByRaw('(latitude IS NOT NULL AND latitude <> 0 AND longitude IS NOT NULL AND longitude <> 0) DESC')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $out = [];
        foreach ($items as $item) {
            $out[] = $this->mapSubjectRow($item);
        }

        // Exact DB hits are already address-scoped; do not re-filter by parser tokens.
        return $this->uniqueSubjects($out, null);
    }

    /**
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    private function mapSubjectRowsByIds(array $ids, ?array $parsed): array
    {
        $rowsById = DB::table('re_properties')
            ->select($this->subjectColumns())
            ->whereIn('id', $ids)
            ->where('moderation_status', 'approved')
            ->get()
            ->keyBy('id');

        $out = [];
        foreach ($ids as $id) {
            $item = $rowsById->get($id);
            if ($item) {
                $out[] = $this->mapSubjectRow($item);
            }
        }

        return $this->uniqueSubjects($out, $parsed);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fulltextSubjectRows(string $keyword, ?array $parsed): array
    {
        $query = PropertyFulltextSearch::baseQuery($this->subjectColumns());
        if ($parsed) {
            PropertyFulltextSearch::applyRequiredTokensFulltext(
                $query,
                (string) $parsed['street_number'],
                (string) ($parsed['street_part'] ?? $parsed['street_name'] ?? '')
            );
            $this->applyShortCivicNumberConstraint($query, (string) ($parsed['street_number'] ?? ''));
        } else {
            PropertyFulltextSearch::applyKeywordFulltext($query, $keyword);
        }

        $items = $query->limit(20)->get();
        if ($items->isEmpty() && $parsed) {
            $query = PropertyFulltextSearch::baseQuery($this->subjectColumns());
            PropertyFulltextSearch::applyKeywordFulltext($query, $keyword);
            $this->applyShortCivicNumberConstraint($query, (string) ($parsed['street_number'] ?? ''));
            $items = $query->limit(20)->get();
        }

        $out = [];
        foreach ($items as $item) {
            $out[] = $this->mapSubjectRow($item);
        }

        return $this->uniqueSubjects($out, $parsed);
    }

    private function applyShortCivicNumberConstraint($query, string $streetNumber): void
    {
        $streetNumber = trim($streetNumber);
        if ($streetNumber === '' || mb_strlen($streetNumber) >= 3) {
            return;
        }

        // Prefix only — leading-% OR forces a full table scan on FULLTEXT-only `name`.
        $query->where('name', 'like', $streetNumber . ' %');
    }

    /**
     * @return list<string>
     */
    private function subjectColumns(): array
    {
        return [
            'id', 'name', 'external_id', 'number_bedroom', 'number_bathroom',
            'BedroomsBelowGrade', 'PropertySubType', 'type', 'square', 'price',
            'ClosePrice', 'MlsStatus', 'TransactionType', 'latitude', 'longitude',
            'close_date', 'listing_contract_date',
        ];
    }

    private function mapSubjectRow(object $item): array
    {
        $fullName = (string) ($item->name ?? '');
        $record = TrebPropertyHelper::enrichRecordAddress([
            'UnparsedAddress' => $fullName,
            'name' => $fullName,
        ]);
        $street = TrebPropertyHelper::formatDisplayAddress($record) ?: $fullName;
        $city = trim((string) ($record['City'] ?? $record['CityRegion'] ?? ''));
        if (preg_match('/^(London|Toronto|Ottawa|Hamilton|Barrie|Mississauga|Brampton|Vaughan|Markham|Oakville|Burlington)\s+(North|South|East|West|Central)\b/i', $city, $m)) {
            $city = $m[1];
        }
        $address = $street;
        if ($city !== '' && ! str_contains(mb_strtolower($address), mb_strtolower($city))) {
            $address .= ', ' . $city . (preg_match('/\b(ON|Ontario)\b/i', $city) ? '' : ', Ontario');
        }

        return [
            'id' => (int) $item->id,
            'external_id' => (string) ($item->external_id ?? ''),
            'address' => $address,
            // Full MLS name (with city) — used for city-aware matching / history.
            'match_address' => $fullName !== '' ? $fullName : $address,
            'beds' => (int) ($item->number_bedroom ?? 0),
            'baths' => (int) ($item->number_bathroom ?? 0),
            'sqft' => $this->valuationSqft($item->square ?? null),
            'type' => trim((string) ($item->PropertySubType ?? $item->type ?? '')),
            'list_price' => (float) ($item->price ?? 0),
            'close_price' => (float) ($item->ClosePrice ?? 0),
            'mls_status' => (string) ($item->MlsStatus ?? ''),
            'transaction_type' => (string) ($item->TransactionType ?? ''),
            'latitude' => (float) ($item->latitude ?? 0),
            'longitude' => (float) ($item->longitude ?? 0),
            'close_date' => $item->close_date ?? null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function uniqueSubjects(array $rows, ?array $parsed): array
    {
        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            $hay = (string) ($row['match_address'] ?? $row['address'] ?? '');
            if ($parsed && ! empty($parsed['street_number']) && ! empty($parsed['street_name'])) {
                if (! AddressNormalizer::addressMatches($hay, $parsed)) {
                    continue;
                }
            }
            $key = mb_strtolower(trim((string) ($row['address'] ?? $hay)));
            if ($key === '') {
                continue;
            }
            if (isset($seen[$key])) {
                $idx = $seen[$key];
                if ($this->subjectPreferScore($row) > $this->subjectPreferScore($out[$idx])) {
                    $out[$idx] = $row;
                }
                continue;
            }
            $seen[$key] = count($out);
            $out[] = $row;
            if (count($out) >= 8) {
                break;
            }
        }

        return $out;
    }

    /**
     * Prefer active/new sale listings (and geocoded rows) when collapsing same address.
     */
    private function subjectPreferScore(array $row): int
    {
        $s = 0;
        $status = strtolower((string) ($row['mls_status'] ?? ''));
        if (in_array($status, ['new', 'active', 'price change', 'ext', 'extension', 'previous status', 'active under contract'], true)) {
            $s += 50;
        } elseif (str_contains($status, 'sold')) {
            $s += 20;
        } elseif (str_contains($status, 'terminat') || str_contains($status, 'expir')) {
            $s += 5;
        }
        if (! str_contains($status, 'leas')) {
            $s += 10;
        }
        if ((float) ($row['latitude'] ?? 0) != 0.0 && (float) ($row['longitude'] ?? 0) != 0.0) {
            $s += 15;
        }
        if ((float) ($row['close_price'] ?? 0) >= 50000 || (float) ($row['list_price'] ?? 0) >= 50000) {
            $s += 8;
        }

        return $s;
    }

    /**
     * @param  array<string, mixed>  $subject
     * @return array<string, mixed>
     */
    private function ensureSubjectCoordinates(array $subject): array
    {
        $lat = (float) ($subject['latitude'] ?? 0);
        $lng = (float) ($subject['longitude'] ?? 0);
        if (abs($lat) > 0.01 && abs($lng) > 0.01) {
            return $subject;
        }

        $query = trim((string) ($subject['match_address'] ?? $subject['address'] ?? ''));
        if ($query === '') {
            return $subject;
        }
        if (! preg_match('/\b(ON|Ontario|Canada)\b/i', $query)) {
            $query .= ', Ontario, Canada';
        }

        // Hard 4s budget — valuation must not wait on slow geocoders (was 20s+12s).
        $cacheKey = 'home_eval_geo_v1:' . md5(mb_strtolower($query));
        $coords = Cache::get($cacheKey);
        if (! is_array($coords) || ! isset($coords['lat'], $coords['lng'])) {
            $coords = null;
            try {
                $google = app(\App\Services\Geocoding\GoogleGeocodingService::class);
                if ($google->isConfigured()) {
                    $key = (string) (
                        config('geocoding.providers.google.key')
                        ?: config('services.google_maps.geocoding_api_key')
                        ?: ''
                    );
                    if ($key !== '') {
                        $endpoint = rtrim((string) (
                            config('geocoding.providers.google.url')
                            ?: config('services.google_maps.geocoding_url')
                            ?: 'https://maps.googleapis.com/maps/api/geocode/json'
                        ), '/');
                        $response = \Illuminate\Support\Facades\Http::timeout(4)
                            ->acceptJson()
                            ->get($endpoint, [
                                'address' => $query,
                                'key' => $key,
                                'region' => 'ca',
                                'components' => 'country:CA',
                            ]);
                        if ($response->ok()) {
                            $loc = $response->json('results.0.geometry.location');
                            if (is_array($loc) && isset($loc['lat'], $loc['lng'])) {
                                $coords = ['lat' => (float) $loc['lat'], 'lng' => (float) $loc['lng']];
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                Log::warning('Home valuation Google geocode failed', ['message' => $e->getMessage()]);
            }

            if ($coords === null) {
                try {
                    $apiKey = (string) config('census.geoapify.api_key', env('GEOAPIFY_API_KEY', ''));
                    if ($apiKey !== '') {
                        $response = \Illuminate\Support\Facades\Http::timeout(4)
                            ->get('https://api.geoapify.com/v1/geocode/search', [
                                'text' => $query,
                                'apiKey' => $apiKey,
                                'limit' => 1,
                                'filter' => 'countrycode:ca',
                            ]);
                        if ($response->ok()) {
                            $feat = $response->json('features.0.geometry.coordinates');
                            if (is_array($feat) && count($feat) >= 2) {
                                $coords = ['lat' => (float) $feat[1], 'lng' => (float) $feat[0]];
                            }
                        }
                    }
                } catch (Throwable $e) {
                    Log::warning('Home valuation Geoapify geocode failed', ['message' => $e->getMessage()]);
                }
            }

            if (is_array($coords) && isset($coords['lat'], $coords['lng'])) {
                Cache::put($cacheKey, $coords, 86400);
            }
        }

        if (! is_array($coords) || ! isset($coords['lat'], $coords['lng'])) {
            return $subject;
        }

        $subject['latitude'] = (float) $coords['lat'];
        $subject['longitude'] = (float) $coords['lng'];

        $id = (int) ($subject['id'] ?? 0);
        if ($id > 0) {
            try {
                DB::table('re_properties')->where('id', $id)->update([
                    'latitude' => $subject['latitude'],
                    'longitude' => $subject['longitude'],
                ]);
            } catch (Throwable $e) {
                // Non-fatal — comps can still use in-memory coords.
            }
        }

        return $subject;
    }

    /**
     * @param  array<string, mixed>  $subject
     * @return array{comps: list<array<string, mixed>>, radius_km: float, months: int, expanded: bool, mode: string}
     */
    private function findSoldComparables(array $subject): array
    {
        $lat = (float) ($subject['latitude'] ?? 0);
        $lng = (float) ($subject['longitude'] ?? 0);
        $hasGeo = abs($lat) > 0.01 && abs($lng) > 0.01;

        $radii = config('home_valuation.radius_km_steps', [1.25, 2.0, 3.5, 6.0]);
        $monthsSteps = config('home_valuation.months_steps', [12, 18]);
        $minComps = (int) config('home_valuation.min_comps', 3);
        // HouseSigma-style: prefer a tight local set over a wide diluted pool.
        $targetPool = max($minComps, 5);
        $goodLocal = max($minComps, 4);

        $buildingComps = $hasGeo ? $this->querySameBuildingSoldComps($subject, $lat, $lng) : [];

        $best = ['comps' => $buildingComps, 'radius_km' => 0.0, 'months' => (int) ($monthsSteps[0] ?? 12), 'expanded' => false, 'mode' => $hasGeo ? 'geo' : 'city'];
        $attempt = 0;

        foreach ($monthsSteps as $months) {
            foreach ($radii as $radiusKm) {
                $attempt++;
                $comps = $hasGeo
                    ? $this->querySoldByGeo($subject, $lat, $lng, (float) $radiusKm, (int) $months)
                    : $this->querySoldByCity($subject, (int) $months);

                $comps = $this->mergeCompsPreferBuilding($buildingComps, $comps);

                if (count($comps) > count($best['comps'])) {
                    $best = [
                        'comps' => $comps,
                        'radius_km' => (float) $radiusKm,
                        'months' => (int) $months,
                        'expanded' => $attempt > 1,
                        'mode' => $hasGeo ? 'geo' : 'city',
                    ];
                }

                // Enough nearby comps — stop before pulling distant neighbourhoods.
                if (count($comps) >= $goodLocal && (float) $radiusKm <= 1.25) {
                    return [
                        'comps' => $comps,
                        'radius_km' => (float) $radiusKm,
                        'months' => (int) $months,
                        'expanded' => $attempt > 1,
                        'mode' => $hasGeo ? 'geo' : 'city',
                    ];
                }

                if (count($comps) >= $targetPool && (float) $radiusKm <= 2.0) {
                    return [
                        'comps' => $comps,
                        'radius_km' => (float) $radiusKm,
                        'months' => (int) $months,
                        'expanded' => $attempt > 1,
                        'mode' => $hasGeo ? 'geo' : 'city',
                    ];
                }

                if (count($comps) >= $targetPool && (float) $radiusKm <= 3.5) {
                    return [
                        'comps' => $comps,
                        'radius_km' => (float) $radiusKm,
                        'months' => (int) $months,
                        'expanded' => $attempt > 1,
                        'mode' => $hasGeo ? 'geo' : 'city',
                    ];
                }

                if (! $hasGeo) {
                    break;
                }
            }
        }

        return $best;
    }

    /**
     * Same-building solds (0 km) — HouseSigma leads with these when available.
     *
     * @param  array<string, mixed>  $subject
     * @return list<array<string, mixed>>
     */
    private function querySameBuildingSoldComps(array $subject, float $lat, float $lng): array
    {
        $months = 24;
        $since = now()->subMonths($months)->startOfDay();
        $minPrice = (float) config('home_valuation.min_sold_price', 50000);
        $street = $this->extractStreetNumber((string) ($subject['address'] ?? ''));
        $unit = $this->extractUnitToken((string) ($subject['address'] ?? ''));

        $rows = DB::table('re_properties')
            ->select([
                'id', 'name', 'external_id', 'ClosePrice', 'close_date', 'price',
                'number_bedroom', 'number_bathroom', 'square', 'PropertySubType',
                'latitude', 'longitude', 'MlsStatus', 'TransactionType', 'listing_contract_date',
            ])
            ->where('moderation_status', 'approved')
            ->whereIn('MlsStatus', self::SOLD_STATUSES)
            ->where('TransactionType', 'For Sale')
            ->where('ClosePrice', '>=', $minPrice)
            ->whereNotNull('close_date')
            ->where('close_date', '>=', $since)
            ->whereBetween('latitude', [$lat - 0.00012, $lat + 0.00012])
            ->whereBetween('longitude', [$lng - 0.00012, $lng + 0.00012])
            ->when(! empty($subject['id']), fn ($q) => $q->where('id', '<>', (int) $subject['id']))
            ->orderByDesc('close_date')
            ->limit(40)
            ->get()
            ->all();

        // Keep other units in the same building (exclude the subject unit itself).
        $filtered = [];
        foreach ($rows as $row) {
            $name = (string) ($row->name ?? '');
            $rowStreet = $this->extractStreetNumber($name);
            $rowUnit = $this->extractUnitToken($name);
            if ($street !== null && $rowStreet !== null && $rowStreet !== $street) {
                continue;
            }
            if ($unit !== null && $rowUnit !== null && $rowUnit === $unit) {
                continue;
            }
            $filtered[] = $row;
        }

        $family = $this->filterRowsBySimilarity($filtered, $subject, false);

        return $this->scoreAndSelectComps($subject, $family, $lat, $lng, 0.35, true, false);
    }

    /**
     * @param  list<array<string, mixed>>  $building
     * @param  list<array<string, mixed>>  $nearby
     * @return list<array<string, mixed>>
     */
    private function mergeCompsPreferBuilding(array $building, array $nearby): array
    {
        $seen = [];
        $out = [];
        foreach (array_merge($building, $nearby) as $c) {
            $key = mb_strtolower((string) ($c['address'] ?? '')) . '|' . (int) ($c['sold_price'] ?? 0);
            if ($key === '|' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $c;
        }

        usort($out, static function (array $a, array $b): int {
            $da = $a['distance_km'] ?? 999.0;
            $db = $b['distance_km'] ?? 999.0;
            if ($da == $db) {
                return ($b['weight'] ?? 0) <=> ($a['weight'] ?? 0);
            }

            return $da <=> $db;
        });

        return array_slice($out, 0, (int) config('home_valuation.max_comps_valuation_pool', 36));
    }

    /**
     * @param  array<string, mixed>  $subject
     * @return list<array<string, mixed>>
     */
    private function querySoldByGeo(array $subject, float $lat, float $lng, float $radiusKm, int $months): array
    {
        $deltaLat = $radiusKm / 111.0;
        $cos = max(0.2, cos(deg2rad($lat)));
        $deltaLng = $radiusKm / (111.0 * $cos);
        $since = now()->subMonths($months)->startOfDay();
        $until = now()->endOfDay();
        $minPrice = (float) config('home_valuation.min_sold_price', 50000);
        $candidateLimit = (int) config('home_valuation.candidate_limit', 220);
        $minComps = (int) config('home_valuation.min_comps', 3);

        // Force map-bounds index — without it MySQL picks close_date and scans ~160k sold rows.
        $forceIndex = DB::connection()->getDriverName() === 'mysql'
            ? ' FORCE INDEX (idx_re_properties_map_bounds)'
            : '';
        $rows = DB::select(
            'SELECT id, name, external_id, ClosePrice, close_date, price,
                    number_bedroom, number_bathroom, square, PropertySubType,
                    latitude, longitude, MlsStatus, TransactionType, listing_contract_date
             FROM re_properties' . $forceIndex . '
             WHERE moderation_status = ?
               AND MlsStatus IN (' . implode(',', array_fill(0, count(self::SOLD_STATUSES), '?')) . ')
               AND TransactionType = ?
               AND ClosePrice >= ?
               AND close_date IS NOT NULL
               AND close_date BETWEEN ? AND ?
               AND latitude BETWEEN ? AND ?
               AND longitude BETWEEN ? AND ?
               AND latitude <> 0
               AND longitude <> 0
               AND id <> ?
               AND (external_id IS NULL OR external_id <> ?)
             ORDER BY close_date DESC
             LIMIT ' . (int) $candidateLimit,
            array_merge(
                ['approved'],
                self::SOLD_STATUSES,
                [
                    'For Sale',
                    $minPrice,
                    $since->toDateTimeString(),
                    $until->toDateTimeString(),
                    $lat - $deltaLat,
                    $lat + $deltaLat,
                    $lng - $deltaLng,
                    $lng + $deltaLng,
                    (int) ($subject['id'] ?? 0),
                    (string) ($subject['external_id'] ?? ''),
                ]
            )
        );

        // Beds/type family in PHP so MySQL keeps the map-bounds index path.
        // Never mix condo comps with detached/townhouse (main accuracy fix).
        $strictRows = $this->filterRowsBySimilarity($rows, $subject, true);
        $comps = $this->scoreAndSelectComps($subject, $strictRows, $lat, $lng, $radiusKm, true, true);
        if (count($comps) >= $minComps) {
            return $comps;
        }

        $familyRows = $this->filterRowsBySimilarity($rows, $subject, false);
        $comps = $this->scoreAndSelectComps($subject, $familyRows, $lat, $lng, $radiusKm, true, false);

        return $comps;
    }

    /**
     * @param  array<string, mixed>  $subject
     * @return list<array<string, mixed>>
     */
    private function querySoldByCity(array $subject, int $months): array
    {
        $location = TrebPropertyHelper::parseLocationPartsFromAddress(
            (string) ($subject['match_address'] ?? $subject['address'] ?? '')
        );
        $city = trim((string) ($location['City'] ?? $location['CityRegion'] ?? ''));
        if (preg_match('/^(London|Toronto|Ottawa|Hamilton|Barrie)\s+(North|South|East|West|Central)\b/i', $city, $m)) {
            $city = $m[1];
        }
        if ($city === '') {
            return [];
        }

        $since = now()->subMonths($months)->startOfDay();
        $until = now()->endOfDay();
        $minPrice = (float) config('home_valuation.min_sold_price', 50000);
        $candidateLimit = (int) config('home_valuation.candidate_limit', 220);

        // FULLTEXT only — `name LIKE %city%` is a 20–60s table scan (no B-tree on name).
        $cityExpr = PropertyFulltextSearch::requiredTokenExpression($city);
        if ($cityExpr === '') {
            return [];
        }

        $q = DB::table('re_properties')
            ->select([
                'id', 'name', 'external_id', 'ClosePrice', 'close_date', 'price',
                'number_bedroom', 'number_bathroom', 'square', 'PropertySubType',
                'latitude', 'longitude', 'MlsStatus', 'TransactionType', 'listing_contract_date',
            ])
            ->where('moderation_status', 'approved')
            ->whereIn('MlsStatus', self::SOLD_STATUSES)
            ->where('TransactionType', 'For Sale')
            ->where('ClosePrice', '>=', $minPrice)
            ->whereNotNull('close_date')
            ->whereBetween('close_date', [$since, $until])
            ->whereRaw('MATCH(`name`, `location`) AGAINST(? IN BOOLEAN MODE)', [$cityExpr])
            ->orderByDesc('close_date')
            ->limit($candidateLimit);

        if (! empty($subject['id'])) {
            $q->where('id', '<>', (int) $subject['id']);
        }

        $cityRows = $q->get()->all();
        $strictRows = $this->filterRowsBySimilarity($cityRows, $subject, true);
        $comps = $this->scoreAndSelectComps($subject, $strictRows, null, null, null, true, true);
        if (count($comps) < (int) config('home_valuation.min_comps', 3)) {
            $familyRows = $this->filterRowsBySimilarity($cityRows, $subject, false);
            $comps = $this->scoreAndSelectComps($subject, $familyRows, null, null, null, true, false);
        }

        return $comps;
    }

    /**
     * @param  array<string, mixed>  $subject
     * @return array{comps: list<array<string, mixed>>, radius_km: float, months: int, expanded: bool, mode: string}
     */
    private function findAskingComparables(array $subject, float $radiusKm, int $months): array
    {
        $lat = (float) ($subject['latitude'] ?? 0);
        $lng = (float) ($subject['longitude'] ?? 0);
        $hasGeo = abs($lat) > 0.01 && abs($lng) > 0.01;
        $minPrice = (float) config('home_valuation.min_sold_price', 50000);
        $candidateLimit = (int) config('home_valuation.candidate_limit', 220);

        if ($hasGeo) {
            $deltaLat = $radiusKm / 111.0;
            $cos = max(0.2, cos(deg2rad($lat)));
            $deltaLng = $radiusKm / (111.0 * $cos);
            $forceIndex = DB::connection()->getDriverName() === 'mysql'
                ? ' FORCE INDEX (idx_re_properties_map_bounds)'
                : '';
            $rows = DB::select(
                'SELECT id, name, external_id, ClosePrice, close_date, price,
                        number_bedroom, number_bathroom, square, PropertySubType,
                        latitude, longitude, MlsStatus, TransactionType
                 FROM re_properties' . $forceIndex . '
                 WHERE moderation_status = ?
                   AND MlsStatus IN (' . implode(',', array_fill(0, count(self::ACTIVE_STATUSES), '?')) . ')
                   AND TransactionType = ?
                   AND price >= ?
                   AND latitude BETWEEN ? AND ?
                   AND longitude BETWEEN ? AND ?
                   AND id <> ?
                 ORDER BY listing_contract_date DESC
                 LIMIT ' . (int) $candidateLimit,
                array_merge(
                    ['approved'],
                    self::ACTIVE_STATUSES,
                    [
                        'For Sale',
                        $minPrice,
                        $lat - $deltaLat,
                        $lat + $deltaLat,
                        $lng - $deltaLng,
                        $lng + $deltaLng,
                        (int) ($subject['id'] ?? 0),
                    ]
                )
            );
            $rows = $this->filterRowsBySimilarity($rows, $subject, false);
            $comps = $this->scoreAndSelectComps($subject, $rows, $lat, $lng, $radiusKm, false, false);
        } else {
            $location = TrebPropertyHelper::parseLocationPartsFromAddress(
                (string) ($subject['match_address'] ?? $subject['address'] ?? '')
            );
            $city = trim((string) ($location['City'] ?? $location['CityRegion'] ?? ''));
            if (preg_match('/^(London|Toronto|Ottawa|Hamilton|Barrie)\s+(North|South|East|West|Central)\b/i', $city, $m)) {
                $city = $m[1];
            }
            if ($city === '') {
                return ['comps' => [], 'radius_km' => $radiusKm, 'months' => $months, 'expanded' => true, 'mode' => 'asking'];
            }

            $cityExpr = PropertyFulltextSearch::requiredTokenExpression($city);
            if ($cityExpr === '') {
                return ['comps' => [], 'radius_km' => $radiusKm, 'months' => $months, 'expanded' => true, 'mode' => 'asking'];
            }

            $q = DB::table('re_properties')
                ->select([
                    'id', 'name', 'external_id', 'ClosePrice', 'close_date', 'price',
                    'number_bedroom', 'number_bathroom', 'square', 'PropertySubType',
                    'latitude', 'longitude', 'MlsStatus', 'TransactionType',
                ])
                ->where('moderation_status', 'approved')
                ->whereIn('MlsStatus', self::ACTIVE_STATUSES)
                ->where('TransactionType', 'For Sale')
                ->where('price', '>=', $minPrice)
                ->whereRaw('MATCH(`name`, `location`) AGAINST(? IN BOOLEAN MODE)', [$cityExpr])
                ->orderByDesc('listing_contract_date')
                ->limit($candidateLimit);

            $this->applySimilarityFilters($q, $subject, false);
            if (! empty($subject['id'])) {
                $q->where('id', '<>', (int) $subject['id']);
            }

            $comps = $this->scoreAndSelectComps($subject, $q->get()->all(), null, null, null, false, false);
        }

        return [
            'comps' => $comps,
            'radius_km' => $radiusKm,
            'months' => $months,
            'expanded' => true,
            'mode' => 'asking',
        ];
    }

    /**
     * Nearby active For Sale listings for estimate UI property cards.
     *
     * @param  array<string, mixed>  $subject
     * @return list<array<string, mixed>>
     */
    private function nearbyForSaleCards(array $subject, float $radiusKm = 3.5, int $limit = 8): array
    {
        $lat = (float) ($subject['latitude'] ?? 0);
        $lng = (float) ($subject['longitude'] ?? 0);
        if (abs($lat) < 0.01 || abs($lng) < 0.01) {
            return [];
        }

        $limit = max(1, min(12, $limit));
        $minPrice = (float) config('home_valuation.min_sold_price', 50000);
        $deltaLat = $radiusKm / 111.0;
        $cos = max(0.2, cos(deg2rad($lat)));
        $deltaLng = $radiusKm / (111.0 * $cos);
        $forceIndex = DB::connection()->getDriverName() === 'mysql'
            ? ' FORCE INDEX (idx_re_properties_map_bounds)'
            : '';

        $rows = DB::select(
            'SELECT id, name, external_id, price, number_bedroom, number_bathroom, square,
                    PropertySubType, latitude, longitude, MlsStatus, image_val, listing_contract_date
             FROM re_properties' . $forceIndex . '
             WHERE moderation_status = ?
               AND MlsStatus IN (' . implode(',', array_fill(0, count(self::ACTIVE_STATUSES), '?')) . ')
               AND TransactionType = ?
               AND price >= ?
               AND latitude BETWEEN ? AND ?
               AND longitude BETWEEN ? AND ?
               AND id <> ?
             ORDER BY listing_contract_date DESC
             LIMIT 120',
            array_merge(
                ['approved'],
                self::ACTIVE_STATUSES,
                [
                    'For Sale',
                    $minPrice,
                    $lat - $deltaLat,
                    $lat + $deltaLat,
                    $lng - $deltaLng,
                    $lng + $deltaLng,
                    (int) ($subject['id'] ?? 0),
                ]
            )
        );

        $rows = $this->filterRowsBySimilarity($rows, $subject, false);
        $scored = [];
        foreach ($rows as $row) {
            $cLat = (float) ($row->latitude ?? 0);
            $cLng = (float) ($row->longitude ?? 0);
            if (abs($cLat) < 0.01 || abs($cLng) < 0.01) {
                continue;
            }
            $distance = $this->haversineKm($lat, $lng, $cLat, $cLng);
            if ($distance > $radiusKm) {
                continue;
            }
            $price = (float) ($row->price ?? 0);
            if ($price < $minPrice) {
                continue;
            }
            $scored[] = [
                'row' => $row,
                'distance_km' => round($distance, 2),
                'price' => $price,
            ];
        }

        usort($scored, static fn ($a, $b) => ($a['distance_km'] <=> $b['distance_km']));
        $scored = array_slice($scored, 0, $limit);
        if ($scored === []) {
            return [];
        }

        $ids = array_map(static fn ($s) => (int) ($s['row']->id ?? 0), $scored);
        $slugMap = DB::table('slugs')
            ->where('reference_type', Property::class)
            ->whereIn('reference_id', $ids)
            ->pluck('key', 'reference_id');

        $cards = [];
        foreach ($scored as $item) {
            $row = $item['row'];
            $id = (int) ($row->id ?? 0);
            $listingKey = strtoupper(trim((string) ($row->external_id ?? '')));
            $slug = trim((string) ($slugMap[$id] ?? ''));
            if ($slug === '') {
                $slug = Str::slug((string) ($row->name ?? 'property')) . '-' . strtolower($listingKey !== '' ? $listingKey : (string) $id);
            }
            $sqft = $this->valuationSqft($row->square ?? null);
            $cards[] = [
                'id' => $id,
                'mls' => $listingKey,
                'address' => (string) ($row->name ?? ''),
                'price' => (int) round($item['price']),
                'beds' => (int) ($row->number_bedroom ?? 0),
                'baths' => (int) ($row->number_bathroom ?? 0),
                'sqft' => $sqft > 0 ? (int) round($sqft) : null,
                'type' => trim((string) ($row->PropertySubType ?? '')),
                'status' => trim((string) ($row->MlsStatus ?? 'For Sale')),
                'distance_km' => $item['distance_km'],
                'url' => '/properties/' . ltrim($slug, '/'),
                'image' => SerikMediaUrl::mapListingCover($listingKey, (string) ($row->image_val ?? '')),
            ];
        }

        return $cards;
    }

    /**
     * @param  list<object>  $rows
     * @return list<object>
     */
    private function filterRowsBySimilarity(array $rows, array $subject, bool $strictType): array
    {
        $beds = (int) ($subject['beds'] ?? 0);
        $tol = (int) config('home_valuation.bed_tolerance', 1);
        $type = mb_strtolower(trim((string) ($subject['type'] ?? '')));
        $family = $this->typeFamily($type);

        $out = [];
        foreach ($rows as $row) {
            if ($beds > 0) {
                $rowBeds = (int) ($row->number_bedroom ?? 0);
                if ($rowBeds < max(0, $beds - $tol) || $rowBeds > $beds + $tol) {
                    continue;
                }
            }

            $rowType = mb_strtolower(trim((string) ($row->PropertySubType ?? '')));
            $rowFamily = $this->typeFamily($rowType);

            if ($type !== '') {
                if ($strictType) {
                    $exact = $rowType === $type
                        || str_starts_with($rowType, $type)
                        || str_starts_with($type, $rowType);
                    $sameFamily = $family !== 'other' && $rowFamily === $family;
                    if (! $exact && ! $sameFamily) {
                        continue;
                    }
                } elseif ($family !== 'other' && $rowFamily !== $family) {
                    // Loose path: still require same property class (condo ≠ detached/other).
                    continue;
                }
            }

            $out[] = $row;
        }

        return $out;
    }

    private function typeFamily(string $type): string
    {
        $t = mb_strtolower(trim($type));
        if ($t === '') {
            return 'other';
        }
        if (str_contains($t, 'condo') || str_contains($t, 'co-op') || str_contains($t, 'cooperative')
            || str_contains($t, 'apartment') || str_contains($t, 'comm element')) {
            return 'condo';
        }
        if (str_contains($t, 'town') || str_contains($t, 'row') || str_contains($t, 'att/')) {
            return 'townhouse';
        }
        if (str_contains($t, 'semi')) {
            return 'semi';
        }
        if (str_contains($t, 'detach')) {
            return 'detached';
        }

        return 'other';
    }

    private function applySimilarityFilters($query, array $subject, bool $strictType = true): void
    {
        $beds = (int) ($subject['beds'] ?? 0);
        $tol = (int) config('home_valuation.bed_tolerance', 1);
        if ($beds > 0) {
            $query->whereBetween('number_bedroom', [max(0, $beds - $tol), $beds + $tol]);
        }

        $type = trim((string) ($subject['type'] ?? ''));
        if ($strictType && $type !== '') {
            $needle = mb_strtolower($type);
            $query->where(function ($inner) use ($needle) {
                $inner->whereRaw('LOWER(COALESCE(`PropertySubType`, "")) LIKE ?', [$needle . '%'])
                    ->orWhereRaw('LOWER(COALESCE(`PropertySubType`, "")) = ?', [$needle]);
            });
        }
    }

    /**
     * @param  list<object>  $rows
     * @return list<array<string, mixed>>
     */
    private function scoreAndSelectComps(
        array $subject,
        array $rows,
        ?float $lat,
        ?float $lng,
        ?float $radiusKm,
        bool $sold,
        bool $enforceSqft = true
    ): array {
        $subjectSqft = (float) ($subject['sqft'] ?? 0);
        $sqftTol = (float) (
            $subjectSqft > 0 && $subjectSqft < 800
                ? config('home_valuation.sqft_tolerance_ratio_small', 0.22)
                : config('home_valuation.sqft_tolerance_ratio', 0.30)
        );
        $maxUsed = (int) config('home_valuation.max_comps_valuation_pool', 36);
        $subjectStreet = $this->extractStreetNumber((string) ($subject['address'] ?? ''));
        $seen = [];
        $scored = [];

        foreach ($rows as $row) {
            $price = $sold ? (float) ($row->ClosePrice ?? 0) : (float) ($row->price ?? 0);
            if ($price < (float) config('home_valuation.min_sold_price', 50000)) {
                continue;
            }

            $address = (string) ($row->name ?? '');
            $dedupe = mb_strtolower($address) . '|' . (int) round($price);
            if (isset($seen[$dedupe])) {
                continue;
            }
            $seen[$dedupe] = true;

            $sqft = $this->valuationSqft($row->square ?? null);
            if ($subjectSqft > 0 && $sqft > 0) {
                $ratio = abs($sqft - $subjectSqft) / $subjectSqft;
                $allowed = $enforceSqft ? $sqftTol : max($sqftTol, 0.45);
                if ($ratio > $allowed) {
                    continue;
                }
            }

            $distance = null;
            if ($lat !== null && $lng !== null) {
                $cLat = (float) ($row->latitude ?? 0);
                $cLng = (float) ($row->longitude ?? 0);
                if (abs($cLat) < 0.01 || abs($cLng) < 0.01) {
                    continue;
                }
                $distance = $this->haversineKm($lat, $lng, $cLat, $cLng);
                if ($radiusKm !== null && $distance > $radiusKm) {
                    continue;
                }
            }

            $days = null;
            if ($sold && ! empty($row->close_date)) {
                try {
                    $days = (int) abs(now()->diffInDays(\Carbon\Carbon::parse((string) $row->close_date)));
                } catch (Throwable) {
                    $days = null;
                }
            }

            $dom = $this->daysOnMarketFromRow($row);

            $sizeScore = 1.0;
            if ($subjectSqft > 0 && $sqft > 0) {
                $sizeScore = max(0.15, 1.0 - (abs($sqft - $subjectSqft) / $subjectSqft));
            }
            $distScore = 1.0;
            if ($distance !== null && $radiusKm) {
                $distScore = max(0.15, 1.0 - ($distance / max(0.5, $radiusKm)));
            }
            $recencyScore = 1.0;
            if ($days !== null) {
                $recencyScore = max(0.2, 1.0 - ($days / 730.0));
            }
            $sameBuilding = $subjectStreet !== null
                && $this->extractStreetNumber($address) === $subjectStreet;
            $buildingBoost = $sameBuilding ? 0.12 : 0.0;

            // Distance dominates so Beaches/local comps beat downtown cherry-picks.
            $weight = round(($distScore * 0.45) + ($sizeScore * 0.28) + ($recencyScore * 0.15) + $buildingBoost, 4);
            $ppsf = ($sqft > 0) ? ($price / $sqft) : null;

            $scored[] = [
                'address' => $address,
                'sold_price' => $sold ? (int) round($price) : null,
                'asking_price' => $sold ? null : (int) round($price),
                'sold_date' => $sold && ! empty($row->close_date)
                    ? \Carbon\Carbon::parse((string) $row->close_date)->toDateString()
                    : null,
                'distance_km' => $distance !== null ? round($distance, 2) : null,
                'beds' => (int) ($row->number_bedroom ?? 0),
                'baths' => (int) ($row->number_bathroom ?? 0),
                'sqft' => $sqft > 0 ? (int) round($sqft) : null,
                'type' => trim((string) ($row->PropertySubType ?? '')),
                'ppsf' => $ppsf !== null ? (int) round($ppsf) : null,
                'days_on_market' => $dom,
                'weight' => $weight,
                'mls' => $this->maskMls((string) ($row->external_id ?? '')),
                '_price' => $price,
                '_ppsf' => $ppsf,
                '_weight' => $weight,
            ];
        }

        usort($scored, static fn ($a, $b) => ($b['_weight'] <=> $a['_weight']));
        $scored = $this->rejectOutliers($scored);
        $scored = array_slice($scored, 0, $maxUsed);

        return array_map(static function (array $c): array {
            unset($c['_price'], $c['_ppsf'], $c['_weight']);

            return $c;
        }, $scored);
    }

    /**
     * @param  list<array<string, mixed>>  $comps
     * @return list<array<string, mixed>>
     */
    private function rejectOutliers(array $comps): array
    {
        if (count($comps) < 5) {
            return $comps;
        }

        // Prefer PPSF outliers when available — stops large homes from widening condo ranges.
        $usePpsf = count(array_filter($comps, static fn ($c) => ($c['_ppsf'] ?? null) > 0)) >= 4;
        $values = array_map(
            static fn ($c) => (float) ($usePpsf ? ($c['_ppsf'] ?? 0) : ($c['_price'] ?? 0)),
            $comps
        );
        $values = array_values(array_filter($values, static fn ($v) => $v > 0));
        if (count($values) < 5) {
            return $comps;
        }
        sort($values);
        $n = count($values);
        $q1 = $values[(int) floor(($n - 1) * 0.25)];
        $q3 = $values[(int) floor(($n - 1) * 0.75)];
        $iqr = max(1.0, $q3 - $q1);
        $low = $q1 - (1.5 * $iqr);
        $high = $q3 + (1.5 * $iqr);

        $filtered = array_values(array_filter($comps, static function (array $c) use ($usePpsf, $low, $high): bool {
            $v = (float) ($usePpsf ? ($c['_ppsf'] ?? 0) : ($c['_price'] ?? 0));

            return $v >= $low && $v <= $high;
        }));

        return count($filtered) >= 3 ? $filtered : $comps;
    }

    /**
     * @param  array<string, mixed>  $subject
     * @param  array{comps: list<array<string, mixed>>, radius_km: float, months: int, expanded: bool, mode: string}  $search
     * @return array<string, mixed>
     */
    private function calculateFromSoldComps(array $subject, array $search): array
    {
        $pool = $search['comps'];
        $subjectSqft = (float) ($subject['sqft'] ?? 0);
        $displayLimit = (int) config('home_valuation.max_comps_used', 12);

        // Distance-weighted implied subject values. PPSF scaling is dampened when
        // comp size differs a lot — small townhouses often have higher $/sqft and
        // blindly scaling them to a larger subject overstates value vs HouseSigma.
        $implied = [];
        $weighted = [];
        foreach ($pool as $c) {
            $price = (float) ($c['sold_price'] ?? 0);
            if ($price <= 0) {
                continue;
            }
            $compSqft = (float) ($c['sqft'] ?? 0);
            $value = $this->impliedSubjectValue($price, $compSqft, $subjectSqft);
            $implied[] = $value;

            $baseW = max(0.05, (float) ($c['weight'] ?? 0.4));
            $dist = (float) ($c['distance_km'] ?? 4.0);
            $distBoost = max(0.12, 1.0 / (1.0 + ($dist * 3.4)));
            $compSqftForW = (float) ($c['sqft'] ?? 0);
            $sizeBoost = 1.0;
            if ($subjectSqft > 0 && $compSqftForW > 0) {
                $sizeGap = abs($compSqftForW - $subjectSqft) / $subjectSqft;
                $sizeBoost = max(0.35, 1.0 - ($sizeGap * 1.1));
            }
            $weighted[] = ['v' => $value, 'w' => $baseW * $distBoost * $sizeBoost];
        }
        sort($implied);

        if (count($weighted) >= 5) {
            // Soft-market lean (HouseSigma mid tracks below raw median).
            $p20 = $this->weightedPercentile($weighted, 0.20);
            $p45 = $this->weightedPercentile($weighted, 0.45);
            $estimate = ($p20 * 0.78) + ($p45 * 0.22);
            $method = 'Sold-comparable AVM using a distance/size-weighted lower-quartile blend of adjusted sold values.';
        } elseif (count($weighted) >= 3) {
            $estimate = $this->weightedPercentile($weighted, 0.32);
            $method = 'Distance-weighted sold value from a small comparable set (size-adjusted).';
        } else {
            $estimate = $implied !== [] ? $this->percentile($implied, 0.5) : 0.0;
            $method = 'Median of verified sold prices (area incomplete for PPSF).';
        }

        // Recent MLS close on THIS address is the strongest signal (HouseSigma anchors here).
        $anchor = $this->recentSubjectSaleAnchor($subject);
        if ($anchor !== null && $estimate > 0) {
            $aw = (float) $anchor['weight'];
            $estimate = ((float) $anchor['price'] * $aw) + ($estimate * (1.0 - $aw));
            $implied[] = (float) $anchor['price'];
            sort($implied);
            $method .= ' Recent MLS close price for this address is used as a primary market anchor.';
        } elseif ($estimate > 0) {
            // Active listing: soft-anchor to current list price (HS does this when no sale).
            $listAnchor = $this->activeListPriceSoftAnchor($subject);
            if ($listAnchor !== null) {
                $lw = (float) $listAnchor['weight'];
                $estimate = ((float) $listAnchor['price'] * $lw) + ($estimate * (1.0 - $lw));
                $implied[] = (float) $listAnchor['price'];
                sort($implied);
                $method .= ' Current list price soft-anchors the mid estimate.';
                $anchor = $listAnchor;
            }
        }

        $estimate = $this->roundCad($estimate);
        [$low, $high] = $this->rangeFromImplied($implied, $estimate);

        // Keep the band realistic around a fresh subject sale / list anchor.
        if ($anchor !== null) {
            $ap = (float) $anchor['price'];
            $isListOnly = (($anchor['source'] ?? '') === 'list');
            $low = min($low, $this->roundCad($ap * ($isListOnly ? 0.92 : 0.90)));
            $low = max($low, $this->roundCad(min($estimate, $ap) * ($isListOnly ? 0.88 : 0.78)));
            $high = max($high, $this->roundCad($ap * ($isListOnly ? 1.12 : 1.06)));
            $high = min($high, $this->roundCad(max($estimate, $ap) * ($isListOnly ? 1.18 : 1.22)));
            if ($low > $estimate) {
                $low = $this->roundCad($estimate * 0.92);
            }
            if ($high < $estimate) {
                $high = $this->roundCad($estimate * 1.08);
            }
        }

        $confidence = $this->confidence(count($pool), $search, true);
        if ($anchor !== null && ($anchor['months'] ?? 99) <= 12) {
            $confidence['level'] = $confidence['level'] === 'low' ? 'medium' : $confidence['level'];
            $confidence['reason'] = 'Recent sold price on this address anchors the estimate. ' . $confidence['reason'];
        }
        $displayComps = array_slice($pool, 0, $displayLimit);

        return [
            'ok' => true,
            'status' => 'valued',
            'basis' => 'sold_comparables',
            'estimate' => $estimate,
            'low' => $low,
            'high' => $high,
            'currency' => 'CAD',
            'confidence' => $confidence['level'],
            'confidence_reason' => $confidence['reason'],
            'comps_count' => count($pool),
            'search' => [
                'radius_km' => $search['radius_km'] ?? null,
                'months' => $search['months'] ?? null,
                'expanded' => (bool) ($search['expanded'] ?? false),
                'mode' => $search['mode'] ?? null,
            ],
            'subject' => $this->publicSubject($subject),
            'comparables' => $displayComps,
            'methodology' => $method . ' Only same property-class MLS sold statuses with ClosePrice ≥ $50,000 and close dates within the search window are used. Asking prices may widen the high end only.',
            'evaluated_at' => now()->toIso8601String(),
            'disclaimer' => (string) config('home_valuation.disclaimer'),
            'city' => data_get(TrebPropertyHelper::parseLocationPartsFromAddress((string) ($subject['address'] ?? '')), 'City'),
            'address' => $subject['address'] ?? null,
            'subject_sale_anchor' => $anchor,
        ];
    }

    /**
     * Map a sold comparable to an implied subject value.
     * Full PPSF scale only when sizes are close; otherwise blend toward the raw sold price.
     */
    private function impliedSubjectValue(float $soldPrice, float $compSqft, float $subjectSqft): float
    {
        if ($soldPrice <= 0) {
            return 0.0;
        }
        if ($subjectSqft <= 0 || $compSqft <= 0) {
            return $soldPrice;
        }

        $ppsfImplied = ($soldPrice / $compSqft) * $subjectSqft;
        $ratio = $compSqft / $subjectSqft;
        if ($ratio >= 0.90 && $ratio <= 1.10) {
            return $ppsfImplied;
        }

        // Small homes often trade at higher $/sqft — do not fully scale that up.
        $gap = abs(1.0 - $ratio);
        $ppsfTrust = max(0.10, min(0.70, 1.0 - ($gap * 2.8)));

        return ($soldPrice * (1.0 - $ppsfTrust)) + ($ppsfImplied * $ppsfTrust);
    }

    /**
     * Soft-anchor active listings to their current ask (HouseSigma-style when no sale).
     *
     * @return array{price: float, weight: float, months: float, date: string, source: string}|null
     */
    private function activeListPriceSoftAnchor(array $subject): ?array
    {
        $list = (float) ($subject['list_price'] ?? 0);
        if ($list < 50000) {
            return null;
        }

        $status = mb_strtolower((string) ($subject['mls_status'] ?? ''));
        $activeHints = ['new', 'active', 'price change', 'extension', 'ext', 'previous status', 'active under contract'];
        $isActive = false;
        foreach ($activeHints as $hint) {
            if ($status !== '' && str_contains($status, $hint)) {
                $isActive = true;
                break;
            }
        }
        if (! $isActive && $status !== '') {
            return null;
        }

        return [
            'price' => $list,
            'weight' => 0.42,
            'months' => 0.0,
            'date' => now()->toDateString(),
            'source' => 'list',
        ];
    }

    /**
     * @return array{price: float, weight: float, months: float, date: string, source?: string}|null
     */
    private function recentSubjectSaleAnchor(array $subject): ?array
    {
        $price = (float) ($subject['close_price'] ?? 0);
        $dateRaw = $subject['close_date'] ?? null;
        if ($price < 50000 || $dateRaw === null || $dateRaw === '') {
            return null;
        }

        $status = mb_strtolower((string) ($subject['mls_status'] ?? ''));
        $tx = mb_strtolower((string) ($subject['transaction_type'] ?? ''));
        if ($tx !== '' && str_contains($tx, 'lease')) {
            return null;
        }
        if ($status !== '' && ! str_contains($status, 'sold') && ! str_contains($status, 'closed')) {
            // Active listing row may still carry a prior ClosePrice — allow if dated.
        }

        try {
            $closed = \Carbon\Carbon::parse((string) $dateRaw);
        } catch (Throwable) {
            return null;
        }

        if ($closed->isFuture()) {
            return null;
        }

        $months = abs($closed->diffInDays(now())) / 30.4375;
        if ($months > 24) {
            return null;
        }

        if ($months <= 6) {
            $weight = 0.88;
        } elseif ($months <= 12) {
            $weight = 0.72;
        } elseif ($months <= 18) {
            $weight = 0.55;
        } else {
            $weight = 0.38;
        }

        return [
            'price' => $price,
            'weight' => $weight,
            'months' => round($months, 1),
            'date' => $closed->toDateString(),
            'source' => 'sold',
        ];
    }

    /**
     * @param  list<array{v: float, w: float}>  $items
     */
    private function weightedPercentile(array $items, float $p): float
    {
        $items = array_values(array_filter(
            $items,
            static fn ($i) => ($i['v'] ?? 0) > 0 && ($i['w'] ?? 0) > 0
        ));
        if ($items === []) {
            return 0.0;
        }
        usort($items, static fn ($a, $b) => $a['v'] <=> $b['v']);
        $total = array_sum(array_column($items, 'w'));
        if ($total <= 0) {
            return (float) $items[(int) floor((count($items) - 1) * max(0.0, min(1.0, $p)))]['v'];
        }

        $target = max(0.0, min(1.0, $p)) * $total;
        $cum = 0.0;
        foreach ($items as $item) {
            $cum += (float) $item['w'];
            if ($cum >= $target) {
                return (float) $item['v'];
            }
        }

        return (float) $items[array_key_last($items)]['v'];
    }

    /**
     * @param  array<string, mixed>  $subject
     * @param  array{comps: list<array<string, mixed>>, radius_km: float, months: int, expanded: bool, mode: string}  $search
     * @return array<string, mixed>
     */
    private function calculateFromAskingComps(array $subject, array $search): array
    {
        $comps = $search['comps'];
        $weights = [];
        foreach ($comps as $c) {
            $price = (float) ($c['asking_price'] ?? 0);
            if ($price > 0) {
                $weights[] = ['v' => $price, 'w' => (float) ($c['weight'] ?? 0.5)];
            }
        }
        $estimate = $this->roundCad($this->weightedMedian($weights));
        [$low, $high] = $this->rangeFromComps($comps, $estimate, false, (float) ($subject['sqft'] ?? 0));
        $confidence = $this->confidence(count($comps), $search, false);

        return [
            'ok' => true,
            'status' => 'valued',
            'basis' => 'asking_price_indication',
            'estimate' => $estimate,
            'low' => $low,
            'high' => $high,
            'currency' => 'CAD',
            'confidence' => 'low',
            'confidence_reason' => 'Based on active asking prices only — not verified sold prices. ' . $confidence['reason'],
            'comps_count' => count($comps),
            'search' => [
                'radius_km' => $search['radius_km'] ?? null,
                'months' => $search['months'] ?? null,
                'expanded' => true,
                'mode' => 'asking',
            ],
            'subject' => $this->publicSubject($subject),
            'comparables' => $comps,
            'methodology' => 'Insufficient sold comparables. Market indication uses the weighted median of nearby active For Sale asking prices. Asking prices are not confirmed sold prices.',
            'evaluated_at' => now()->toIso8601String(),
            'disclaimer' => (string) config('home_valuation.disclaimer'),
            'city' => data_get(TrebPropertyHelper::parseLocationPartsFromAddress((string) ($subject['address'] ?? '')), 'City'),
            'address' => $subject['address'] ?? null,
        ];
    }

    /**
     * @param  list<array{v: float, w: float}>  $items
     */
    private function weightedMedian(array $items): float
    {
        $items = array_values(array_filter($items, static fn ($i) => $i['v'] > 0 && $i['w'] > 0));
        if ($items === []) {
            return 0.0;
        }
        usort($items, static fn ($a, $b) => $a['v'] <=> $b['v']);
        $total = array_sum(array_column($items, 'w'));
        $half = $total / 2;
        $cum = 0.0;
        foreach ($items as $item) {
            $cum += $item['w'];
            if ($cum >= $half) {
                return (float) $item['v'];
            }
        }

        return (float) $items[array_key_last($items)]['v'];
    }

    /**
     * @param  list<array<string, mixed>>  $comps
     * @return array{0: int, 1: int}
     */
    private function rangeFromComps(array $comps, float $estimate, bool $sold, float $subjectSqft = 0): array
    {
        $prices = [];
        foreach ($comps as $c) {
            if ($sold && $subjectSqft > 0 && ! empty($c['ppsf'])) {
                $prices[] = (float) $c['ppsf'] * $subjectSqft;
                continue;
            }
            $p = $sold ? (float) ($c['sold_price'] ?? 0) : (float) ($c['asking_price'] ?? 0);
            if ($p > 0) {
                $prices[] = $p;
            }
        }
        sort($prices);

        return $this->rangeFromImplied($prices, $estimate);
    }

    /**
     * @param  array<string, mixed>  $valuation
     * @param  array<string, mixed>  $subject
     * @param  array{comps?: list<array<string, mixed>>}|null  $asking
     * @return array<string, mixed>
     */
    private function widenHighWithAsking(array $valuation, array $subject, ?array $asking): array
    {
        $comps = $asking['comps'] ?? [];
        if ($comps === []) {
            return $valuation;
        }

        $subjectSqft = (float) ($subject['sqft'] ?? 0);
        $subjectStreet = $this->extractStreetNumber((string) ($subject['address'] ?? ''));
        $askImplied = [];
        $sameBuildingAsks = [];
        foreach ($comps as $c) {
            $ap = (float) ($c['asking_price'] ?? 0);
            if ($ap < 50000) {
                continue;
            }
            if ($subjectSqft > 0 && ! empty($c['ppsf']) && (float) $c['ppsf'] > 0) {
                $askImplied[] = (float) $c['ppsf'] * $subjectSqft;
            } else {
                $askImplied[] = $ap;
            }
            if ($subjectStreet !== null
                && $this->extractStreetNumber((string) ($c['address'] ?? '')) === $subjectStreet) {
                $sameBuildingAsks[] = $ap;
            }
        }

        $candidates = [];
        if (count($askImplied) >= 3) {
            sort($askImplied);
            $candidates[] = $this->percentile($askImplied, 0.85);
        }
        if ($sameBuildingAsks !== []) {
            sort($sameBuildingAsks);
            // Same-building list prices often anchor the high end of the range.
            $candidates[] = $this->percentile($sameBuildingAsks, 0.5) * 0.88;
            $candidates[] = max($sameBuildingAsks) * 0.85;
        }
        if ($candidates === []) {
            return $valuation;
        }

        $askHigh = $this->roundCad(max($candidates));
        $currentHigh = (float) ($valuation['high'] ?? 0);
        $estimate = (float) ($valuation['estimate'] ?? 0);
        // Asking can widen high, but not into fantasy territory when solds already priced the mid.
        if ($estimate > 0) {
            $askHigh = min($askHigh, $this->roundCad($estimate * 1.22));
        }
        if ($askHigh > $currentHigh) {
            $valuation['high'] = $askHigh;
        }

        return $valuation;
    }

    /**
     * @param  list<float>  $implied
     * @return array{0: int, 1: int}
     */
    private function rangeFromImplied(array $implied, float $estimate): array
    {
        $n = count($implied);
        if ($n >= 5) {
            $low = min($estimate, $this->percentile($implied, 0.15));
            $high = max($estimate, $this->percentile($implied, 0.82));
        } elseif ($n > 0) {
            $low = min($estimate, $this->percentile($implied, 0.15));
            $high = max($estimate, $this->percentile($implied, 0.85));
        } else {
            $low = $estimate * 0.9;
            $high = $estimate * 1.1;
        }

        $low = min($low, $estimate * 0.94);
        $high = max($high, $estimate * 1.06);
        // Cap fantasy highs from tiny high-PPSF comps.
        $high = min($high, $estimate * 1.22);

        return [$this->roundCad($low), $this->roundCad($high)];
    }

    /**
     * @param  list<float>  $sorted
     */
    private function percentile(array $sorted, float $p): float
    {
        if ($sorted === []) {
            return 0.0;
        }
        sort($sorted);
        $n = count($sorted);
        if ($n === 1) {
            return (float) $sorted[0];
        }
        $idx = max(0.0, min(1.0, $p)) * ($n - 1);
        $lo = (int) floor($idx);
        $hi = (int) ceil($idx);
        if ($lo === $hi) {
            return (float) $sorted[$lo];
        }
        $w = $idx - $lo;

        return (float) ($sorted[$lo] * (1 - $w) + $sorted[$hi] * $w);
    }

    /**
     * @param  array{radius_km?: float|null, months?: int|null, expanded?: bool, mode?: string|null}  $search
     * @return array{level: string, reason: string}
     */
    private function confidence(int $comps, array $search, bool $sold): array
    {
        if (! $sold) {
            return [
                'level' => 'low',
                'reason' => 'Asking-price sample only; sold evidence insufficient.',
            ];
        }

        $radius = (float) ($search['radius_km'] ?? 99);
        $months = (int) ($search['months'] ?? 99);
        $expanded = (bool) ($search['expanded'] ?? false);

        if ($comps >= 8 && $radius <= 2.0 && $months <= 12 && ! $expanded) {
            return [
                'level' => 'high',
                'reason' => sprintf('%d sold comps within %.1f km and %d months without search expansion.', $comps, $radius, $months),
            ];
        }
        if ($comps >= 4 && $radius <= 5.0 && $months <= 18) {
            return [
                'level' => 'medium',
                'reason' => sprintf('%d sold comps within %.1f km over %d months%s.', $comps, $radius, $months, $expanded ? ' (search expanded)' : ''),
            ];
        }

        return [
            'level' => 'low',
            'reason' => sprintf('Limited sold evidence (%d comps; radius %.1f km; %d months%s).', $comps, $radius, $months, $expanded ? '; expanded' : ''),
        ];
    }

    private function roundCad(float $value): int
    {
        if ($value <= 0) {
            return 0;
        }
        if ($value >= 1_000_000) {
            return (int) (round($value / 5000) * 5000);
        }
        if ($value >= 100_000) {
            return (int) (round($value / 1000) * 1000);
        }

        return (int) (round($value / 500) * 500);
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }

    private function maskMls(string $externalId): ?string
    {
        $externalId = trim($externalId);
        if ($externalId === '') {
            return null;
        }
        if (mb_strlen($externalId) <= 4) {
            return $externalId;
        }

        return mb_substr($externalId, 0, 2) . str_repeat('*', max(2, mb_strlen($externalId) - 4)) . mb_substr($externalId, -2);
    }

    /**
     * @param  array<string, mixed>  $subject
     * @return array<string, mixed>
     */
    private function publicSubject(array $subject): array
    {
        return [
            'address' => $subject['address'] ?? null,
            'beds' => (int) ($subject['beds'] ?? 0) ?: null,
            'baths' => (int) ($subject['baths'] ?? 0) ?: null,
            'sqft' => ((float) ($subject['sqft'] ?? 0)) > 0 ? (int) round((float) $subject['sqft']) : null,
            'type' => $subject['type'] ?? null,
            'latitude' => ((float) ($subject['latitude'] ?? 0)) != 0.0 ? round((float) $subject['latitude'], 5) : null,
            'longitude' => ((float) ($subject['longitude'] ?? 0)) != 0.0 ? round((float) $subject['longitude'], 5) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fail(string $message, string $status): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'evaluated_at' => now()->toIso8601String(),
            'disclaimer' => (string) config('home_valuation.disclaimer'),
        ];
    }

    private function addressSpecifiesUnit(string $address): bool
    {
        $address = trim($address);
        if ($address === '') {
            return false;
        }

        // Patterns: "320 - 1050 Eastern…", "#320 - 1050…", "Unit 320 - 1050…", "Apt 320, 1050…"
        return (bool) preg_match(
            '/(?:^|\b)(?:unit|apt|suite|ph)?\s*#?\s*\d+[a-z]?\s*[-–,]\s*\d+/i',
            $address
        ) || (bool) preg_match('/^\s*#\s*\d+[a-z]?\b/i', $address);
    }

    private function extractUnitToken(string $address): ?string
    {
        $address = trim($address);
        // "320 - 1050 Eastern…"
        if (preg_match('/^\s*#?\s*(\d+[a-z]?)\s*[-–]\s*\d+/i', $address, $m)) {
            return mb_strtolower($m[1]);
        }
        if (preg_match('/\b(?:unit|apt|suite)\s*#?\s*(\d+[a-z]?)\b/i', $address, $m)) {
            return mb_strtolower($m[1]);
        }
        // TREB style: "1050 Eastern Avenue 320, Toronto…"
        if (preg_match('/^\d+[a-z]?\s+.+\s+(\d+[a-z]?)\s*(?:,|$)/i', $address, $m)) {
            return mb_strtolower($m[1]);
        }

        return null;
    }

    private function extractStreetNumber(string $address): ?string
    {
        $address = trim($address);
        // "320 - 1050 Eastern…"
        if (preg_match('/^\s*#?\s*\d+[a-z]?\s*[-–]\s*(\d+)/i', $address, $m)) {
            return $m[1];
        }
        // "1050 Eastern Avenue 320…" (TREB unparsed style — street number first)
        if (preg_match('/^\s*(\d{2,6}[a-z]?)\s+[A-Za-z]/', $address, $m)) {
            return $m[1];
        }
        if (preg_match('/\b(\d{2,6}[a-z]?)\s+[A-Za-z]/', $address, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * TREB size bands → valuation midpoint (HouseSigma-style), not the band ceiling.
     * "0-499" ≈ 450, "500-599" ≈ 550.
     */
    private function valuationSqft(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        $raw = is_string($value) ? trim($value) : (string) $value;
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $raw, $m)) {
            $lo = (int) $m[1];
            $hi = (int) $m[2];
            if ($hi <= 0) {
                return 0.0;
            }
            if ($lo <= 0) {
                // Open lower band (0-499): use ~90% of ceiling — typical studio/1-bed size.
                return (float) max(350, (int) round($hi * 0.9));
            }

            return (float) (int) round(($lo + $hi) / 2);
        }

        return TrebPropertyHelper::parseSquareFeet($value);
    }

    private function daysOnMarketFromRow(object $row): ?int
    {
        $listed = $row->listing_contract_date ?? null;
        $closed = $row->close_date ?? null;
        if (! $listed || ! $closed) {
            return null;
        }
        try {
            $start = \Carbon\Carbon::parse((string) $listed);
            $end = \Carbon\Carbon::parse((string) $closed);
            if ($end->lt($start)) {
                return null;
            }

            return max(0, (int) $start->diffInDays($end));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $comps
     * @return array<string, mixed>
     */
    private function predictDaysOnMarket(array $comps, float $estimate): array
    {
        $doms = [];
        foreach ($comps as $c) {
            if (isset($c['days_on_market']) && is_numeric($c['days_on_market'])) {
                $doms[] = (int) $c['days_on_market'];
            }
        }
        sort($doms);
        $baseline = 45;
        if ($doms !== []) {
            $baseline = $doms[(int) floor((count($doms) - 1) / 2)];
        }

        return [
            'baseline_days' => (int) $baseline,
            'predicted_days' => (int) $baseline,
            'listed_at_price' => $estimate > 0 ? $this->roundCad($estimate) : null,
            'scale' => [
                'fast' => 7,
                'mark_20' => 20,
                'mark_60' => 60,
                'very_slow' => 120,
            ],
            'note' => 'Predicted from median days-on-market of nearby sold comparables. Raising the list price typically slows sales.',
        ];
    }

    /**
     * Buy/sell/lease timeline for this exact unit only (never other units in the building).
     *
     * @param  array<string, mixed>  $subject
     * @return list<array<string, mixed>>
     */
    private function listingHistoryForSubject(array $subject): array
    {
        $address = trim((string) ($subject['address'] ?? ''));
        $matchAddress = trim((string) ($subject['match_address'] ?? $address));
        $unit = $this->extractUnitToken($matchAddress !== '' ? $matchAddress : $address);
        $street = $this->extractStreetNumber($matchAddress !== '' ? $matchAddress : $address);
        $lat = (float) ($subject['latitude'] ?? 0);
        $lng = (float) ($subject['longitude'] ?? 0);
        $propertyId = (int) ($subject['id'] ?? 0);
        $externalId = trim((string) ($subject['external_id'] ?? ''));

        // Resolve unit from the DB row name when display address is ambiguous.
        if ($unit === null && $propertyId > 0) {
            $selfName = (string) (DB::table('re_properties')->where('id', $propertyId)->value('name') ?? '');
            if ($selfName !== '') {
                $matchAddress = $selfName;
            }
            $unit = $this->extractUnitToken($selfName);
            if ($street === null) {
                $street = $this->extractStreetNumber($selfName);
            }
        }

        $streetLine = trim(explode(',', $matchAddress !== '' ? $matchAddress : $address)[0] ?? '');
        $subjectParsed = AddressNormalizer::parseQuery($matchAddress !== '' ? $matchAddress : $address);

        $historyCols = [
            'id', 'external_id', 'name', 'price', 'ClosePrice', 'MlsStatus',
            'TransactionType', 'listing_contract_date', 'close_date', 'updated_at',
        ];

        // Split queries — OR(id, MATCH) makes MySQL abandon FULLTEXT and table-scan (~120s).
        $rows = collect();

        if ($propertyId > 0 || $externalId !== '') {
            $byKey = DB::table('re_properties')
                ->select($historyCols)
                ->where('moderation_status', 'approved')
                ->where(function ($inner) use ($propertyId, $externalId) {
                    if ($propertyId > 0) {
                        $inner->where('id', $propertyId);
                    }
                    if ($externalId !== '') {
                        $inner->orWhere('external_id', $externalId);
                    }
                })
                ->orderByDesc('listing_contract_date')
                ->limit(20)
                ->get();
            $rows = $rows->merge($byKey);
        }

        // Prefer map-pin lookup (indexed) over FULLTEXT for history — much faster.
        if (abs($lat) > 0.01 && abs($lng) > 0.01) {
            $byPin = DB::table('re_properties')
                ->select($historyCols)
                ->where('moderation_status', 'approved')
                ->whereBetween('latitude', [$lat - 0.00012, $lat + 0.00012])
                ->whereBetween('longitude', [$lng - 0.00012, $lng + 0.00012])
                ->orderByDesc('listing_contract_date')
                ->limit(40)
                ->get();
            $rows = $rows->merge($byPin);
        } elseif ($street !== null && $subjectParsed && $rows->isEmpty()) {
            $ftExpr = PropertyFulltextSearch::requiredTokenExpression(
                (string) $street,
                (string) ($subjectParsed['street_part'] ?? $subjectParsed['street_name'] ?? '')
            );
            if ($ftExpr !== '') {
                $byFt = DB::table('re_properties')
                    ->select($historyCols)
                    ->where('moderation_status', 'approved')
                    ->whereRaw('MATCH(`name`, `location`) AGAINST(? IN BOOLEAN MODE)', [$ftExpr])
                    ->when(mb_strlen((string) $street) < 3, function ($q) use ($street) {
                        $q->where('name', 'like', $street . ' %');
                    })
                    ->orderByDesc('listing_contract_date')
                    ->limit(40)
                    ->get();
                $rows = $rows->merge($byFt);
            }
        }

        if ($rows->isEmpty()) {
            return [];
        }

        $rows = $rows->unique('id')->sortByDesc(static function ($row) {
            return (string) ($row->listing_contract_date ?? '');
        })->values();
        $unitRows = [];
        foreach ($rows as $row) {
            $rowName = (string) ($row->name ?? '');
            $rowUnit = $this->extractUnitToken($rowName);
            $rowStreet = $this->extractStreetNumber($rowName);

            if ($unit !== null) {
                if ($rowUnit === null || $rowUnit !== $unit) {
                    continue;
                }
                if ($street !== null && $rowStreet !== null && $rowStreet !== $street) {
                    continue;
                }
            } else {
                // Same house only — reject other cities / units at similar street names.
                if ($rowUnit !== null) {
                    continue;
                }
                if ($subjectParsed && ! AddressNormalizer::addressMatches($rowName, $subjectParsed)) {
                    continue;
                }
            }

            $unitRows[] = $row;
        }

        usort($unitRows, static function ($a, $b): int {
            $da = (string) ($a->listing_contract_date ?? '');
            $db = (string) ($b->listing_contract_date ?? '');

            return $db <=> $da;
        });

        $events = [];
        foreach ($unitRows as $idx => $row) {
            $status = trim((string) ($row->MlsStatus ?? ''));
            $tx = trim((string) ($row->TransactionType ?? ''));
            $event = $this->historyEventLabel($status, $tx);
            $start = $this->formatOptionalDate($row->listing_contract_date ?? null);
            $end = $this->formatOptionalDate($row->close_date ?? null);
            if ($end === null && isset($unitRows[$idx - 1])) {
                $end = $this->formatOptionalDate($unitRows[$idx - 1]->listing_contract_date ?? null);
            }

            $listPrice = (float) ($row->price ?? 0);
            $closePrice = (float) ($row->ClosePrice ?? 0);
            $price = null;
            if ($tx === 'For Lease' || str_contains(mb_strtolower($status), 'leas')) {
                $price = $closePrice > 0 ? (int) round($closePrice) : ($listPrice > 0 ? (int) round($listPrice) : null);
            } elseif ($closePrice >= 50000) {
                $price = (int) round($closePrice);
            } elseif ($listPrice >= 50000) {
                $price = (int) round($listPrice);
            }

            $events[] = [
                'event' => $event,
                'date_start' => $start,
                'date_end' => $end,
                'date' => $start,
                'price' => $price,
                'status' => $status,
                'transaction_type' => $tx,
                'listing_id' => trim((string) ($row->external_id ?? '')),
                'mls' => trim((string) ($row->external_id ?? '')),
                'source' => 'listing',
            ];
        }

        $seen = [];
        $out = [];
        foreach ($events as $e) {
            $key = ($e['listing_id'] ?? '') !== ''
                ? (string) $e['listing_id']
                : (($e['date_start'] ?? '') . '|' . ($e['event'] ?? '') . '|' . ($e['price'] ?? ''));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $e;
            if (count($out) >= 12) {
                break;
            }
        }

        return $out;
    }

    private function historyEventLabel(string $status, string $transactionType): string
    {
        $s = mb_strtolower($status);
        if (str_contains($s, 'sold')) {
            return 'Sold';
        }
        if (str_contains($s, 'leased') || $s === 'lease') {
            return 'Leased';
        }
        if (str_contains($s, 'terminat')) {
            return 'Terminated';
        }
        if (str_contains($s, 'expir')) {
            return 'Expired';
        }
        if (str_contains($s, 'suspend')) {
            return 'Suspended';
        }
        if ($transactionType === 'For Lease') {
            return 'For Lease';
        }
        if ($transactionType === 'For Sale') {
            return 'For Sale';
        }

        return $status !== '' ? $status : 'Updated';
    }

    private function formatOptionalDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
