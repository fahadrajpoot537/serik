<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\Property;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Meilisearch\Client;
use Theme\homzen\Supports\TrebPropertyHelper;
use Throwable;

/**
 * Enterprise search facade over Meilisearch with a safe fallback contract:
 * every public method returns null when Meilisearch is unavailable/misconfigured
 * so callers can transparently fall back to the existing MySQL query paths.
 */
class PropertySearchService
{
    private ?Client $client = null;

    public function driverIsMeilisearch(): bool
    {
        return config('scout.driver') === 'meilisearch';
    }

    public function isAvailable(): bool
    {
        if (! $this->driverIsMeilisearch()) {
            return false;
        }

        // Cache health for 30s to avoid a network round-trip on every request.
        // Also require the properties index to exist with documents — an empty
        // healthy Meili (common on fresh live deploys) must report unavailable
        // so map/search fall back to MySQL instead of returning zero pins.
        return (bool) Cache::remember('serik_meili_health_v2', 30, function () {
            try {
                if (! $this->client()->isHealthy()) {
                    return false;
                }

                $stats = $this->index()->stats();
                $docs = (int) ($stats['numberOfDocuments'] ?? 0);

                return $docs > 0;
            } catch (Throwable) {
                return false;
            }
        });
    }

    /**
     * Fast facet-style total for browse pagination (limit=0 Meili search).
     *
     * @return int|null null when Meili unavailable
     */
    public function searchEstimatedTotal(string $keyword, array $opts = []): ?int
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $cacheKey = 'serik_meili_total_v1:' . md5(mb_strtolower($keyword) . '|' . json_encode($opts));

