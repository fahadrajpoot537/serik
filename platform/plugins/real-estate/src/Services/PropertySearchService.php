<?php

namespace Botble\RealEstate\Services;

use Botble\RealEstate\Models\Property;
use Illuminate\Support\Facades\Cache;
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
                    'number_bedroom', 'number_bathroom', 'covered_spaces',
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
     * Tries exact Meili `city` filter, then free-text search.
     *
     * @return int[]|null  null = Meili unavailable
     */
    public function searchCityIds(string $city, int $limit = 2000, array $opts = []): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $city = trim($city);
        if ($city === '') {
            return [];
        }

        // Province cookie must not act as a city facet (would blank browse).
        if (strcasecmp($city, 'ontario') === 0 || strcasecmp($city, 'on') === 0) {
            return null;
        }

        // Avoid residential_only here: Meili drops docs missing property_sub_type.
        $opts = array_merge(['limit' => $limit], $opts);
        unset($opts['residential_only']);

        foreach ([ucwords(strtolower($city)), $city] as $variant) {
            $ids = $this->searchIds('', array_merge($opts, ['city' => $variant]));
            if ($ids !== null && $ids !== []) {
                return $ids;
            }
        }

        return $this->searchIds($city, $opts);
    }

    /**
     * Constrain an Eloquent/Query builder to Meili-matched IDs for a city.
     *
     * @param  bool  $strict  When true, empty Meili hits become WHERE 0=1 (search UX).
     *                        When false (browse/homepage/map), empty/unavailable skips
     *                        the city filter so the page does not go blank.
     * @return bool true if a city constraint was applied
     */
    public function constrainQueryToCity($query, string $city, int $limit = 2000, bool $strict = false): bool
    {
        $ids = $this->searchCityIds($city, $limit);
        if ($ids === null) {
            return false;
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

        if (! empty($opts['transaction'])) {
            $filters[] = 'transaction_type = "' . $this->escape($opts['transaction']) . '"';
        }

        if (! empty($opts['city'])) {
            $filters[] = 'city = "' . $this->escape($opts['city']) . '"';
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
