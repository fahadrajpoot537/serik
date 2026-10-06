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
        $cacheKey = 'census:property:' . $propertyId . ':v3';

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['status'])) {
            return $cached;
        }

        $lock = Cache::lock('serik:cache:sf:' . md5($cacheKey), 60);

        try {
            $payload = $lock->block(20, function () use ($cacheKey, $ttl, $property) {
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

        $geo = $this->findDisseminationArea((float) $coords['lat'], (float) $coords['lng']);
        if ($geo === null) {
            return $this->errorPayload(self::STATUS_GEOGRAPHY_UNAVAILABLE, [
                'property_id' => (int) $property->getKey(),
                'address' => $address,
                'latitude' => $coords['lat'],
                'longitude' => $coords['lng'],
                'message' => 'Census geography unavailable.',
            ]);
        }

        $profile = $this->getCensusProfileCached((string) $geo['dguid']);
        if ($profile === null) {
            return $this->errorPayload(self::STATUS_CENSUS_UNAVAILABLE, [
                'property_id' => (int) $property->getKey(),
                'address' => $address,
                'latitude' => $coords['lat'],
                'longitude' => $coords['lng'],
                'dauid' => $geo['dauid'],
                'dguid' => $geo['dguid'],
                'pruid' => $geo['pruid'],
                'message' => 'Census data temporarily unavailable.',
            ]);
        }

        $metrics = $this->calculateMetrics($profile['values'] ?? []);
        $charts = $this->buildCharts($profile['values'] ?? []);
        $dguid = (string) $geo['dguid'];

        return [
            'status' => self::STATUS_OK,
            'message' => null,
            'property_id' => (int) $property->getKey(),
            'address' => $address,
            'latitude' => (float) $coords['lat'],
            'longitude' => (float) $coords['lng'],
            'dauid' => (string) $geo['dauid'],
            'dguid' => $dguid,
            'pruid' => (string) $geo['pruid'],
            'metrics' => $metrics,
            'charts' => $charts,
            'categories' => $this->buildCategorySnapshot($profile['values'] ?? []),
            'source' => 'Statistics Canada — 2021 Census',
            'source_url' => $this->profilePageUrl($dguid),
            'synced_at' => now()->toIso8601String(),
        ];
    }

    public function buildAddress(Property $property): string
    {
        $parts = [];

        $google = trim((string) ($property->google_formatted_address ?? ''));
        if ($google !== '') {
            return $this->sanitizeAddress($google);
        }

        try {
            if (class_exists(\Theme\homzen\Supports\TrebPropertyHelper::class)) {
                $helper = \Theme\homzen\Supports\TrebPropertyHelper::class;
                $listingKey = trim((string) ($property->external_id ?? ''));
                $localData = $helper::dbRowToLocalArray($property);
                $record = $listingKey !== ''
                    ? $helper::resolveFactRecordForDetail($listingKey, $localData)
                    : $localData;

                if (is_array($record) && $record !== []) {
                    $street = (string) $helper::formatDisplayAddress($record);
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
            $location = trim((string) ($property->location ?? ''));
            if ($location !== '') {
                $parts[] = $location;
            } else {
                $name = trim((string) ($property->name ?? ''));
                $city = trim((string) ($property->cityName ?? ''));
                $zip = trim((string) ($property->zip_code ?? ''));
                foreach ([$name, $city, $zip, 'Canada'] as $p) {
                    if ($p !== '') {
                        $parts[] = $p;
                    }
                }
            }
        }

        $joined = implode(', ', array_values(array_unique($parts)));

        return $this->sanitizeAddress($joined);
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
        $lat = $this->toFloatOrNull($property->latitude ?? null);
        $lng = $this->toFloatOrNull($property->longitude ?? null);

        if ($lat !== null && $lng !== null && $this->isCanadianCoordinate($lat, $lng)) {
            return ['lat' => $lat, 'lng' => $lng];
        }

        return $this->geocodeAddress($address);
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

        $url = (string) config('census.statcan.da_identify_url');
        if ($url === '') {
            Log::warning('census.da.missing_url');

            return null;
        }

        $verify = (bool) config('census.statcan.geo_ssl_verify', true);
        // Local Windows TLS inspection often breaks StatCan's cert chain.
        if (app()->environment('local') && ! $verify) {
            $verify = false;
        }

        $deltas = [0.0008, 0.002, 0.005];
        $lastError = null;

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

            $attrs = $results[0]['attributes'] ?? [];
            $dauid = (string) ($attrs['DAUID'] ?? $results[0]['value'] ?? '');
            $dguid = (string) ($attrs['DGUID'] ?? '');
            $pruid = (string) ($attrs['PRUID'] ?? '');

            if ($dguid === '' && preg_match('/^\d{8}$/', $dauid)) {
                $dguid = '2021S0512' . $dauid;
            }

            if ($dguid === '' || $dauid === '') {
                continue;
            }

            if ($pruid === '' && strlen($dauid) >= 2) {
                $pruid = substr($dauid, 0, 2);
            }

            $geo = [
                'dauid' => $dauid,
                'dguid' => $dguid,
                'pruid' => $pruid,
            ];

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
     * Prefer cURL for StatCan geo (Laravel HTTP + Accept: application/json is flaky with their ArcGIS adaptor).
     *
     * @param  array<string, scalar>  $params
     */
    protected function requestStatCanGeo(string $url, array $params, bool $verify, ?string &$lastError): ?string
    {
        $fullUrl = $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
        $timeout = max(15, (int) config('census.statcan.timeout', 45));

        if (function_exists('curl_init')) {
            $ch = curl_init($fullUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => $verify,
                CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
                CURLOPT_HTTPHEADER => ['User-Agent: SerikRealtyCensus/1.0 (+https://serik.ca)'],
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body === false || $code < 200 || $code >= 300 || ! is_string($body) || ! str_contains($body, 'results')) {
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
    public function getCensusProfileCached(string $dguid): ?array
    {
        $dguid = trim($dguid);
        if ($dguid === '') {
            return null;
        }

        $cacheKey = 'census:dguid:' . $dguid . ':v3';
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached['values'])) {
            return $cached;
        }

        $profile = $this->getCensusProfile($dguid);
        if ($profile === null) {
            return null;
        }

        Cache::put($cacheKey, $profile, max(3600, (int) config('census.cache_ttl', 2592000)));

        return $profile;
    }

    /**
     * @return array{values: array<string, float|int|string|null>}|null
     */
    public function getCensusProfile(string $dguid): ?array
    {
        $base = rtrim((string) config('census.statcan.profile_url'), '/');
        if ($base === '') {
            Log::warning('census.profile.missing_url');

            return null;
        }

        $charIds = array_values(array_unique(array_filter(array_map(
            'strval',
            array_merge(
                array_values(config('census.characteristics', [])),
                $this->collectChartCharacteristicIds()
            )
        ))));

        if ($charIds === []) {
            return null;
        }

        // Request only needed characteristics (full DF_DA profile is ~1MB and often times out).
        // SDMX multi-value syntax: id1+id2+id3
        $charKey = implode('+', $charIds);
        $url = $base . '/A5.' . rawurlencode($dguid) . '.1.' . $charKey . '.1';
        $timeout = max(60, (int) config('census.statcan.timeout', 45));

        try {
            $body = null;
            $lastError = null;

            if (function_exists('curl_init')) {
                $full = $url . '?detail=dataonly&format=jsondata';
                $ch = curl_init($full);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_CONNECTTIMEOUT => 20,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'User-Agent: SerikRealtyCensus/1.0 (+https://serik.ca)',
                    ],
                    CURLOPT_SSL_VERIFYPEER => ! app()->environment('local') || (bool) config('census.statcan.geo_ssl_verify', true),
                    CURLOPT_SSL_VERIFYHOST => (! app()->environment('local') || (bool) config('census.statcan.geo_ssl_verify', true)) ? 2 : 0,
                ]);
                // Local TLS inspection: prefer insecure when configured.
                if (app()->environment('local') && ! (bool) config('census.statcan.geo_ssl_verify', true)) {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                }
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
                Log::warning('census.profile.http_failed', [
                    'dguid' => $dguid,
                    'error' => $lastError,
                ]);

                return null;
            }

            $json = json_decode($body, true);
            if (! is_array($json)) {
                Log::warning('census.profile.bad_json', ['dguid' => $dguid]);

                return null;
            }

            $values = $this->extractCharacteristicValues($json);
            if ($values === []) {
                Log::warning('census.profile.empty', ['dguid' => $dguid]);

                return null;
            }

            return ['values' => $values];
        } catch (Throwable $e) {
            Log::warning('census.profile.exception', [
                'dguid' => $dguid,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, float|int|string|null>
     */
    protected function extractCharacteristicValues(array $json): array
    {
        $seriesDims = $json['data']['structures'][0]['dimensions']['series'] ?? [];
        $charDim = null;
        foreach ($seriesDims as $dim) {
            if (($dim['id'] ?? '') === 'CHARACTERISTIC') {
                $charDim = $dim;
                break;
            }
        }

        if (! is_array($charDim) || empty($charDim['values'])) {
            return [];
        }

        $idByIndex = [];
        foreach ($charDim['values'] as $idx => $v) {
            $idByIndex[$idx] = (string) ($v['id'] ?? '');
        }

        $series = $json['data']['dataSets'][0]['series'] ?? [];
        $out = [];

        foreach ($series as $key => $obs) {
            $parts = explode(':', (string) $key);
            // freq:geo:gender:characteristic:statistic
            if (count($parts) < 5) {
                continue;
            }
            $charIdx = (int) $parts[3];
            $charId = $idByIndex[$charIdx] ?? null;
            if ($charId === null || $charId === '') {
                continue;
            }
            $out[$charId] = $obs['observations']['0'][0] ?? null;
        }

        return $out;
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
        foreach (config('census.charts', []) as $chart) {
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
        }

        return $ids;
    }

    /**
     * Build pie-chart datasets for the neighbourhood demographics UI.
     *
     * @param  array<string, float|int|string|null>  $values
     * @return array<int, array<string, mixed>>
     */
    public function buildCharts(array $values): array
    {
        $colors = array_values(config('census.chart_colors', []));
        $charts = [];

        foreach (config('census.charts', []) as $key => $def) {
            $slices = $this->resolveChartSlices($def, $values);
            if ($slices === []) {
                continue;
            }

            $total = array_sum(array_column($slices, 'value'));
            if ($total <= 0) {
                continue;
            }

            $outSlices = [];
            foreach ($slices as $i => $slice) {
                $pct = round(($slice['value'] / $total) * 100, 1);
                $outSlices[] = [
                    'label' => $slice['label'],
                    'value' => $slice['value'],
                    'percent' => $pct,
                    'color' => $colors[$i % max(1, count($colors))] ?? '#5B8DEF',
                ];
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