        return Cache::remember($cacheKey, 300, function () use ($keyword, $opts) {
            try {
                $params = [
                    'limit' => 0,
                    'offset' => 0,
                    'attributesToRetrieve' => ['id'],
                ];
                $filters = $this->buildFilters($opts);
                if ($filters !== '') {
                    $params['filter'] = $filters;
                }

                $res = $this->index()->search($keyword, $params);
                $hits = method_exists($res, 'getEstimatedTotalHits')
                    ? $res->getEstimatedTotalHits()
                    : ($res['estimatedTotalHits'] ?? $res['totalHits'] ?? null);

                return $hits === null ? null : (int) $hits;
            } catch (Throwable $e) {
                report($e);

                return null;
            }
        });
    }

    /**
     * Ordered listing IDs for the autocomplete / smart search box.
     *
     * @return int[]|null  null => Meilisearch unavailable (caller should fall back)
     */
    public function searchIds(string $keyword, array $opts = []): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $limit = (int) ($opts['limit'] ?? 10);
        $offset = (int) ($opts['offset'] ?? 0);

        $params = [
            'limit' => $limit,
            'offset' => $offset,
            'attributesToRetrieve' => ['id'],
        ];

        $filters = $this->buildFilters($opts);

        if ($filters !== '') {
            $params['filter'] = $filters;
        }

        if (! empty($opts['sort']) && is_array($opts['sort'])) {
            $params['sort'] = array_values($opts['sort']);
        }

        try {
            $res = $this->index()->search($keyword, $params);

            return array_values(array_map(
                static fn ($hit) => (int) $hit['id'],
                $res->getHits()
            ));
        } catch (Throwable $e) {
            // Sortable attribute missing on older indexes — retry without sort.
            if (! empty($params['sort'])) {
                try {
                    unset($params['sort']);
                    $res = $this->index()->search($keyword, $params);

                    return array_values(array_map(
                        static fn ($hit) => (int) $hit['id'],
                        $res->getHits()
                    ));
                } catch (Throwable $e2) {
                    report($e2);

                    return null;
                }
            }

            report($e);

            return null;
        }
    }

    /**
     * Geo/bounding-box search for the map. Returns raw hit documents (only the
     * columns the map needs) or null when Meilisearch is unavailable.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function geoSearch(float $south, float $north, float $west, float $east, array $opts = [], int $limit = 15000): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $filters = array_filter([
            sprintf('_geoBoundingBox([%F, %F], [%F, %F])', $north, $east, $south, $west),
            $this->buildFilters($opts),
        ]);

        try {
            $res = $this->index()->search((string) ($opts['keyword'] ?? ''), [
                'limit' => $limit,
                'filter' => implode(' AND ', $filters),
                // Only the fields the map feature builder actually emits — fewer
                // attributes = smaller Meili response + faster serialization.
                'attributesToRetrieve' => [
                    'id', 'name', 'external_id', 'price', 'close_price',
                    'number_bedroom', 'number_bathroom', 'bedrooms_below', 'covered_spaces',
                    'square', 'broker', 'mls_status', 'transaction_type',
                    'created_ts', '_geo',
                ],
            ]);

            return $res->getHits();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Ordered property IDs for street / address history sibling lookup.
     * Returns null when Meilisearch is unavailable (caller may use FULLTEXT only —
     * never a leading-% LIKE scan).
     *
     * @return int[]|null
     */
    public function searchStreetCandidateIds(
        string $streetNumber,
        string $streetName,
        int $limit = 40,
        array $opts = []
    ): ?array {
        if (! $this->isAvailable()) {
            return null;
        }

        $streetNumber = trim($streetNumber);
        $streetName = trim($streetName);
        if ($streetNumber === '' || $streetName === '') {
            return [];
        }

        $phrase = trim($streetNumber . ' ' . $streetName);
        $limit = max(1, min(100, $limit));

        $params = [
            'limit' => $limit,
            'offset' => 0,
            'attributesToRetrieve' => ['id'],
            // Prefer freshest siblings first (matches previous ORDER BY created_at DESC intent).
            'sort' => ['created_ts:desc'],
        ];

        $filterParts = [];
        if (! empty($opts['unit'])) {
            $filterParts[] = 'unit = "' . $this->escape((string) $opts['unit']) . '"';
        }
        // When street_number is indexed (after reindex), tighten candidate set.
        if (! empty($opts['filter_street_number'])) {
            $filterParts[] = 'street_number = "' . $this->escape($streetNumber) . '"';
        }
        $extra = $this->buildFilters($opts);
        if ($extra !== '') {
            $filterParts[] = $extra;
        }
        if ($filterParts !== []) {
            $params['filter'] = implode(' AND ', $filterParts);
        }

        try {
            $res = $this->index()->search($phrase, $params);
            $ids = array_values(array_map(
                static fn ($hit) => (int) $hit['id'],
                $res->getHits()
            ));

            // Soft retry without unit/street_number filters if too strict.
            if ($ids === [] && (! empty($opts['unit']) || ! empty($opts['filter_street_number']))) {
                unset($params['filter']);
                $extraOnly = $this->buildFilters(array_diff_key($opts, array_flip(['unit', 'filter_street_number'])));
                if ($extraOnly !== '') {
                    $params['filter'] = $extraOnly;
                }
                $res = $this->index()->search($phrase, $params);
                $ids = array_values(array_map(
                    static fn ($hit) => (int) $hit['id'],
                    $res->getHits()
                ));
            }

            return $ids;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Hydrate Scout/Meili IDs via a single whereIn (ordered). Never SELECT *.
     *
     * @param  int[]  $ids
     * @param  list<string>  $columns
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function hydrateIds(array $ids, array $columns): \Illuminate\Support\Collection
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return collect();
        }

        $rows = \Illuminate\Support\Facades\DB::table('re_properties')
            ->select($columns)
            ->whereIn('id', $ids)
            ->where('moderation_status', 'approved')
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $rows->get($id))
            ->filter()
            ->values();
    }

    /**
     * Resolve property IDs for a city without MySQL LIKE.
     * Tries exact Meili `city` filter, then TREB district aliases
     * (North York / Scarborough / Etobicoke), then geo radius, then free-text.
     *
     * @return int[]|null  null = Meili unavailable (and no district fallback)
     */
    public function searchCityIds(string $city, int $limit = 2000, array $opts = []): ?array
    {
        $city = trim($city);
        if ($city === '') {
            return [];
        }

        // Province cookie must not act as a city facet (would blank browse).
        if (strcasecmp($city, 'ontario') === 0 || strcasecmp($city, 'on') === 0) {
            return null;
        }

        $cacheKey = 'serik_city_ids_v4:' . md5(mb_strtolower($city) . '|' . $limit . '|' . md5(json_encode($opts)));

        // Never cache null — a temporary Meili outage would pin "no city filter"
        // for 15 minutes and force multi-second MySQL full scans on every filter click.
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $ids = $this->resolveCityIdsUncached($city, $limit, $opts);
        if (is_array($ids)) {
            Cache::put($cacheKey, $ids, 900);
        }

        return $ids;
    }

    /**
     * @return int[]|null
     */
    protected function resolveCityIdsUncached(string $city, int $limit, array $opts): ?array
    {
        if (! $this->isAvailable()) {
            // District / geo fallbacks still work without Meili.
            $districtIds = $this->searchDistrictCityIds($city, $limit);
            if ($districtIds !== null) {
                return $districtIds;
            }

            return null;
        }

        // Avoid residential_only here: Meili drops docs missing property_sub_type.
        $opts = array_merge(['limit' => $limit], $opts);
        unset($opts['residential_only']);

        foreach ([ucwords(strtolower($city)), $city] as $variant) {
            $found = $this->searchIds('', array_merge($opts, ['city' => $variant]));
            if ($found !== null && $found !== []) {
                return $found;
            }
        }

        // Former Toronto municipalities (North York, etc.) live as district codes.
        // Prefer districts only — geo radius overlaps neighboring GTA cities.
        $districtIds = $this->searchDistrictCityIds($city, $limit);
        if ($districtIds !== null && $districtIds !== []) {
            return $districtIds;
        }

        $geoIds = $this->searchCityIdsByGeo($city, $limit);
        if ($geoIds !== null && $geoIds !== []) {
            return $geoIds;
        }

        // Free-text last — can match street names ("Northgate"); only for
        // cities without district/geo coverage.
        return $this->searchIds($city, $opts);
    }

    /**
     * Resolve IDs via TREB City district codes stored in amp_snapshot meta.
     *
     * @return int[]|null  null = no district mapping for this city
     */
    public function searchDistrictCityIds(string $city, int $limit = 5000): ?array
    {
        $city = trim($city);
        if ($city === '') {
            return null;
        }

        $map = (array) config('seo_navigation.treb_city_districts', []);
        if ($map === []) {
            return null;
        }

        $slug = \Illuminate\Support\Str::slug($city);
        $districts = $map[$slug] ?? null;

        if ($districts === null) {
            foreach ($map as $key => $codes) {
                if (strcasecmp(str_replace('-', ' ', (string) $key), $city) === 0) {
                    $districts = $codes;
                    break;
                }
            }
        }

        if (! is_array($districts) || $districts === []) {
            return null;
        }

        $cacheKey = 'serik_district_ids_v1:' . $slug . ':' . $limit;

        return Cache::remember($cacheKey, 1800, function () use ($districts, $limit) {
            $districts = array_values(array_unique(array_map('strval', $districts)));
            $placeholders = implode(',', array_fill(0, count($districts), '?'));

            $ids = DB::table('meta_boxes as mb')
                ->where('mb.meta_key', 'amp_snapshot')
                ->where('mb.reference_type', Property::class)
                ->whereRaw(
                    'JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.City")) IN (' . $placeholders . ')',
                    $districts
                )
                ->orderByDesc('mb.reference_id')
                ->limit(max(1, min($limit, 8000)))
                ->pluck('mb.reference_id')
                ->map(static fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            return $ids;
        });
    }

    /**
     * Geo radius around a city lat/lng from the cities table.
     *
     * @return int[]|null
     */
    public function searchCityIdsByGeo(string $city, int $limit = 2000, float $radiusKm = 8.0): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $row = DB::table('cities')
            ->where(function ($q) use ($city): void {
                $q->where('name', $city)
                    ->orWhere('slug', \Illuminate\Support\Str::slug($city));
            })
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->first(['latitude', 'longitude']);

        if (! $row) {
            return null;
        }

        $lat = (float) $row->latitude;
        $lng = (float) $row->longitude;
        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }

        try {
            $res = $this->index()->search('', [
                'limit' => max(1, min($limit, 5000)),
                'filter' => '_geoRadius(' . $lat . ', ' . $lng . ', ' . (int) ($radiusKm * 1000) . ')',
                'attributesToRetrieve' => ['id'],
            ]);

            return array_values(array_map(
                static fn ($hit) => (int) $hit['id'],
                $res->getHits()
            ));
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Constrain an Eloquent/Query builder to Meili-matched IDs for a city.
     *
     * @param  bool  $strict  When true, empty Meili hits become WHERE 0=1 (search UX).
     *                        When false (browse/homepage/map), empty/unavailable skips
     *                        the city filter so the page does not go blank.
     * @return bool true if a city constraint was applied
     */
    public function constrainQueryToCity($query, string $city, int $limit = 2000, bool $strict = false, array $opts = []): bool
    {
        $ids = $this->searchCityIds($city, $limit, $opts);
        if ($ids === null) {
            // Meili down / empty index: still pin the city via address fragment so
            // filters never scan every Ontario listing (15–30s cold MySQL).
            return $this->constrainQueryToCityViaLocation($query, $city, $strict);
        }

        if ($ids === []) {
            if ($strict) {
                $query->whereRaw('0 = 1');

                return true;
            }

            return false;
        }

        $query->whereIn('id', $ids);

        return true;
    }

    /**
     * MySQL fallback when Meilisearch cannot resolve city IDs.
     * MLS addresses are stored as "…, {City}, ON {postal}".
     */
    public function constrainQueryToCityViaLocation($query, string $city, bool $strict = false): bool
    {
        $city = trim($city);
        if ($city === '' || strcasecmp($city, 'ontario') === 0 || strcasecmp($city, 'on') === 0) {
            return false;
        }

        // Escape LIKE wildcards in city names (e.g. odd punctuation).
        $safe = addcslashes($city, '%_\\');
        $pattern = '%, ' . $safe . ', ON%';

        $query->where('location', 'like', $pattern);

        return true;
    }

    /**
     * Constrain query to Meili keyword hits. Empty Meili = no rows.
     * Returns false when Meili unavailable.
     */
    public function constrainQueryToKeyword($query, string $keyword, int $limit = 500, array $opts = []): bool
    {
        $ids = $this->searchIds($keyword, array_merge(['limit' => $limit, 'residential_only' => true], $opts));
        if ($ids === null) {
            return false;
        }

        if ($ids === []) {
            $query->whereRaw('0 = 1');

            return true;
        }

        $query->whereIn('id', $ids);

        return true;
    }

    /**
     * Distinct community/neighbourhood suggestions for search autocomplete.
     *
     * @return array<int, array{name: string, city: string, lat: float|null, lng: float|null, count: int}>
     */
    public function searchCommunitySuggestions(string $keyword, int $limit = 8): array
    {
        $keyword = trim($keyword);
        if (mb_strlen($keyword) < 2 || $limit < 1) {
            return [];
        }

        $limit = min($limit, 12);
        $needle = mb_strtolower($keyword);
        $communities = [];

        foreach ($this->getCommunityIndex() as $row) {
            if (! $this->communityKeywordMatches($needle, $row)) {
                continue;
            }

            $key = mb_strtolower((string) ($row['name'] ?? '')) . '|' . mb_strtolower((string) ($row['city'] ?? ''));
            $communities[$key] = [
                'name' => $row['name'],
                'city' => $row['city'],
                'lat' => $row['lat'],
                'lng' => $row['lng'],
                'count' => (int) ($row['count'] ?? 1),
            ];
        }

        return $this->sortCommunitySuggestions($communities, $keyword, $limit);
    }

    /**
     * Compact community list for instant client-side autocomplete.
     *
     * @return array<int, array{n: string, c: string, la: float|null, lo: float|null, t: int}>
     */
    public function getPublicCommunityIndex(): array
    {
        return Cache::remember('serik_community_index_public_v1', 21600, function () {
            $out = [];

            foreach ($this->getCommunityIndex() as $row) {
                $out[] = [
                    'n' => (string) ($row['name'] ?? ''),
                    'c' => (string) ($row['city'] ?? ''),
                    'la' => $row['lat'] ?? null,
                    'lo' => $row['lng'] ?? null,
                    't' => (int) ($row['count'] ?? 1),
                ];
            }

            return $out;
        });
    }

    /**
     * Property IDs for a community (optionally scoped to a city).
     *
     * @return int[]
     */
    public function searchCommunityIds(string $community, ?string $city = null, int $limit = 5000): array
    {
        $community = TrebPropertyHelper::formatRegionLabel(trim($community));
        if ($community === '') {
            return [];
        }

        $opts = [
                'limit' => max(1, min($limit, 20000)),
            'residential_only' => true,
            'community' => $community,
        ];

        if ($city !== null && trim($city) !== '' && strcasecmp(trim($city), 'ontario') !== 0) {
            $normalizedCity = TrebPropertyHelper::formatCityLabel(trim($city));
            if ($normalizedCity !== '') {
                $opts['city'] = $normalizedCity;
            }
        }

        $cacheKey = 'serik_community_ids_v3:' . md5(mb_strtolower($community) . '|' . mb_strtolower(trim((string) $city)) . '|' . $limit);

        return Cache::remember($cacheKey, 1800, function () use ($community, $city, $limit) {
            return $this->searchCommunityIdsFromMysql($community, $city, $limit);
        });
    }

    /**
     * Pre-built distinct community list for instant autocomplete.
     *
     * @return array<int, array{name: string, city: string, lat: float|null, lng: float|null, count: int, raw_region: string}>
     */
    private function getCommunityIndex(): array
    {
        return Cache::remember('serik_community_index_v6', 21600, function () {
            $rows = $this->communityRegionQuery()
                ->select([
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.CityRegion")) as raw_region'),
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.City")) as raw_city'),
                    DB::raw('AVG(CASE WHEN p.latitude != 0 AND p.longitude != 0 THEN p.latitude END) as avg_lat'),
                    DB::raw('AVG(CASE WHEN p.latitude != 0 AND p.longitude != 0 THEN p.longitude END) as avg_lng'),
                    DB::raw('COUNT(*) as cnt'),
                ])
                ->groupBy('raw_region', 'raw_city')
                ->orderByDesc('cnt')
                ->get();

            $index = [];

            foreach ($rows as $row) {
                $rawRegion = (string) ($row->raw_region ?? '');
                $name = TrebPropertyHelper::formatRegionLabel($rawRegion);
                if ($name === '' || preg_match('/^[A-Z]\d+$/i', $name)) {
                    continue;
                }

                $city = TrebPropertyHelper::formatCityLabel((string) ($row->raw_city ?? ''));
                $key = mb_strtolower($name . '|' . $city);

                if (! isset($index[$key])) {
                    $index[$key] = [
                        'name' => $name,
                        'city' => $city,
                        'lat' => is_numeric($row->avg_lat ?? null) ? (float) $row->avg_lat : null,
                        'lng' => is_numeric($row->avg_lng ?? null) ? (float) $row->avg_lng : null,
                        'count' => (int) ($row->cnt ?? 1),
                        'raw_region' => $rawRegion,
                    ];

                    continue;
                }

                $index[$key]['count'] += (int) ($row->cnt ?? 1);
                if ($index[$key]['lat'] === null && is_numeric($row->avg_lat ?? null) && is_numeric($row->avg_lng ?? null)) {
                    $index[$key]['lat'] = (float) $row->avg_lat;
                    $index[$key]['lng'] = (float) $row->avg_lng;
                }
            }

            return array_values($index);
        });
    }

    /**
     * @param  array<string, array{name: string, city: string, lat: float|null, lng: float|null, count: int}>  $communities
     * @return array<int, array{name: string, city: string, lat: float|null, lng: float|null, count: int}>
     */
    private function sortCommunitySuggestions(array $communities, string $keyword, int $limit): array
    {
        if ($communities === []) {
            return [];
        }

        $needle = mb_strtolower($keyword);
        $values = array_values($communities);

        usort($values, static function (array $a, array $b) use ($needle) {
            $aName = mb_strtolower($a['name']);
            $bName = mb_strtolower($b['name']);
            $aStarts = str_starts_with($aName, $needle) ? 0 : 1;
            $bStarts = str_starts_with($bName, $needle) ? 0 : 1;
            if ($aStarts !== $bStarts) {
                return $aStarts <=> $bStarts;
            }
            if ($a['count'] !== $b['count']) {
                return $b['count'] <=> $a['count'];
            }

            return strcmp($a['name'], $b['name']);
        });

        return array_slice($values, 0, $limit);
    }

    /**
     * @return int[]
     */
    private function searchCommunityIdsFromMysql(string $community, ?string $city, int $limit): array
    {
        $needle = mb_strtolower($community);
        $cityFilter = $city !== null && trim($city) !== '' && strcasecmp(trim($city), 'ontario') !== 0
            ? TrebPropertyHelper::formatCityLabel(trim($city))
            : '';

        $query = $this->communityRegionQuery()
            ->where(function ($regionQuery) use ($community, $needle) {
                $regionQuery->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.CityRegion")) = ?', [$community])
                    ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.CityRegion"))) = ?', [$needle]);
            })
            ->select([
                'p.id',
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.CityRegion")) as raw_region'),
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.City")) as raw_city'),
            ])
            ->limit(max(1, min($limit, 20000)) * 2);

        if ($cityFilter !== '') {
            $cityLike = '%' . addcslashes(mb_strtolower($cityFilter), '%_\\') . '%';
            $query->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.City"))) LIKE ?', [$cityLike]);
        }

        $ids = [];

        foreach ($query->get() as $row) {
            $label = TrebPropertyHelper::formatRegionLabel((string) ($row->raw_region ?? ''));
            if (strcasecmp($label, $community) !== 0) {
                continue;
            }

            if ($cityFilter !== '') {
                $rowCity = TrebPropertyHelper::formatCityLabel((string) ($row->raw_city ?? ''));
                if ($rowCity !== '' && strcasecmp($rowCity, $cityFilter) !== 0) {
                    continue;
                }
            }

            $ids[] = (int) $row->id;
            if (count($ids) >= $limit) {
                break;
            }
        }

        return array_values(array_unique($ids));
    }

    private function suffixFamily(string $value): string
    {
        if (preg_match('/(ville|borough|burg|wood|dale|view|park|hill|side|town|grove|valley)$/i', $value, $m)) {
            return mb_strtolower($m[1]);
        }

        return '';
    }

    private function communityRegionQuery(): Builder
    {
        $excluded = array_values(array_unique(array_merge(
            TrebPropertyHelper::excludedCommercialSubTypes(),
            array_map(static fn ($v) => $v . ' ', TrebPropertyHelper::excludedCommercialSubTypes())
        )));

        return DB::table('meta_boxes as mb')
            ->join('re_properties as p', 'p.id', '=', 'mb.reference_id')
            ->where('mb.reference_type', Property::class)
            ->where('mb.meta_key', 'amp_snapshot')
            ->where(function ($query) use ($excluded) {
                $query->whereNull('p.PropertySubType')
                    ->orWhereNotIn('p.PropertySubType', $excluded);
            });
    }

    private function communitySnapshotQuery(): Builder
    {
        return $this->communityRegionQuery()
            ->where('p.latitude', '!=', 0)
            ->where('p.longitude', '!=', 0);
    }

    /**
     * @param  array{name: string, city: string, lat: float|null, lng: float|null, count: int, raw_region?: string}  $row
     */
    private function communityKeywordMatches(string $needle, array $row): bool
    {
        $nameLower = mb_strtolower((string) ($row['name'] ?? ''));
        $rawLower = mb_strtolower((string) ($row['raw_region'] ?? ''));
        $cityLower = mb_strtolower((string) ($row['city'] ?? ''));

        if (str_contains($nameLower, $needle)
            || str_contains($rawLower, $needle)
            || str_contains($cityLower, $needle)) {
            return true;
        }

        if (mb_strlen($needle) < 5) {
            return false;
        }

        $prefixLen = 0;
        $nameLen = mb_strlen($nameLower);
        $needleLen = mb_strlen($needle);
        $max = min($needleLen, $nameLen);

        while ($prefixLen < $max && mb_substr($needle, $prefixLen, 1) === mb_substr($nameLower, $prefixLen, 1)) {
            $prefixLen++;
        }

        // Typo tolerance only when suffix family matches (ville→ville, borough→borough).
        $needleFamily = $this->suffixFamily($needle);
        $nameFamily = $this->suffixFamily($nameLower);
        if ($needleFamily !== '' && $needleFamily === $nameFamily) {
            return $prefixLen >= 6;
        }

        return false;
    }

    private function buildFilters(array $opts): string
    {
        $filters = [];

        if (! empty($opts['residential_only'])) {
            $base = TrebPropertyHelper::excludedCommercialSubTypes();
            $excluded = array_map(
                static fn ($v) => '"' . str_replace('"', '', (string) $v) . '"',
                array_values(array_unique(array_merge(
                    $base,
                    array_map(static fn ($v) => $v . ' ', $base)
                )))
            );

            if ($excluded !== []) {
                // Include docs with empty/missing subtype (parity with SQL
                // whereNull('PropertySubType')->orWhereNotIn(...)). Meili's
                // plain NOT IN drops documents that lack the attribute.
                $filters[] = '(property_sub_type NOT IN [' . implode(', ', $excluded)
                    . '] OR property_sub_type IS EMPTY OR property_sub_type NOT EXISTS)';
            }
        }

        if (! empty($opts['transactions']) && is_array($opts['transactions'])) {
            $escaped = [];
            foreach ($opts['transactions'] as $tx) {
                $tx = trim((string) $tx);
                if ($tx !== '') {
                    $escaped[] = '"' . $this->escape($tx) . '"';
                }
            }
            if ($escaped !== []) {
                $filters[] = 'transaction_type IN [' . implode(', ', $escaped) . ']';
            }
        } elseif (! empty($opts['transaction'])) {
            $filters[] = 'transaction_type = "' . $this->escape($opts['transaction']) . '"';
        }

        if (! empty($opts['city'])) {
            $filters[] = 'city = "' . $this->escape($opts['city']) . '"';
        }

        if (! empty($opts['community'])) {
            $filters[] = 'community = "' . $this->escape($opts['community']) . '"';
        }

        if (! empty($opts['street_number'])) {
            $filters[] = 'street_number = "' . $this->escape((string) $opts['street_number']) . '"';
        }

        if (! empty($opts['street_name'])) {
            $filters[] = 'street_name = "' . $this->escape((string) $opts['street_name']) . '"';
        }

        if (! empty($opts['status'])) {
            if ($opts['status'] === 'Sold') {
                $filters[] = 'is_sold = true';
            } else {
                $filters[] = 'mls_status = "' . $this->escape($opts['status']) . '"';
            }
        }

        // Restrict to an explicit set of MlsStatus values (e.g. the Active set).
        if (! empty($opts['statuses']) && is_array($opts['statuses'])) {
            $vals = array_map(fn ($v) => '"' . $this->escape((string) $v) . '"', $opts['statuses']);
            $filters[] = 'mls_status IN [' . implode(', ', $vals) . ']';
        }

        // Exclude a set of MlsStatus values (default active browse hides sold/de-listed).
        if (! empty($opts['exclude_statuses']) && is_array($opts['exclude_statuses'])) {
            $vals = array_map(fn ($v) => '"' . $this->escape((string) $v) . '"', $opts['exclude_statuses']);
            $filters[] = 'mls_status NOT IN [' . implode(', ', $vals) . ']';
        }

        if (isset($opts['min_price']) && $opts['min_price'] > 0) {
            $filters[] = 'price >= ' . (float) $opts['min_price'];
        }

        if (isset($opts['max_price']) && $opts['max_price'] > 0) {
            $filters[] = 'price <= ' . (float) $opts['max_price'];
        }

        if (isset($opts['min_bedrooms']) && $opts['min_bedrooms'] > 0) {
            $filters[] = 'number_bedroom >= ' . (int) $opts['min_bedrooms'];
        }

        if (isset($opts['min_bathrooms']) && $opts['min_bathrooms'] > 0) {
            $filters[] = 'number_bathroom >= ' . (int) $opts['min_bathrooms'];
        }

        if (! empty($opts['subtypes']) && is_array($opts['subtypes'])) {
            $vals = array_values(array_filter(array_map(
                fn ($v) => '"' . $this->escape((string) $v) . '"',
                $opts['subtypes']
            )));
            if ($vals !== []) {
                $filters[] = 'property_sub_type IN [' . implode(', ', $vals) . ']';
            }
        }

        if (isset($opts['min_square']) && $opts['min_square'] > 0) {
            $filters[] = 'square >= ' . (int) $opts['min_square'];
        }

        if (isset($opts['max_square']) && $opts['max_square'] > 0) {
            $filters[] = 'square <= ' . (int) $opts['max_square'];
        }

        if (isset($opts['min_covered_spaces']) && $opts['min_covered_spaces'] > 0) {
            $filters[] = 'covered_spaces >= ' . (int) $opts['min_covered_spaces'];
        }

        // Date-window filters use the indexed numeric timestamps so "Last N days"
        // resolves entirely inside Meilisearch instead of a slow MySQL scan.
        // 'listing_contract_ts' => active "Listed On" date; 'close_ts' => sold date.
        foreach (['listing_contract_ts', 'close_ts', 'created_ts'] as $tsField) {
            if (isset($opts[$tsField . '_gte']) && $opts[$tsField . '_gte'] > 0) {
                $filters[] = $tsField . ' >= ' . (int) $opts[$tsField . '_gte'];
            }
            if (isset($opts[$tsField . '_lte']) && $opts[$tsField . '_lte'] > 0) {
                $filters[] = $tsField . ' <= ' . (int) $opts[$tsField . '_lte'];
            }
        }

        return implode(' AND ', $filters);
    }

    private function escape(string $value): string
    {
        return str_replace('"', '\"', $value);
    }

    private function index()
    {
        return $this->client()->index((new Property())->searchableAs());
    }

    private function client(): Client
    {
        if ($this->client === null) {
            $this->client = new Client(
                (string) config('scout.meilisearch.host'),
                config('scout.meilisearch.key')
            );
        }

        return $this->client;
    }
}
