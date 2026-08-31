<?php

namespace App\Services\Seo;

use App\Models\Neighborhood;
use App\Models\NearbyCity;
use App\Support\HomepageResponseCache;
use App\Support\SeoLandingUrl;
use Botble\Location\Models\City;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Theme\homzen\Supports\TrebPropertyHelper;

final class CityNavigationService
{
    public function __construct(
        private readonly CityResolutionService $cityResolution,
    ) {
    }

    /**
     * @return array{
     *   layout: string,
     *   context: string,
     *   current_city: ?City,
     *   current_community: ?string,
     *   sections: array<int, array{title: string, subtitle?: string, links: array<int, array{label: string, url: string}>}>
     * }
     */
    public function build(string $context = 'home', ?City $city = null, ?string $community = null): array
    {
        $community = trim((string) $community);

        if ($context === 'home') {
            $city ??= $this->cityResolution->resolveForHome();
            $slug = $city?->slug ?? 'ontario';
            $ttl = (int) config('seo_navigation.cache_ttl', 3600);

            return Cache::remember("seo_nav:v10:home:{$slug}", $ttl, fn () => $this->buildHomeLayout($city));
        }

        $city ??= $this->cityResolution->resolve()
            ?? $this->cityResolution->resolveForHome();
        $slug = $city?->slug ?? 'ontario';
        $ttl = (int) config('seo_navigation.cache_ttl', 3600);
        $communityKey = $community !== '' ? md5(mb_strtolower($community)) : 'none';

        return Cache::remember(
            "seo_nav:v9:properties:{$slug}:{$communityKey}",
            $ttl,
            fn () => $this->buildPropertiesLayout($city, $community !== '' ? $community : null)
        );
    }

    public function bustCache(?string $citySlug = null): void
    {
        if ($citySlug) {
            foreach ([
                'v2:home', 'v2:properties', 'v3:home', 'v3:properties',
                'v5:home', 'v5:properties', 'v6:home', 'v6:properties',
                'v7:home', 'v7:properties', 'v8:home', 'v8:properties',
                'neighborhoods', 'nearby', 'popular_cities',
            ] as $prefix) {
                Cache::forget("seo_nav:{$prefix}:{$citySlug}");
            }
            Cache::forget("seo_nav_html:v1:properties:{$citySlug}");
            Cache::forget("seo_nav_html:v2:home:{$citySlug}");
            Cache::forget("seo_nav_html:v3:properties:{$citySlug}");
            Cache::forget("seo_nav_html:v5:home:{$citySlug}:none");
            Cache::forget("seo_nav_html:v6:home:{$citySlug}:none");
            Cache::forget("seo_nav_html:v7:home:{$citySlug}:none");
            Cache::forget("seo_nav_html:v8:home:{$citySlug}:none");
            Cache::forget("seo_nav_html:v7:properties:{$citySlug}:none");
            Cache::forget("seo_nav_html:v8:properties:{$citySlug}:none");
        }

        Cache::forget('seo_nav:v2:ontario_active_cities');
        Cache::forget('seo_nav:v2:ontario_sold_cities');
        Cache::forget('seo_nav:v3:ontario_active_cities');
        Cache::forget('seo_nav:v3:ontario_sold_cities');
        Cache::forget('seo_nav:v4:ontario_active_cities');
        Cache::forget('seo_nav:v4:ontario_sold_cities');
        Cache::forget('seo_nav:v5:ontario_active_cities');
        Cache::forget('seo_nav:v5:ontario_sold_cities');
        Cache::forget('seo_nav:v5:popular_real_estate_cities');
        Cache::forget('seo_nav:v6:popular_real_estate_cities');

        try {
            HomepageResponseCache::bump();
            HomepageResponseCache::bumpDataOnly();
        } catch (\Throwable) {
            // ignore during early boot
        }
    }

    public function cityLandingUrl(?City $city): string
    {
        return SeoLandingUrl::city($city);
    }

