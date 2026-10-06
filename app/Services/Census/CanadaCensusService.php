<?php

namespace App\Services\Census;

use Botble\RealEstate\Models\Property;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CanadaCensusService
{
    public const STATUS_OK = 'ok';

    public const STATUS_MISSING_ADDRESS = 'missing_address';

    public const STATUS_GEOCODE_UNAVAILABLE = 'geocode_unavailable';

    public const STATUS_GEOGRAPHY_UNAVAILABLE = 'geography_unavailable';

    public const STATUS_CENSUS_UNAVAILABLE = 'census_unavailable';

    /**
     * Full pipeline for a property detail page (cached).
     *
     * @return array<string, mixed>
     */
    public function getPropertyCensusData(Property $property): array
    {
        $propertyId = (int) $property->getKey();
        $ttl = max(3600, (int) config('census.cache_ttl', 2592000));
        $cacheKey = 'census:property:' . $propertyId . ':v10';

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['status'])) {
            return $cached;
        }

        $lock = Cache::lock('serik:cache:sf:' . md5($cacheKey), 45);

        try {
            $payload = $lock->block(app()->environment('local') ? 3 : 15, function () use ($cacheKey, $ttl, $property) {
                $again = Cache::get($cacheKey);
                if (is_array($again) && isset($again['status'])) {
                    return $again;
                }

                $computed = $this->computeForProperty($property);
                // Cache successes for full TTL; cache failures briefly so env/API fixes recover quickly.
                $putTtl = ($computed['status'] ?? null) === self::STATUS_OK
                    ? $ttl
                    : min(300, $ttl);
                Cache::put($cacheKey, $computed, $putTtl);

                return $computed;
            });
        } catch (Throwable $e) {
            Log::warning('census.property.lock_failed', [
                'property_id' => $propertyId,
                'message' => $e->getMessage(),
            ]);

            $again = Cache::get($cacheKey);
            if (is_array($again) && isset($again['status'])) {
                return $again;
            }

            return $this->computeForProperty($property);
        }

        return is_array($payload) ? $payload : $this->errorPayload(self::STATUS_CENSUS_UNAVAILABLE);
    }

    /**
     * @return array<string, mixed>
     */
    protected function computeForProperty(Property $property): array
    {
        $address = $this->buildAddress($property);
        if ($address === '') {
            return $this->errorPayload(self::STATUS_MISSING_ADDRESS, [
                'property_id' => (int) $property->getKey(),
                'message' => 'Census data unavailable for this property.',
            ]);
        }

        $coords = $this->resolveCoordinates($property, $address);
        if ($coords === null) {
            return $this->errorPayload(self::STATUS_GEOCODE_UNAVAILABLE, [
                'property_id' => (int) $property->getKey(),
                'address' => $address,
                'message' => 'Census data unavailable for this property.',
            ]);
        }

        $lat = (float) $coords['lat'];
        $lng = (float) $coords['lng'];
        $daGeo = $this->findDisseminationArea($lat, $lng);

        $resolved = $this->resolveCensusProfileForPoint($lat, $lng, $daGeo);
        if ($resolved === null) {
            return $this->errorPayload(self::STATUS_CENSUS_UNAVAILABLE, [
                'property_id' => (int) $property->getKey(),
                'address' => $address,
                'latitude' => $lat,
                'longitude' => $lng,
                'dauid' => $daGeo['dauid'] ?? null,
                'dguid' => $daGeo['dguid'] ?? null,
                'pruid' => $daGeo['pruid'] ?? null,
                'message' => 'Census data temporarily unavailable.',
            ]);
        }

        $profile = $resolved['profile'];
        $metrics = $this->calculateMetrics($profile['values'] ?? []);
        $charts = $this->buildCharts($profile['values'] ?? [], $profile['rates'] ?? []);
        $dguid = (string) $resolved['dguid'];

        return [
            'status' => self::STATUS_OK,
            'message' => null,
            'property_id' => (int) $property->getKey(),
            'address' => $address,
            'latitude' => $lat,
            'longitude' => $lng,
            'dauid' => (string) ($daGeo['dauid'] ?? $resolved['geo_id'] ?? ''),
            'dguid' => $dguid,
            'pruid' => (string) ($daGeo['pruid'] ?? $resolved['pruid'] ?? ''),
            'geography_level' => (string) $resolved['level'],
            'metrics' => $metrics,
            'charts' => $charts,
            'categories' => $this->buildCategorySnapshot($profile['values'] ?? []),
            'source' => 'Statistics Canada — 2021 Census',
            'source_url' => $this->profilePageUrl($dguid),
            'synced_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Try SDMX profile dataflows in configured order (DA → ADA → CSD).
     * DF_DA often hangs with 0 bytes from some networks; ADA/CSD stay reachable.
     *
     * @param  array{dauid: string, dguid: string, pruid: string}|null  $daGeo
     * @return array{profile: array{values: array}, dguid: string, level: string, geo_id: string, pruid: string}|null
     */
    protected function resolveCensusProfileForPoint(float $lat, float $lng, ?array $daGeo): ?array
    {
        $levels = config('census.statcan.profile_levels', ['da', 'ada', 'csd']);
        if (! is_array($levels) || $levels === []) {
            $levels = ['da', 'ada', 'csd'];
        }

        foreach ($levels as $level) {
            $level = strtolower(trim((string) $level));
            $candidate = match ($level) {
                'da' => $daGeo !== null ? [
                    'dguid' => (string) $daGeo['dguid'],
                    'geo_id' => (string) $daGeo['dauid'],
                    'pruid' => (string) $daGeo['pruid'],
                ] : null,
                'ada' => $this->findBoundaryGeo($lat, $lng, 'ada'),
                'csd' => $this->findBoundaryGeo($lat, $lng, 'csd'),
                default => null,
            };

            if ($candidate === null || ($candidate['dguid'] ?? '') === '') {
                continue;
            }

            $baseUrl = (string) (config('census.statcan.profile_urls.' . $level)
                ?: config('census.statcan.profile_url'));
            if ($baseUrl === '') {
                continue;
            }

            $profile = $this->getCensusProfileCached((string) $candidate['dguid'], $baseUrl, $level);
            if ($profile === null) {
                Log::info('census.profile.level_miss', [
                    'level' => $level,
                    'dguid' => $candidate['dguid'],
                ]);

                continue;
            }

            Log::info('census.profile.level_hit', [
                'level' => $level,
                'dguid' => $candidate['dguid'],
            ]);

            return [
                'profile' => $profile,
                'dguid' => (string) $candidate['dguid'],
                'level' => $level,
                'geo_id' => (string) ($candidate['geo_id'] ?? ''),
                'pruid' => (string) ($candidate['pruid'] ?? ''),
            ];
        }

        if ($daGeo === null) {
            Log::warning('census.geography.all_levels_failed', ['lat' => $lat, 'lng' => $lng]);
        }

        return null;
    }

    /**
     * Point-in-polygon for ADA (layer 10) or CSD (layer 9).
     *
     * @return array{dguid: string, geo_id: string, pruid: string}|null
     */
    public function findBoundaryGeo(float $lat, float $lng, string $level): ?array
    {
        $level = strtolower($level);
        $layer = match ($level) {
            'ada' => (int) config('census.statcan.ada_layer', 10),
            'csd' => (int) config('census.statcan.csd_layer', 9),
            default => 0,
        };
        if ($layer < 1) {
            return null;
        }

        $cacheKey = 'census:' . $level . ':' . round($lat, 5) . ':' . round($lng, 5) . ':v8';
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['dguid'])) {
            return $cached;
        }

        $identify = rtrim((string) config('census.statcan.da_identify_url'), '/');
        $url = str_ends_with($identify, '/identify')
            ? substr($identify, 0, -strlen('identify')) . $layer . '/query'
            : 'https://geo.statcan.gc.ca/geo_wa/rest/services/2021/Digital_boundary_files/MapServer/' . $layer . '/query';

        $outFields = $level === 'ada' ? 'ADAUID,DGUID,PRUID' : 'CSDUID,DGUID,PRUID';
        $params = [
            'geometry' => $lng . ',' . $lat,
            'geometryType' => 'esriGeometryPoint',
            'inSR' => '4326',
            'spatialRel' => 'esriSpatialRelIntersects',
            'outFields' => $outFields,
            'returnGeometry' => 'false',
            'f' => 'json',
        ];

        $verify = (bool) config('census.statcan.geo_ssl_verify', true);
        if (app()->environment('local')) {
            $verify = false;
        }

        $lastError = null;
        $body = $this->requestStatCanGeo($url, $params, $verify, $lastError, 'features');
        if ($body === null && $verify) {
            $body = $this->requestStatCanGeo($url, $params, false, $lastError, 'features');
        }
        if ($body === null) {
            Log::warning('census.' . $level . '.no_result', [
                'lat' => $lat,
                'lng' => $lng,
                'error' => $lastError,
            ]);

            return null;
        }

        $json = json_decode($body, true);
        $attrs = $json['features'][0]['attributes'] ?? null;
        if (! is_array($attrs)) {
            return null;
        }

        $dguid = trim((string) ($attrs['DGUID'] ?? ''));
        $geoId = trim((string) ($attrs[$level === 'ada' ? 'ADAUID' : 'CSDUID'] ?? ''));
        $pruid = trim((string) ($attrs['PRUID'] ?? ''));
        if ($dguid === '') {
            return null;
        }
        if ($pruid === '' && $geoId !== '' && strlen($geoId) >= 2) {
            $pruid = substr($geoId, 0, 2);
        }

        $geo = [
            'dguid' => $dguid,
            'geo_id' => $geoId,
            'pruid' => $pruid,
        ];
        Cache::put($cacheKey, $geo, max(3600, (int) config('census.cache_ttl', 2592000)));

        return $geo;
    }

    public function buildAddress(Property $property): string
    {
        $parts = [];

        try {
            if (class_exists(\Theme\homzen\Supports\TrebPropertyHelper::class)) {
                $helper = \Theme\homzen\Supports\TrebPropertyHelper::class;
                $listingKey = trim((string) ($property->external_id ?? ''));
                $localData = $helper::dbRowToLocalArray($property);
                $record = $listingKey !== ''
                    ? $helper::resolveFactRecordForDetail($listingKey, $localData)
                    : $localData;

                if (is_array($record) && $record !== []) {
                    // Civic street only — unit tokens make geocoders pick the wrong point/DA.
                    $street = $this->stripLeadingUnit(trim((string) $helper::formatStreetLine($record)));
                    $line = (string) $helper::formatLocationLine($record);
                    $postal = trim((string) ($record['PostalCode'] ?? $property->zip_code ?? ''));
                    foreach ([$street, $line, $postal, 'Canada'] as $p) {
                        $p = trim((string) $p);
                        if ($p !== '') {
                            $parts[] = $p;
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            Log::debug('census.address.treb_failed', [
                'property_id' => $property->getKey(),
                'message' => $e->getMessage(),
            ]);
        }

        if ($parts === []) {
            $google = trim((string) ($property->google_formatted_address ?? ''));
            if ($google !== '') {
                return $this->sanitizeAddress($this->stripLeadingUnit($google));
            }

            $location = trim((string) ($property->location ?? ''));
            if ($location !== '') {
                $parts[] = $this->stripLeadingUnit($location);
            } else {
                $name = trim((string) ($property->name ?? ''));
                $city = trim((string) ($property->cityName ?? ''));
                $zip = trim((string) ($property->zip_code ?? ''));
                foreach ([$this->stripLeadingUnit($name), $city, $zip, 'Canada'] as $p) {
                    if ($p !== '') {
                        $parts[] = $p;
                    }
                }
            }
        }

        $joined = implode(', ', array_values(array_unique($parts)));

        return $this->sanitizeAddress($joined);
    }

    /**
     * Drop unit tokens that confuse geocoders.
     * "412 - 455 Rosewell" → "455 Rosewell"
     * "448 Burnhamthorpe Rd W 5611" → "448 Burnhamthorpe Rd W"
     */
    protected function stripLeadingUnit(string $address): string
    {
        $address = trim($address);
        $address = preg_replace('/^(?:unit|apt|suite|#)?\s*\d+\s*[-–]\s*/i', '', $address) ?? $address;
        // Trailing condo/unit number after a street suffix word.
        $address = preg_replace(
            '/\s+\d{1,6}\s*(?=,|$)/',
            '',
            $address,
            1
        ) ?? $address;

        return trim(preg_replace('/\s+/', ' ', $address) ?? $address);
    }

    protected function sanitizeAddress(string $address): string
    {
        $address = trim(preg_replace('/\s+/', ' ', $address) ?? '');
        $address = strip_tags($address);

        return mb_substr($address, 0, 300);
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function resolveCoordinates(Property $property, string $address): ?array
    {
        // Geocode the civic building address first. Listing pins are often on a
        // neighbouring DA (condo unit vs street number), which skews percents vs HouseSigma.
        $geo = $this->geocodeAddress($address);
        if ($geo !== null) {
            return $geo;
        }

        $lat = $this->toFloatOrNull($property->latitude ?? null);
        $lng = $this->toFloatOrNull($property->longitude ?? null);

        if ($lat !== null && $lng !== null && $this->isCanadianCoordinate($lat, $lng)) {
            return ['lat' => $lat, 'lng' => $lng];
        }

        return null;
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function geocodeAddress(string $address): ?array
    {
        $apiKey = trim((string) config('census.geoapify.api_key'));
        if ($apiKey === '' || $address === '') {
            Log::warning('census.geoapify.missing_key_or_address');

            return null;
        }

        $cacheKey = 'census:geocode:' . md5(mb_strtolower($address));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['lat'], $cached['lng'])) {
            return ['lat' => (float) $cached['lat'], 'lng' => (float) $cached['lng']];
        }

        try {
            $response = Http::timeout((int) config('census.geoapify.timeout', 12))
                ->acceptJson()
                ->get((string) config('census.geoapify.geocode_url'), [
                    'text' => $address,
                    'filter' => 'countrycode:ca',
                    'limit' => 1,
                    'format' => 'json',
                    'apiKey' => $apiKey,
                ]);

            if (! $response->successful()) {
                Log::warning('census.geoapify.http_failed', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $results = $response->json('results') ?? $response->json('features');
            $lat = null;
            $lng = null;

            if (is_array($results) && isset($results[0])) {
                $first = $results[0];
                if (isset($first['lat'], $first['lon'])) {
                    $lat = (float) $first['lat'];
                    $lng = (float) $first['lon'];
                } elseif (isset($first['geometry']['coordinates'][0], $first['geometry']['coordinates'][1])) {
                    $lng = (float) $first['geometry']['coordinates'][0];
                    $lat = (float) $first['geometry']['coordinates'][1];
                }
            }

            if ($lat === null || $lng === null || ! $this->isCanadianCoordinate($lat, $lng)) {
                Log::info('census.geoapify.no_result');

                return null;
            }

            $coords = ['lat' => $lat, 'lng' => $lng];
            Cache::put($cacheKey, $coords, max(3600, (int) config('census.cache_ttl', 2592000)));

            return $coords;
        } catch (Throwable $e) {
            Log::warning('census.geoapify.exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array{dauid: string, dguid: string, pruid: string}|null
     */
    public function findDisseminationArea(float $lat, float $lng): ?array
    {
        $cacheKey = 'census:da:' . round($lat, 5) . ':' . round($lng, 5);
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && ! empty($cached['dguid'])) {
            return $cached;
        }

        $verify = (bool) config('census.statcan.geo_ssl_verify', true);
        // Local Windows TLS inspection often breaks StatCan's cert chain.
        if (app()->environment('local') && ! $verify) {
            $verify = false;
        }

        $lastError = null;
        $fromQuery = $this->queryDisseminationArea($lat, $lng, $verify, $lastError);
        if ($fromQuery !== null) {
            Cache::put($cacheKey, $fromQuery, max(3600, (int) config('census.cache_ttl', 2592000)));

            return $fromQuery;
        }

        $url = (string) config('census.statcan.da_identify_url');
        if ($url === '') {
            Log::warning('census.da.missing_url');

            return null;
        }

        $deltas = [0.0008, 0.002, 0.005];

        foreach ($deltas as $delta) {
            $params = [
                'geometry' => $lng . ',' . $lat,
                'geometryType' => 'esriGeometryPoint',
                'sr' => '4326',
                'layers' => (string) config('census.statcan.da_layer', 'all:12'),
                'tolerance' => 1,
                'mapExtent' => ($lng - $delta) . ',' . ($lat - $delta) . ',' . ($lng + $delta) . ',' . ($lat + $delta),
                'imageDisplay' => '400,400,96',
                'returnGeometry' => 'false',
                'f' => 'json',
            ];

            $body = $this->requestStatCanGeo($url, $params, $verify, $lastError);
            if ($body === null && $verify && app()->environment('local')) {
                Log::warning('census.da.ssl_retry_local', ['error' => $lastError]);
                $body = $this->requestStatCanGeo($url, $params, false, $lastError);
            }

            if ($body === null) {
                continue;
            }

            $json = json_decode($body, true);
            $results = is_array($json) ? ($json['results'] ?? null) : null;
            if (! is_array($results) || $results === []) {
                continue;
            }

            $geo = $this->geoFromIdentifyResults($results);
            if ($geo === null) {
                continue;
            }

            Cache::put($cacheKey, $geo, max(3600, (int) config('census.cache_ttl', 2592000)));

            return $geo;
        }

        Log::warning('census.da.no_result', [
            'lat' => $lat,
            'lng' => $lng,
            'error' => $lastError,
        ]);

        return null;
    }

    /**
     * Point-in-polygon on DA layer 12 (avoids Identify returning Province as result[0]).
     *
     * @return array{dauid: string, dguid: string, pruid: string}|null
     */
    protected function queryDisseminationArea(float $lat, float $lng, bool $verify, ?string &$lastError): ?array
    {
        $identify = rtrim((string) config('census.statcan.da_identify_url'), '/');
        $url = str_ends_with($identify, '/identify')
            ? substr($identify, 0, -strlen('identify')) . '12/query'
            : 'https://geo.statcan.gc.ca/geo_wa/rest/services/2021/Digital_boundary_files/MapServer/12/query';

        // Plain "lng,lat" — JSON geometry objects currently 500 on StatCan's adaptor.
        $params = [
            'geometry' => $lng . ',' . $lat,
            'geometryType' => 'esriGeometryPoint',
            'inSR' => '4326',
            'spatialRel' => 'esriSpatialRelIntersects',
            'outFields' => 'DAUID,DGUID,PRUID',
            'returnGeometry' => 'false',
            'f' => 'json',
        ];

        $body = $this->requestStatCanGeo($url, $params, $verify, $lastError, 'features');
        if ($body === null && $verify && app()->environment('local')) {
            $body = $this->requestStatCanGeo($url, $params, false, $lastError, 'features');
        }
        if ($body === null) {
            return null;
        }

        $json = json_decode($body, true);
        $attrs = $json['features'][0]['attributes'] ?? null;
        if (! is_array($attrs)) {
            return null;
        }

        return $this->normalizeDaGeo($attrs);
    }

    /**
     * @param  list<array<string, mixed>>  $results
     * @return array{dauid: string, dguid: string, pruid: string}|null
     */
    protected function geoFromIdentifyResults(array $results): ?array
    {
        foreach ($results as $result) {
            $layer = strtoupper((string) ($result['layerName'] ?? ''));
            $attrs = is_array($result['attributes'] ?? null) ? $result['attributes'] : [];
            $dauid = (string) ($attrs['DAUID'] ?? '');
            $isDa = str_contains($layer, 'DA -') || str_contains($layer, 'DA—') || preg_match('/^\d{8}$/', $dauid);
            if (! $isDa) {
                continue;
            }

            $geo = $this->normalizeDaGeo($attrs + ['value' => $result['value'] ?? '']);
            if ($geo !== null) {
                return $geo;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @return array{dauid: string, dguid: string, pruid: string}|null
     */
    protected function normalizeDaGeo(array $attrs): ?array
    {
        $dauid = (string) ($attrs['DAUID'] ?? $attrs['value'] ?? '');
        $dguid = (string) ($attrs['DGUID'] ?? '');
        $pruid = (string) ($attrs['PRUID'] ?? '');

        if ($dguid === '' && preg_match('/^\d{8}$/', $dauid)) {
            $dguid = '2021S0512' . $dauid;
        }

        if ($dauid === '' && preg_match('/^2021S0512(\d{8})$/', $dguid, $m)) {
            $dauid = $m[1];
        }

        if ($dguid === '' || $dauid === '' || ! preg_match('/^\d{8}$/', $dauid)) {
            return null;
        }

        if ($pruid === '' && strlen($dauid) >= 2) {
            $pruid = substr($dauid, 0, 2);
        }

        return [
            'dauid' => $dauid,
            'dguid' => $dguid,
            'pruid' => $pruid,
        ];
    }

    /**
     * Prefer cURL for StatCan geo (Laravel HTTP + Accept: application/json is flaky with their ArcGIS adaptor).
     *
     * @param  array<string, scalar>  $params
     */
    protected function requestStatCanGeo(string $url, array $params, bool $verify, ?string &$lastError, string $okNeedle = 'results'): ?string
    {
        $fullUrl = $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
        $timeout = app()->environment('local')
            ? 10
            : max(12, min(20, (int) config('census.statcan.timeout', 45)));

        if (function_exists('curl_init')) {
            $ch = curl_init($fullUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => app()->environment('local') ? 4 : 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => $verify,
                CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
                CURLOPT_HTTPHEADER => ['User-Agent: SerikRealtyCensus/1.0 (+https://serik.ca)'],
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body === false || $code < 200 || $code >= 300 || ! is_string($body) || ! str_contains($body, $okNeedle)) {
                $lastError = $err !== '' ? $err : ('HTTP ' . $code);

                return null;
            }

            return $body;
        }

        try {
            $response = $this->httpClient($verify)->get($url, $params);
            if (! $response->successful() || ! str_contains($response->body(), 'results')) {
                $lastError = 'HTTP ' . $response->status();

                return null;
            }

            return $response->body();
        } catch (Throwable $e) {
            $lastError = $e->getMessage();

            return null;
        }
    }

    /**
     * @return array{values: array<string, float|int|string|null>, raw_keys?: array}|null
     */
    public function getCensusProfileCached(string $dguid, ?string $profileBaseUrl = null, string $level = 'da'): ?array
    {
        $dguid = trim($dguid);
        if ($dguid === '') {
            return null;
        }

        $cacheKey = 'census:dguid:' . $dguid . ':v10';
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['values'])) {
            return $cached;
        }

        $profile = $this->getCensusProfile($dguid, $profileBaseUrl, $level);
        if ($profile === null) {
            // Brief negative cache — avoids hammering StatCan / max_execution FatalErrors.
            Cache::put($cacheKey . ':miss', 1, 90);

            return null;
        }

        Cache::put($cacheKey, $profile, max(3600, (int) config('census.cache_ttl', 2592000)));

        return $profile;
    }

    /**
     * @return array{values: array<string, float|int|string|null>, rates: array<string, float>}|null
     */
    public function getCensusProfile(string $dguid, ?string $profileBaseUrl = null, string $level = 'da'): ?array
    {
        $base = rtrim((string) ($profileBaseUrl ?: config('census.statcan.profile_url')), '/');
        if ($base === '') {
            Log::warning('census.profile.missing_url');

            return null;
        }

        if (Cache::get('census:dguid:' . $dguid . ':v10:miss')) {
            return null;
        }

        // Charts first as complete groups (never split a tab across chunks), then metrics.
        // Counts-only (.1) — rates(1+2) previously doubled latency and starved remaining tabs.
        $chartGroups = $this->collectChartIdGroups();
        $chartIdsFlat = [];
        foreach ($chartGroups as $group) {
            foreach ($group as $id) {
                $chartIdsFlat[$id] = true;
            }
        }
        $metricIds = array_values(array_diff(
            array_values(array_unique(array_filter(array_map(
                'strval',
                array_values(config('census.characteristics', []))
            )))),
            array_keys($chartIdsFlat)
        ));
        if ($metricIds !== []) {
            $chartGroups[] = $metricIds;
        }

        if ($chartGroups === []) {
            return null;
        }

        $values = [];
        $rates = [];
        $started = microtime(true);
        $budgetSeconds = app()->environment('local') ? 85.0 : 95.0;
        if ($level === 'da') {
            $budgetSeconds = app()->environment('local') ? 8.0 : 32.0;
        }
        $chunkSize = $level === 'da' ? 28 : 70;
        $consecutiveFails = 0;
        $maxFails = $level === 'da' ? 1 : 2;

        // Pack whole chart groups into chunks so Age/Ethnicity/etc. stay complete.
        $chunks = [];
        $current = [];
        foreach ($chartGroups as $group) {
            $group = array_values(array_unique($group));
            if ($group === []) {
                continue;
            }
            if ($current !== [] && (count($current) + count($group)) > $chunkSize) {
                $chunks[] = $current;
                $current = [];
            }
            // Oversized single chart (e.g. language): send alone, never merge-split mid-group.
            if (count($group) > $chunkSize) {
                if ($current !== []) {
                    $chunks[] = $current;
                    $current = [];
                }
                $chunks[] = $group;
                continue;
            }
            $current = array_merge($current, $group);
        }
        if ($current !== []) {
            $chunks[] = $current;
        }

        foreach ($chunks as $chunk) {
            if ((microtime(true) - $started) >= $budgetSeconds) {
                Log::warning('census.profile.budget_stop', [
                    'dguid' => $dguid,
                    'level' => $level,
                    'elapsed' => round(microtime(true) - $started, 2),
                    'have' => count($values),
                ]);
                break;
            }

            $part = $this->fetchProfileChunk(
                $dguid,
                $chunk,
                $budgetSeconds - (microtime(true) - $started),
                $base,
                $level,
                false
            );
            if (! is_array($part) || (($part['counts'] ?? []) === [] && ($part['rates'] ?? []) === [])) {
                $consecutiveFails++;
                if ($consecutiveFails >= $maxFails) {
                    Log::warning('census.profile.abort_after_fails', [
                        'dguid' => $dguid,
                        'level' => $level,
                    ]);
                    break;
                }

                continue;
            }

            $consecutiveFails = 0;
            foreach (($part['counts'] ?? []) as $id => $val) {
                if (! array_key_exists($id, $values) || $values[$id] === null || $values[$id] === '') {
                    $values[$id] = $val;
                }
            }
        }

        // Small rates-only pass for religion (HouseSigma-compatible %) if time remains.
        $religionIds = ['1935', '1936', '1937', '1953', '1954', '1955', '1956', '1957', '1958', '1959'];
        $remaining = $budgetSeconds - (microtime(true) - $started);
        if ($remaining >= 8 && $values !== []) {
            $ratePart = $this->fetchProfileChunk(
                $dguid,
                $religionIds,
                $remaining,
                $base,
                $level,
                true
            );
            foreach (($ratePart['rates'] ?? []) as $id => $val) {
                if (is_numeric($val)) {
                    $rates[(string) $id] = (float) $val;
                }
            }
            foreach (($ratePart['counts'] ?? []) as $id => $val) {
                if (! array_key_exists($id, $values) || $values[$id] === null || $values[$id] === '') {
                    $values[$id] = $val;
                }
            }
        }

        if ($values === []) {
            Log::warning('census.profile.empty', ['dguid' => $dguid, 'level' => $level]);

            return null;
        }

        return ['values' => $values, 'rates' => $rates];
    }

    /**
     * @param  list<string>  $charIds
     * @return array{counts: array<string, float|int|string|null>, rates: array<string, float>}|null
     */
    protected function fetchProfileChunk(
        string $dguid,
        array $charIds,
        ?float $remainingBudget = null,
        ?string $profileBaseUrl = null,
        string $level = 'da',
        bool $withRates = false
    ): ?array {
        $base = rtrim((string) ($profileBaseUrl ?: config('census.statcan.profile_url')), '/');
        $charKey = implode('+', $charIds);
        // Counts (1) by default — faster. Rates (2) only for small religion pass.
        $statKey = $withRates ? '1+2' : '1';
        $url = $base . '/A5.' . $dguid . '.1.' . $charKey . '.' . $statKey;
        $configured = (int) config('census.statcan.timeout', 45);
        // ADA/CSD typically respond in 10–18s; DA either answers quickly or never (0-byte hang).
        $timeout = $level === 'da'
            ? (app()->environment('local') ? 6 : 12)
            : (app()->environment('local')
                ? max(14, min(26, $configured > 0 ? $configured : 26))
                : max(14, min(28, $configured > 0 ? $configured : 28)));
        if ($remainingBudget !== null) {
            $timeout = max(3, min($timeout, (int) floor($remainingBudget)));
        }
        $connectTimeout = app()->environment('local') ? 4 : 8;
        // Local Windows TLS inspection often breaks StatCan; always skip verify in local.
        $verify = app()->environment('local')
            ? false
            : (bool) config('census.statcan.geo_ssl_verify', true);

        try {
            $body = null;
            $lastError = null;

            if (function_exists('curl_init')) {
                $full = $url . '?detail=dataonly&format=jsondata';
                $ch = curl_init($full);
                $opts = [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Accept-Encoding: gzip, deflate',
                        'User-Agent: SerikRealtyCensus/1.0 (+https://serik.ca)',
                    ],
                    CURLOPT_ENCODING => '',
                    CURLOPT_SSL_VERIFYPEER => $verify,
                    CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
                ];
                if (defined('CURL_IPRESOLVE_V4')) {
                    $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
                }
                curl_setopt_array($ch, $opts);
                $raw = curl_exec($ch);
                $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $err = curl_error($ch);
                curl_close($ch);
                if ($raw !== false && $code >= 200 && $code < 300) {
                    $body = $raw;
                } else {
                    $lastError = $err !== '' ? $err : ('HTTP ' . $code);
                }
            } else {
                $response = Http::timeout($timeout)
                    ->acceptJson()
                    ->get($url, [
                        'detail' => 'dataonly',
                        'format' => 'jsondata',
                    ]);
                if ($response->successful()) {
                    $body = $response->body();
                } else {
                    $lastError = 'HTTP ' . $response->status();
                }
            }

            if ($body === null) {
                Log::warning('census.profile.chunk_failed', [
                    'dguid' => $dguid,
                    'level' => $level,
                    'error' => $lastError,
                    'chars' => count($charIds),
                ]);

                return null;
            }

            $json = json_decode($body, true);
            if (! is_array($json)) {
                Log::warning('census.profile.bad_json', ['dguid' => $dguid, 'level' => $level]);

                return null;
            }

            return $this->extractCharacteristicValues($json);
        } catch (Throwable $e) {
            Log::warning('census.profile.chunk_exception', [
                'dguid' => $dguid,
                'level' => $level,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array{counts: array<string, float|int|string|null>, rates: array<string, float>}
     */
    protected function extractCharacteristicValues(array $json): array
    {
        $structure = $json['data']['structures'][0] ?? $json['data']['structure'] ?? [];
        $seriesDims = $structure['dimensions']['series'] ?? [];
        if (! is_array($seriesDims) || $seriesDims === []) {
            return ['counts' => [], 'rates' => []];
        }

        $dimIds = [];
        $idByIndex = [];
        foreach ($seriesDims as $pos => $dim) {
            $dimIds[$pos] = (string) ($dim['id'] ?? '');
            foreach (($dim['values'] ?? []) as $idx => $v) {
                $idByIndex[$pos][(int) $idx] = (string) ($v['id'] ?? '');
            }
        }

        $charPos = array_search('CHARACTERISTIC', $dimIds, true);
        $statPos = array_search('STATISTIC', $dimIds, true);
        $genderPos = array_search('GENDER', $dimIds, true);
        if ($charPos === false) {
            return ['counts' => [], 'rates' => []];
        }

        $series = $json['data']['dataSets'][0]['series'] ?? [];
        $counts = [];
        $rates = [];

        foreach ($series as $key => $obs) {
            $parts = explode(':', (string) $key);
            if (! isset($parts[$charPos])) {
                continue;
            }

            if ($genderPos !== false && isset($parts[$genderPos])) {
                $genderId = $idByIndex[$genderPos][(int) $parts[$genderPos]] ?? '';
                if ($genderId !== '' && $genderId !== '1') {
                    continue;
                }
            }

            $charId = $idByIndex[$charPos][(int) $parts[$charPos]] ?? '';
            if ($charId === '') {
                continue;
            }

            $val = $obs['observations']['0'][0] ?? null;
            $statId = '1';
            if ($statPos !== false && isset($parts[$statPos])) {
                $statId = $idByIndex[$statPos][(int) $parts[$statPos]] ?? '1';
            }

            if ($statId === '2') {
                if (is_numeric($val)) {
                    $rates[$charId] = (float) $val;
                }
                continue;
            }

            // Default / statistic 1 = counts
            if ($statId === '' || $statId === '1') {
                $counts[$charId] = $val;
            }
        }

        return ['counts' => $counts, 'rates' => $rates];
    }

    /**
     * @param  array<string, float|int|string|null>  $values
     * @return array<int, array<string, mixed>>
     */
    public function calculateMetrics(array $values): array
    {
        $c = config('census.characteristics', []);

        $population = $this->num($values, $c['population_2021'] ?? '1');
        $averageAge = $this->num($values, $c['average_age'] ?? '39');
        $medianAge = $this->num($values, $c['median_age'] ?? '40');
        $hhSize = $this->num($values, $c['average_household_size'] ?? '56');
        $avgIncome = $this->num($values, $c['average_household_income'] ?? '238');
        $medianIncome = $this->num($values, $c['median_household_income'] ?? '229');

        $tenureTotal = $this->num($values, $c['tenure_total'] ?? '1400');
        $renter = $this->num($values, $c['renter'] ?? '1402');
        $rentersPct = $this->pct($renter, $tenureTotal);

        $immTotal = $this->num($values, $c['immigrant_status_total'] ?? '1513');
        $immigrants = $this->num($values, $c['immigrants'] ?? '1515');
        $immigrantsPct = $this->pct($immigrants, $immTotal);

        $condoTotal = $this->num($values, $c['condo_total'] ?? '1404');
        $condo = $this->num($values, $c['condominium'] ?? '1405');
        $condosPct = $this->pct($condo, $condoTotal);

        $eduTotal = $this->num($values, $c['education_25_64_total'] ?? '2014');
        $college = $this->num($values, $c['college_cegep'] ?? '2022');
        $uniBelow = $this->num($values, $c['university_below_bachelor'] ?? '2023');
        $bachelorsPlus = $this->num($values, $c['bachelors_or_higher'] ?? '2024');
        $collegeUni = null;
        if ($eduTotal !== null && $eduTotal > 0) {
            $num = ($college ?? 0) + ($uniBelow ?? 0) + ($bachelorsPlus ?? 0);
            // Only compute when at least one education component exists.
            if ($college !== null || $uniBelow !== null || $bachelorsPlus !== null) {
                $collegeUni = round(($num / $eduTotal) * 100, 1);
            }
        }

        $avgHome = $this->num($values, $c['average_dwelling_value'] ?? '1475');
        $medianHome = $this->num($values, $c['median_dwelling_value'] ?? '1474');

        $lowIncomePct = $this->num($values, $c['lim_at_prevalence'] ?? '331');
        if ($lowIncomePct === null) {
            $lowIncomePct = $this->pct(
                $this->num($values, $c['lim_at_in_low_income'] ?? '326'),
                $this->num($values, $c['lim_at_total'] ?? '321')
            );
        }

        $lfTotal = $this->num($values, $c['labour_force_total_15'] ?? '2223');
        $notInLf = $this->num($values, $c['not_in_labour_force'] ?? '2227');
        $notInLfPct = $this->pct($notInLf, $lfTotal);

        $maritalTotal = $this->num($values, $c['marital_status_total_15'] ?? '58');
        $neverMarried = $this->num($values, $c['never_married_not_common_law'] ?? '67');
        $singlePct = $this->pct($neverMarried, $maritalTotal);

        $hhTypeTotal = $this->num($values, $c['household_type_total'] ?? '100');
        $coupleWithChildren = $this->num($values, $c['couple_with_children'] ?? '103');
        $oneParent = $this->num($values, $c['one_parent_family_households'] ?? '105');
        $hhWithChildrenPct = null;
        if ($hhTypeTotal !== null && $hhTypeTotal > 0 && ($coupleWithChildren !== null || $oneParent !== null)) {
            $hhWithChildrenPct = round(((($coupleWithChildren ?? 0) + ($oneParent ?? 0)) / $hhTypeTotal) * 100, 1);
        }

        // Age label: prefer Average Age when characteristic 39 is present.
        $ageLabel = 'Average Age';
        $ageValue = $averageAge;
        if ($ageValue === null && $medianAge !== null) {
            $ageLabel = 'Median Age';
            $ageValue = $medianAge;
        }

        $incomeLabel = 'Average Household Income';
        $incomeValue = $avgIncome;
        if ($incomeValue === null && $medianIncome !== null) {
            $incomeLabel = 'Median Household Income';
            $incomeValue = $medianIncome;
        }

        $homeLabel = 'Average Home Value';
        $homeValue = $avgHome;
        if ($homeValue === null && $medianHome !== null) {
            $homeLabel = 'Median Home Value';
            $homeValue = $medianHome;
        }

        return [
            $this->metric('population_2021', 'Population 2021', $population, 'integer'),
            $this->metric('average_age', $ageLabel, $ageValue, 'decimal1'),
            $this->metric('household_average_size', 'Household Average Size', $hhSize, 'decimal1'),
            $this->metric('average_household_income', $incomeLabel, $incomeValue, 'currency'),
            $this->metric('renters', 'Renters', $rentersPct, 'percent'),
            $this->metric('immigrants', 'Immigrants', $immigrantsPct, 'percent'),
            $this->metric('condos', 'Condos', $condosPct, 'percent'),
            $this->metric('college_university', 'College/University Education', $collegeUni, 'percent'),
            $this->metric('average_home_value', $homeLabel, $homeValue, 'currency'),
            $this->metric('low_income', 'Low Income', $lowIncomePct, 'percent'),
            $this->metric('not_in_labour_force', 'Not in Labour Force', $notInLfPct, 'percent'),
            $this->metric('single', 'Single', $singlePct, 'percent'),
            $this->metric('households_with_children', 'Households with Children', $hhWithChildrenPct, 'percent'),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function collectChartCharacteristicIds(): array
    {
        $ids = [];
        foreach ($this->collectChartIdGroups() as $group) {
            foreach ($group as $id) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * One ID list per chart definition (keeps each tab fetchable as a unit).
     *
     * @return array<int, list<string>>
     */
    protected function collectChartIdGroups(): array
    {
        $groups = [];
        foreach (config('census.charts', []) as $chart) {
            $ids = [];
            if (! empty($chart['universe_id'])) {
                $ids[] = (string) $chart['universe_id'];
            }
            if (! empty($chart['slices']) && is_array($chart['slices'])) {
                foreach ($chart['slices'] as $slice) {
                    foreach (($slice['ids'] ?? []) as $id) {
                        $ids[] = (string) $id;
                    }
                }
            }
            if (! empty($chart['items']) && is_array($chart['items'])) {
                foreach ($chart['items'] as $item) {
                    if (! empty($item['id'])) {
                        $ids[] = (string) $item['id'];
                    }
                    foreach (($item['ids'] ?? []) as $id) {
                        $ids[] = (string) $id;
                    }
                }
            }
            $ids = array_values(array_unique(array_filter($ids)));
            if ($ids !== []) {
                $groups[] = $ids;
            }
        }

        return $groups;
    }

    /**
     * Build pie-chart datasets for the neighbourhood demographics UI.
     *
     * @param  array<string, float|int|string|null>  $values  Counts
     * @param  array<string, float>  $rates  Official StatCan rates (statistic=2)
     * @return array<int, array<string, mixed>>
     */
    public function buildCharts(array $values, array $rates = []): array
    {
        $colors = array_values(config('census.chart_colors', []));
        $charts = [];

        foreach (config('census.charts', []) as $key => $def) {
            $slices = $this->resolveChartSlices($def, $values);
            if ($slices === []) {
                continue;
            }

            $sliceSum = (float) array_sum(array_column($slices, 'value'));
            $universe = null;
            if (! empty($def['universe_id'])) {
                $universe = $this->num($values, (string) $def['universe_id']);
            }
            // Official StatCan total when it is a true parent of the slices; otherwise
            // fall back to the slice sum so percents stay internally consistent.
            $total = ($universe !== null && $universe > 0 && $sliceSum <= ($universe * 1.15))
                ? $universe
                : $sliceSum;
            if ($total <= 0) {
                continue;
            }

            $useOfficialRates = $this->chartHasOfficialRates($def, $rates);
            $outSlices = [];
            foreach ($slices as $i => $slice) {
                $count = (int) round($slice['value']);
                $officialRate = $useOfficialRates ? $this->sliceOfficialRate($def, $slice, $rates) : null;
                // Prefer StatCan rates (HouseSigma-compatible). Else count/universe.
                $pct = $officialRate !== null
                    ? round($officialRate, 1)
                    : round(($slice['value'] / $total) * 100, 1);
                $outSlices[] = [
                    'label' => $slice['label'],
                    'value' => $slice['value'],
                    'count' => $count,
                    'percent' => $pct,
                    'display' => number_format($pct, 1) . '% (' . number_format($count) . ')',
                    'color' => $colors[$i % max(1, count($colors))] ?? '#5B8DEF',
                ];
            }

            if ($useOfficialRates) {
                $rateSum = (float) array_sum(array_column($outSlices, 'percent'));
                if ($rateSum > 0 && ($rateSum < 85 || $rateSum > 115)) {
                    foreach ($outSlices as &$s) {
                        $s['percent'] = round(($s['value'] / $total) * 100, 1);
                        $s['display'] = number_format($s['percent'], 1) . '% (' . number_format($s['count']) . ')';
                    }
                    unset($s);
                }
            }

            $charts[] = [
                'key' => (string) $key,
                'label' => (string) ($def['label'] ?? $key),
                'slices' => $outSlices,
            ];
        }

        return $charts;
    }

    /**
     * @param  array<string, mixed>  $def
     * @param  array<string, float>  $rates
     */
    protected function chartHasOfficialRates(array $def, array $rates): bool
    {
        if ($rates === []) {
            return false;
        }
        $ids = [];
        foreach (($def['items'] ?? []) as $item) {
            if (! empty($item['id'])) {
                $ids[] = (string) $item['id'];
            }
            foreach (($item['ids'] ?? []) as $id) {
                $ids[] = (string) $id;
            }
        }
        foreach (($def['slices'] ?? []) as $slice) {
            foreach (($slice['ids'] ?? []) as $id) {
                $ids[] = (string) $id;
            }
        }
        $unique = array_values(array_unique($ids));
        if ($unique === []) {
            return false;
        }
        $hit = 0;
        foreach ($unique as $id) {
            if (isset($rates[$id])) {
                $hit++;
            }
        }

        return $hit >= max(2, (int) floor(count($unique) * 0.5));
    }

    /**
     * @param  array<string, mixed>  $def
     * @param  array{label: string, value: float}  $slice
     * @param  array<string, float>  $rates
     */
    protected function sliceOfficialRate(array $def, array $slice, array $rates): ?float
    {
        $label = (string) ($slice['label'] ?? '');
        foreach (($def['items'] ?? []) as $item) {
            if ((string) ($item['label'] ?? '') !== $label) {
                continue;
            }
            if (! empty($item['id']) && isset($rates[(string) $item['id']])) {
                return (float) $rates[(string) $item['id']];
            }
            $sum = 0.0;
            $any = false;
            foreach (($item['ids'] ?? []) as $id) {
                if (isset($rates[(string) $id])) {
                    $sum += (float) $rates[(string) $id];
                    $any = true;
                }
            }

            return $any ? $sum : null;
        }
        foreach (($def['slices'] ?? []) as $agg) {
            if ((string) ($agg['label'] ?? '') !== $label) {
                continue;
            }
            $sum = 0.0;
            $any = false;
            foreach (($agg['ids'] ?? []) as $id) {
                if (isset($rates[(string) $id])) {
                    $sum += (float) $rates[(string) $id];
                    $any = true;
                }
            }

            return $any ? $sum : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $def
     * @param  array<string, float|int|string|null>  $values
     * @return array<int, array{label: string, value: float}>
     */
    protected function resolveChartSlices(array $def, array $values): array
    {
        $mode = (string) ($def['mode'] ?? 'items');
        $slices = [];

        if ($mode === 'aggregate' && ! empty($def['slices'])) {
            foreach ($def['slices'] as $slice) {
                $sum = 0.0;
                $any = false;
                foreach (($slice['ids'] ?? []) as $id) {
                    $n = $this->num($values, (string) $id);
                    if ($n !== null) {
                        $sum += $n;
                        $any = true;
                    }
                }
                if ($any && $sum > 0) {
                    $slices[] = ['label' => (string) ($slice['label'] ?? ''), 'value' => $sum];
                }
            }

            return $slices;
        }

        foreach (($def['items'] ?? []) as $item) {
            $sum = 0.0;
            $any = false;
            if (! empty($item['id'])) {
                $n = $this->num($values, (string) $item['id']);
                if ($n !== null) {
                    $sum += $n;
                    $any = true;
                }
            }
            foreach (($item['ids'] ?? []) as $id) {
                $n = $this->num($values, (string) $id);
                if ($n !== null) {
                    $sum += $n;
                    $any = true;
                }
            }
            if ($any && $sum > 0) {
                $slices[] = ['label' => (string) ($item['label'] ?? ''), 'value' => $sum];
            }
        }

        if ($mode === 'top_n') {
            usort($slices, fn ($a, $b) => $b['value'] <=> $a['value']);
            $top = max(1, (int) ($def['top'] ?? 10));
            $slices = array_slice($slices, 0, $top);
        }

        return $slices;
    }

    /**
     * Retain a broader snapshot for future charts (by characteristic ID).
     *
     * @param  array<string, float|int|string|null>  $values
     * @return array<string, array<string, float|int|string|null>>
     */
    protected function buildCategorySnapshot(array $values): array
    {
        $ids = array_values(config('census.characteristics', []));
        $snap = [];
        foreach ($ids as $id) {
            if (array_key_exists($id, $values)) {
                $snap[$id] = $values[$id];
            }
        }

        return [
            'characteristic_ids' => $snap,
        ];
    }

    /**
     * @param  array<string, float|int|string|null>  $values
     */
    protected function num(array $values, string $id): ?float
    {
        if (! array_key_exists($id, $values) || $values[$id] === null || $values[$id] === '') {
            return null;
        }

        if (! is_numeric($values[$id])) {
            return null;
        }

        return (float) $values[$id];
    }

    protected function pct(?float $numerator, ?float $denominator): ?float
    {
        if ($numerator === null || $denominator === null || $denominator <= 0) {
            return null;
        }

        return round(($numerator / $denominator) * 100, 1);
    }

    /**
     * @return array<string, mixed>
     */
    protected function metric(string $key, string $label, ?float $value, string $format): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'display' => $this->formatValue($value, $format),
            'format' => $format,
            'available' => $value !== null,
        ];
    }

    protected function formatValue(?float $value, string $format): string
    {
        if ($value === null) {
            return 'N/A';
        }

        return match ($format) {
            'integer' => number_format((int) round($value)),
            'decimal1' => number_format($value, 1),
            'percent' => number_format($value, 1) . '%',
            'currency' => '$' . number_format((int) round($value)),
            default => (string) $value,
        };
    }

    protected function profilePageUrl(string $dguid): string
    {
        $base = (string) config('census.statcan.profile_page_url');

        return $base . '?' . http_build_query([
            'Lang' => 'E',
            'DGUIDlist' => $dguid,
            'GENDERlist' => '1,2,3',
            'STATISTIClist' => '1',
            'HEADERlist' => '0',
        ]);
    }

    protected function httpClient(bool $verify)
    {
        return Http::timeout((int) config('census.statcan.timeout', 45))
            ->withOptions(['verify' => $verify])
            ->withHeaders(['User-Agent' => 'SerikRealtyCensus/1.0 (+https://serik.ca)']);
    }

    protected function isCanadianCoordinate(float $lat, float $lng): bool
    {
        // Rough mainland + territories bounding box.
        return $lat >= 41.0 && $lat <= 84.0 && $lng >= -141.0 && $lng <= -52.0;
    }

    protected function toFloatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }
        $f = (float) $value;

        return $f == 0.0 ? null : $f;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function errorPayload(string $status, array $extra = []): array
    {
        $messages = [
            self::STATUS_MISSING_ADDRESS => 'Census data unavailable for this property.',
            self::STATUS_GEOCODE_UNAVAILABLE => 'Census data unavailable for this property.',
            self::STATUS_GEOGRAPHY_UNAVAILABLE => 'Census geography unavailable.',
            self::STATUS_CENSUS_UNAVAILABLE => 'Census data temporarily unavailable.',
        ];

        return array_merge([
            'status' => $status,
            'message' => $messages[$status] ?? 'Census data temporarily unavailable.',
            'metrics' => [],
            'source' => 'Statistics Canada — 2021 Census',
        ], $extra);
    }
}