    public function neighborhoodUrl(?City $city, string $communityName): string
    {
        return SeoLandingUrl::community($city, $communityName);
    }

    /**
     * @return array{layout: string, context: string, current_city: ?City, sections: array}
     */
    private function buildHomeLayout(?City $ipCity): array
    {
        return [
            'layout' => 'home',
            'context' => 'home',
            'current_city' => $ipCity,
            'sections' => [
                [
                    'title' => __('Active Properties'),
                    'links' => $this->activeOntarioCityLinks($ipCity),
                ],
                [
                    'title' => __('Sold Properties'),
                    'links' => $this->soldOntarioCityLinks($ipCity),
                ],
                [
                    'title' => __('Popular Searches'),
                    'links' => $this->homePopularSearches($ipCity),
                ],
                [
                    'title' => __('Neighborhoods'),
                    'links' => $this->homeNeighborhoodLinks($ipCity),
                ],
            ],
        ];
    }

    /**
     * @return array{layout: string, context: string, current_city: ?City, current_community: ?string, sections: array}
     */
    private function buildPropertiesLayout(?City $city, ?string $community = null): array
    {
        // Neighborhood selected (Zolo-style): Property Types + Nearby Neighbourhoods.
        if ($community !== null && $community !== '') {
            return [
                'layout' => 'properties',
                'context' => 'properties',
                'current_city' => $city,
                'current_community' => $community,
                'sections' => [
                    [
                        'title' => __('Property Types'),
                        'links' => $this->communityPropertyTypeLinks($city, $community),
                    ],
                    [
                        'title' => __('Nearby Neighbourhoods'),
                        'links' => $this->nearbyNeighborhoodLinks($city, $community),
                    ],
                    [
                        'title' => __('Nearby Cities'),
                        'links' => $this->nearbyCityLinks($city),
                    ],
                    [
                        'title' => __('Popular Cities'),
                        'links' => $this->popularCityLinks($city),
                    ],
                ],
            ];
        }

        return [
            'layout' => 'properties',
            'context' => 'properties',
            'current_city' => $city,
            'current_community' => null,
            'sections' => [
                [
                    'title' => __('Popular Searches'),
                    'links' => $this->propertiesPopularSearches($city),
                ],
                [
                    'title' => __('Neighborhoods'),
                    'links' => $this->neighborhoodLinks($city),
                ],
                [
                    'title' => __('Nearby Cities'),
                    'links' => $this->nearbyCityLinks($city),
                ],
                [
                    'title' => __('Popular Cities'),
                    'links' => $this->popularCityLinks($city),
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function communityPropertyTypeLinks(?City $city, string $community): array
    {
        return [
            $this->link("{$community} Houses", SeoLandingUrl::community($city, $community)),
            $this->link("{$community} Townhouses", SeoLandingUrl::communityTownhouses($city, $community)),
            $this->link("{$community} Condos", SeoLandingUrl::communityCondos($city, $community)),
            $this->link("{$community} Houses For Rent", SeoLandingUrl::communityHousesForLease($city, $community)),
            $this->link("{$community} Townhouses For Rent", SeoLandingUrl::communityTownhousesForLease($city, $community)),
            $this->link("{$community} Apartments For Rent", SeoLandingUrl::communityApartmentsForLease($city, $community)),
            $this->link(
                "{$community} Studio Apartments For Rent",
                SeoLandingUrl::communityApartmentsForLease($city, $community, ['k' => 'studio'])
            ),
            $this->link(
                "{$community} 1 Bedroom Apartments For Rent",
                SeoLandingUrl::communityApartmentsForLease($city, $community, ['bedroom' => 1])
            ),
            $this->link(
                "{$community} 2 Bedroom Apartments For Rent",
                SeoLandingUrl::communityApartmentsForLease($city, $community, ['bedroom' => 2])
            ),
        ];
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function nearbyNeighborhoodLinks(?City $city, string $community): array
    {
        $links = [];

        foreach ($this->nearbyNeighborhoodsFor($city, $community) as $neighborhood) {
            $links[] = $this->link(
                (string) $neighborhood->name,
                SeoLandingUrl::community($city, (string) $neighborhood->name)
            );
        }

        return $links;
    }

    /**
     * Homepage col 3 — IP city popular searches.
     *
     * @return array<int, array{label: string, url: string}>
     */
    private function homePopularSearches(?City $city): array
    {
        $prefix = $city?->name ?? 'Ontario';

        return [
            $this->link('Ontario houses for sale', SeoLandingUrl::housesForSale(null)),
            $this->link("{$prefix} open houses for sale", SeoLandingUrl::openHouses($city)),
            $this->link("{$prefix} houses for sale", SeoLandingUrl::housesForSale($city)),
            $this->link("{$prefix} townhouses for sale", SeoLandingUrl::townhousesForSale($city)),
            $this->link("{$prefix} condos for sale", SeoLandingUrl::condosForSale($city)),
        ];
    }

    /**
     * Homepage col 4 — IP city + its neighborhoods ("… houses for sale").
     *
     * @return array<int, array{label: string, url: string}>
     */
    private function homeNeighborhoodLinks(?City $city): array
    {
        $links = [];

        if ($city) {
            $links[] = $this->link(
                "{$city->name} houses for sale",
                SeoLandingUrl::housesForSale($city)
            );
        }

        foreach ($this->neighborhoodsForCity($city) as $neighborhood) {
            $links[] = $this->link(
                "{$neighborhood->name} houses for sale",
                SeoLandingUrl::community($city, $neighborhood->name)
            );
        }

        if (count($links) <= 1) {
            $fallbackSlug = (string) config('seo_navigation.default_home_city_slug', 'toronto');
            if (! $city || strcasecmp((string) $city->slug, $fallbackSlug) !== 0) {
                $fallbackCity = $this->citiesBySlugs([$fallbackSlug])->first();
                if ($fallbackCity) {
                    foreach ($this->neighborhoodsForCity($fallbackCity) as $neighborhood) {
                        $links[] = $this->link(
                            "{$neighborhood->name} houses for sale",
                            SeoLandingUrl::community($fallbackCity, $neighborhood->name)
                        );
                    }
                }
            }
        }

        $unique = [];
        foreach ($links as $link) {
            $unique[$link['url']] = $link;
        }

        return array_values($unique);
    }

    /**
     * Featured / properties col 4 — current location city.
     *
     * @return array<int, array{label: string, url: string}>
     */
    private function propertiesPopularSearches(?City $city): array
    {
        $prefix = $city?->name ?? 'Ontario';

        return [
            $this->link('Ontario houses for sale', SeoLandingUrl::ontarioRealEstate()),
            $this->link("{$prefix} open houses for sale", SeoLandingUrl::openHouses($city)),
            $this->link("{$prefix} houses for sale", SeoLandingUrl::housesForSale($city)),
            $this->link("{$prefix} townhouses for sale", SeoLandingUrl::townhousesForSale($city)),
            $this->link("{$prefix} condos for sale", SeoLandingUrl::condosForSale($city)),
        ];
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function activeOntarioCityLinks(?City $near = null): array
    {
        $ttl = (int) config('seo_navigation.cache_ttl', 3600);
        $origin = $near?->slug ?? 'ontario';

        return Cache::remember("seo_nav:v6:ontario_active_cities:{$origin}", $ttl, function () use ($near) {
            $links = [];

            foreach ($this->citiesNearFirst(config('seo_navigation.ontario_active_cities', []), $near) as $city) {
                $links[] = $this->link(
                    "{$city->name} houses for sale",
                    SeoLandingUrl::housesForSale($city)
                );
            }

            return $links;
        });
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function soldOntarioCityLinks(?City $near = null): array
    {
        $ttl = (int) config('seo_navigation.cache_ttl', 3600);
        $origin = $near?->slug ?? 'ontario';

        return Cache::remember("seo_nav:v6:ontario_sold_cities:{$origin}", $ttl, function () use ($near) {
            $links = [];

            foreach ($this->citiesNearFirst(config('seo_navigation.ontario_sold_cities', []), $near) as $city) {
                $links[] = $this->link(
                    "{$city->name} sold houses",
                    SeoLandingUrl::soldHomes($city)
                );
            }

            return $links;
        });
    }

    /**
     * Homepage col 4 — national/popular "{City} Real Estate" links (config-driven, no DB).
     *
     * @return array<int, array{label: string, url: string}>
     */
    private function popularRealEstateCityLinks(): array
    {
        $ttl = (int) config('seo_navigation.cache_ttl', 3600);

        return Cache::remember('seo_nav:v6:popular_real_estate_cities', $ttl, function () {
            $links = [];

            foreach (config('seo_navigation.popular_real_estate_cities', []) as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $slug = trim((string) ($row['slug'] ?? ''));
                if ($name === '' || $slug === '') {
                    continue;
                }

                $links[] = $this->link(
                    "{$name} houses for sale",
                    SeoLandingUrl::url($slug . '-houses-for-sale')
                );
            }

            return $links;
        });
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function neighborhoodLinks(?City $city): array
    {
        $links = [];

        foreach ($this->neighborhoodsForCity($city) as $neighborhood) {
            $links[] = $this->link(
                "{$neighborhood->name} houses for sale",
                SeoLandingUrl::community($city, $neighborhood->name)
            );
        }

        return $links;
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function nearbyCityLinks(?City $city): array
    {
        $links = [];

        foreach ($this->nearbyCitiesFor($city) as $nearbyCity) {
            $links[] = $this->link(
                "{$nearbyCity->name} houses for sale",
                SeoLandingUrl::housesForSale($nearbyCity)
            );
        }

        return $links;
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function popularCityLinks(?City $excludeCity): array
    {
        $links = [];

        foreach ($this->popularCities($excludeCity) as $popularCity) {
            $links[] = $this->link(
                "{$popularCity->name} houses for sale",
                SeoLandingUrl::housesForSale($popularCity)
            );
        }

        return $links;
    }

    /**
     * @param  array<int, string>  $slugs
     * @return Collection<int, City>
     */
    private function citiesBySlugs(array $slugs): Collection
    {
        if ($slugs === []) {
            return collect();
        }

        $cities = City::query()
            ->where('is_active', true)
            ->whereIn('slug', $slugs)
            ->get(['id', 'name', 'slug', 'property_count', 'latitude', 'longitude', 'is_major']);

        return collect($slugs)
            ->map(fn (string $slug) => $cities->firstWhere('slug', $slug))
            ->filter()
            ->values();
    }

    /**
     * @param  array<int, string>  $slugs
     * @return Collection<int, City>
     */
    private function citiesNearFirst(array $slugs, ?City $near): Collection
    {
        $cities = $this->citiesBySlugs($slugs);
        if ($cities->isEmpty() || ! $near) {
            return $cities;
        }

        $originLat = (float) ($near->latitude ?? 0);
        $originLng = (float) ($near->longitude ?? 0);
        if ($originLat === 0.0 && $originLng === 0.0) {
            return $cities;
        }

        return $cities
            ->sortBy(function (City $city) use ($near, $originLat, $originLng) {
                if ((int) $city->id === (int) $near->id || strcasecmp((string) $city->slug, (string) $near->slug) === 0) {
                    return -1;
                }

                $lat = (float) ($city->latitude ?? 0);
                $lng = (float) ($city->longitude ?? 0);
                if ($lat === 0.0 && $lng === 0.0) {
                    return 99999;
                }

                return $this->haversineKm($originLat, $originLng, $lat, $lng);
            })
            ->values();
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** @return Collection<int, Neighborhood> */
    private function neighborhoodsForCity(?City $city): Collection
    {
        if (! $city) {
            return collect();
        }

        $ttl = (int) config('seo_navigation.cache_ttl', 3600);
        $limit = (int) config('seo_navigation.neighborhood_limit', 10);

        return Cache::remember("seo_nav:neighborhoods:v4:{$city->slug}:{$limit}", $ttl, function () use ($city, $limit) {
            $owned = Neighborhood::query()
                ->where('city_id', $city->id)
                ->orderByDesc('property_count')
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'city_id', 'name', 'slug', 'property_count', 'latitude', 'longitude']);

            if ($owned->isNotEmpty()) {
                return $owned;
            }

            // Former municipalities (North York, Scarborough, Etobicoke, …) store
            // MLS City as "Toronto C15" etc. — pull communities from those districts.
            return $this->neighborhoodsFromTrebDistricts($city, $limit);
        });
    }

    /**
     * Build neighborhood rows from TREB district codes when the city has none synced.
     *
     * @return Collection<int, Neighborhood>
     */
    private function neighborhoodsFromTrebDistricts(City $city, int $limit): Collection
    {
        $districts = config('seo_navigation.treb_city_districts.' . $city->slug, []);
        if (! is_array($districts) || $districts === []) {
            return collect();
        }

        $districts = array_values(array_unique(array_map('strval', $districts)));
        $placeholders = implode(',', array_fill(0, count($districts), '?'));

        try {
            $rows = DB::table('meta_boxes as mb')
                ->join('re_properties as p', 'p.id', '=', 'mb.reference_id')
                ->where('mb.meta_key', 'amp_snapshot')
                ->where('mb.reference_type', \Botble\RealEstate\Models\Property::class)
                ->whereRaw(
                    'JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.City")) IN (' . $placeholders . ')',
                    $districts
                )
                ->select([
                    DB::raw('JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.CityRegion")) as raw_region'),
                    DB::raw('AVG(CASE WHEN p.latitude != 0 AND p.longitude != 0 THEN p.latitude END) as avg_lat'),
                    DB::raw('AVG(CASE WHEN p.latitude != 0 AND p.longitude != 0 THEN p.longitude END) as avg_lng'),
                    DB::raw('COUNT(*) as cnt'),
                ])
                ->groupBy('raw_region')
                ->orderByDesc('cnt')
                ->limit(max($limit * 4, 40))
                ->get();
        } catch (\Throwable) {
            return collect();
        }

        $out = collect();
        $seen = [];

        foreach ($rows as $row) {
            $name = TrebPropertyHelper::formatRegionLabel((string) ($row->raw_region ?? ''));
            if ($name === '' || preg_match('/^[A-Z]\d+$/i', $name)) {
                continue;
            }

            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $neighborhood = new Neighborhood([
                'city_id' => $city->id,
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'latitude' => is_numeric($row->avg_lat ?? null) ? (float) $row->avg_lat : null,
                'longitude' => is_numeric($row->avg_lng ?? null) ? (float) $row->avg_lng : null,
                'property_count' => (int) ($row->cnt ?? 0),
            ]);
            $out->push($neighborhood);

            if ($out->count() >= $limit) {
                break;
            }
        }

        return $out->values();
    }

    /** @return Collection<int, Neighborhood> */
    private function nearbyNeighborhoodsFor(?City $city, string $community): Collection
    {
        if (! $city) {
            return collect();
        }

        $ttl = (int) config('seo_navigation.cache_ttl', 3600);
        $limit = (int) config('seo_navigation.neighborhood_limit', 10);
        $cacheKey = 'seo_nav:nearby_neighborhoods:v2:' . $city->slug . ':' . md5(mb_strtolower($community));

        return Cache::remember($cacheKey, $ttl, function () use ($city, $community, $limit) {
            $pool = $this->neighborhoodsForCity($city);
            // Refresh a wider pool for distance sorting when city uses district fallback.
            if ($pool->count() < $limit) {
                $pool = $this->neighborhoodsFromTrebDistricts($city, max($limit * 4, 40));
                if ($pool->isEmpty()) {
                    $pool = Neighborhood::query()
                        ->where('city_id', $city->id)
                        ->orderByDesc('property_count')
                        ->orderBy('name')
                        ->limit(max($limit * 4, 40))
                        ->get(['id', 'city_id', 'name', 'slug', 'property_count', 'latitude', 'longitude']);
                }
            }

            $current = $pool->first(function (Neighborhood $n) use ($community) {
                return strcasecmp((string) $n->name, $community) === 0
                    || $n->slug === \Illuminate\Support\Str::slug($community);
            });

            $candidates = $pool
                ->filter(function (Neighborhood $n) use ($community, $current) {
                    if ($current && (int) ($n->id ?? 0) !== 0 && (int) $n->id === (int) $current->id) {
                        return false;
                    }

                    return strcasecmp((string) $n->name, $community) !== 0;
                })
                ->values();

            if (! $current || ! $current->latitude || ! $current->longitude) {
                return $candidates->take($limit)->values();
            }

            $lat = (float) $current->latitude;
            $lng = (float) $current->longitude;

            return $candidates
                ->map(function (Neighborhood $n) use ($lat, $lng) {
                    $nLat = (float) ($n->latitude ?? 0);
                    $nLng = (float) ($n->longitude ?? 0);
                    $n->setAttribute(
                        '_distance',
                        ($nLat && $nLng)
                            ? (($lat - $nLat) ** 2 + ($lng - $nLng) ** 2)
                            : PHP_FLOAT_MAX
                    );

                    return $n;
                })
                ->sortBy('_distance')
                ->take($limit)
                ->values();
        });
    }

    /** @return Collection<int, City> */
    private function nearbyCitiesFor(?City $city): Collection
    {
        if (! $city) {
            return collect();
        }

        $ttl = (int) config('seo_navigation.cache_ttl', 3600);

        return Cache::remember("seo_nav:nearby:v3:{$city->slug}", $ttl, function () use ($city) {
            $nearbyIds = NearbyCity::query()
                ->where('city_id', $city->id)
                ->orderBy('distance_km')
                ->limit((int) config('seo_navigation.nearby_city_limit', 10) * 3)
                ->pluck('nearby_city_id');

            if ($nearbyIds->isEmpty()) {
                return collect();
            }

            $limit = (int) config('seo_navigation.nearby_city_limit', 10);

            return City::query()
                ->whereIn('id', $nearbyIds)
                ->where('is_active', true)
                ->get(['id', 'name', 'slug', 'property_count', 'latitude', 'longitude', 'is_major'])
                ->filter(fn (City $c) => self::isDisplayableCityName((string) $c->name))
                ->sortBy(fn (City $c) => $nearbyIds->search($c->id))
                ->take($limit)
                ->values();
        });
    }

    /** @return Collection<int, City> */
    private function popularCities(?City $excludeCity = null): Collection
    {
        $excludeId = $excludeCity?->id;
        $excludeSlug = $excludeCity?->slug ?? 'none';
        $ttl = (int) config('seo_navigation.cache_ttl', 3600);
        $limit = (int) config('seo_navigation.popular_city_limit', 10);

        return Cache::remember("seo_nav:popular_cities:v3:{$excludeSlug}", $ttl, function () use ($excludeId, $limit) {
            return City::query()
                ->where('is_active', true)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->orderByDesc('property_count')
                ->orderBy('name')
                ->limit($limit * 4)
                ->get(['id', 'name', 'slug', 'property_count', 'latitude', 'longitude', 'is_major'])
                ->filter(fn (City $c) => self::isDisplayableCityName((string) $c->name))
                ->take($limit)
                ->values();
        });
    }

    /**
     * Hide TREB district codes mistakenly imported as cities (Toronto E01, C15, W08).
     */
    public static function isDisplayableCityName(string $name): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }

        return ! preg_match('/\b[CEW]\d{1,2}\b/i', $name);
    }

    /**
     * @return array{label: string, url: string}
     */
    private function link(string $label, string $url): array
    {
        return ['label' => $label, 'url' => $url];
    }
}
