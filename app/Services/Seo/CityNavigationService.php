<?php

namespace App\Services\Seo;

use App\Models\Neighborhood;
use App\Models\NearbyCity;
use App\Support\HomepageResponseCache;
use App\Support\SeoLandingUrl;
use Botble\Location\Models\City;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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

            return Cache::remember("seo_nav:v5:home:{$slug}", $ttl, fn () => $this->buildHomeLayout($city));
        }

        $city ??= $this->cityResolution->resolve()
            ?? $this->cityResolution->resolveForHome();
        $slug = $city?->slug ?? 'ontario';
        $ttl = (int) config('seo_navigation.cache_ttl', 3600);
        $communityKey = $community !== '' ? md5(mb_strtolower($community)) : 'none';

        return Cache::remember(
            "seo_nav:v5:properties:{$slug}:{$communityKey}",
            $ttl,
            fn () => $this->buildPropertiesLayout($city, $community !== '' ? $community : null)
        );
    }

    public function bustCache(?string $citySlug = null): void
    {
        if ($citySlug) {
            foreach (['v2:home', 'v2:properties', 'v3:home', 'v3:properties', 'v5:home', 'v5:properties', 'neighborhoods', 'nearby', 'popular_cities'] as $prefix) {
                Cache::forget("seo_nav:{$prefix}:{$citySlug}");
            }
            Cache::forget("seo_nav_html:v1:properties:{$citySlug}");
            Cache::forget("seo_nav_html:v2:home:{$citySlug}");
            Cache::forget("seo_nav_html:v3:properties:{$citySlug}");
            Cache::forget("seo_nav_html:v5:home:{$citySlug}:none");
        }

        Cache::forget('seo_nav:v2:ontario_active_cities');
        Cache::forget('seo_nav:v2:ontario_sold_cities');
        Cache::forget('seo_nav:v3:ontario_active_cities');
        Cache::forget('seo_nav:v3:ontario_sold_cities');
        Cache::forget('seo_nav:v5:popular_real_estate_cities');

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
                    'title' => __('Popular Searches'),
                    'subtitle' => __('Active'),
                    'links' => $this->homePopularSearches($ipCity),
                ],
                [
                    'title' => __('Active'),
                    'subtitle' => __('Ontario'),
                    'links' => $this->activeOntarioCityLinks(),
                ],
                [
                    'title' => __('Sold'),
                    'subtitle' => __('Ontario'),
                    'links' => $this->soldOntarioCityLinks(),
                ],
                [
                    'title' => __('Popular Cities'),
                    'links' => $this->popularRealEstateCityLinks(),
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
                [
                    'title' => __('Popular Searches'),
                    'links' => $this->propertiesPopularSearches($city),
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
     * Homepage col 1 — IP city.
     *
     * @return array<int, array{label: string, url: string}>
     */
    private function homePopularSearches(?City $city): array
    {
        $prefix = $city?->name ?? 'Ontario';

        return [
            $this->link(__('House For Sale In Ontario'), SeoLandingUrl::housesForSale(null)),
            $this->link("{$prefix} Open Houses For Sale", SeoLandingUrl::openHouses($city)),
            $this->link("{$prefix} Houses For Sale", SeoLandingUrl::housesForSale($city)),
            $this->link("{$prefix} Townhouses For Sale", SeoLandingUrl::townhousesForSale($city)),
            $this->link("{$prefix} Condos For Sale", SeoLandingUrl::condosForSale($city)),
        ];
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
            $this->link(__('Ontario Real Estate'), SeoLandingUrl::ontarioRealEstate()),
            $this->link("{$prefix} Open Houses", SeoLandingUrl::openHouses($city)),
            $this->link("{$prefix} Houses", SeoLandingUrl::housesForSale($city)),
            $this->link("{$prefix} Townhouses", SeoLandingUrl::townhousesForSale($city)),
            $this->link("{$prefix} Condos", SeoLandingUrl::condosForSale($city)),
        ];
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function activeOntarioCityLinks(): array
    {
        $ttl = (int) config('seo_navigation.cache_ttl', 3600);

        return Cache::remember('seo_nav:v3:ontario_active_cities', $ttl, function () {
            $links = [];
            $index = 0;

            foreach ($this->citiesBySlugs(config('seo_navigation.ontario_active_cities', [])) as $city) {
                $label = $index < 3
                    ? __('House for Sale in :city', ['city' => $city->name])
                    : __('Home for Sale in :city', ['city' => $city->name]);
                $links[] = $this->link($label, SeoLandingUrl::housesForSale($city));
                $index++;
            }

            return $links;
        });
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function soldOntarioCityLinks(): array
    {
        $ttl = (int) config('seo_navigation.cache_ttl', 3600);

        return Cache::remember('seo_nav:v3:ontario_sold_cities', $ttl, function () {
            $links = [];

            foreach ($this->citiesBySlugs(config('seo_navigation.ontario_sold_cities', [])) as $city) {
                $links[] = $this->link(
                    __('Sold House for Sale in :city', ['city' => $city->name]),
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

        return Cache::remember('seo_nav:v5:popular_real_estate_cities', $ttl, function () {
            $links = [];

            foreach (config('seo_navigation.popular_real_estate_cities', []) as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                $slug = trim((string) ($row['slug'] ?? ''));
                if ($name === '' || $slug === '') {
                    continue;
                }

                $links[] = $this->link(
                    __(':city Real Estate', ['city' => $name]),
                    SeoLandingUrl::url('houses-for-sale-in-' . $slug)
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
        $index = 0;

        foreach ($this->neighborhoodsForCity($city) as $neighborhood) {
            $label = $index % 2 === 0
                ? "{$neighborhood->name} Houses for sale"
                : "{$neighborhood->name} Real Estate";
            $links[] = $this->link($label, SeoLandingUrl::community($city, $neighborhood->name));
            $index++;
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
                "{$nearbyCity->name} Houses For Sale",
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
                "{$popularCity->name} Houses For Sale",
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

    /** @return Collection<int, Neighborhood> */
    private function neighborhoodsForCity(?City $city): Collection
    {
        if (! $city) {
            return collect();
        }

        $ttl = (int) config('seo_navigation.cache_ttl', 3600);

        return Cache::remember("seo_nav:neighborhoods:{$city->slug}", $ttl, function () use ($city) {
            return Neighborhood::query()
                ->where('city_id', $city->id)
                ->orderByDesc('property_count')
                ->orderBy('name')
                ->limit((int) config('seo_navigation.neighborhood_limit', 10))
                ->get(['id', 'city_id', 'name', 'slug', 'property_count', 'latitude', 'longitude']);
        });
    }

    /** @return Collection<int, Neighborhood> */
    private function nearbyNeighborhoodsFor(?City $city, string $community): Collection
    {
        if (! $city) {
            return collect();
        }

        $ttl = (int) config('seo_navigation.cache_ttl', 3600);
        $limit = (int) config('seo_navigation.neighborhood_limit', 10);
        $cacheKey = 'seo_nav:nearby_neighborhoods:v1:' . $city->slug . ':' . md5(mb_strtolower($community));

        return Cache::remember($cacheKey, $ttl, function () use ($city, $community, $limit) {
            $current = Neighborhood::query()
                ->where('city_id', $city->id)
                ->where(function ($q) use ($community): void {
                    $q->where('name', $community)
                        ->orWhere('slug', \Illuminate\Support\Str::slug($community));
                })
                ->first(['id', 'city_id', 'name', 'slug', 'property_count', 'latitude', 'longitude']);

            $candidates = Neighborhood::query()
                ->where('city_id', $city->id)
                ->when($current, fn ($q) => $q->where('id', '!=', $current->id))
                ->when(! $current, fn ($q) => $q->where('name', '!=', $community))
                ->orderByDesc('property_count')
                ->orderBy('name')
                ->limit(max($limit * 4, 40))
                ->get(['id', 'city_id', 'name', 'slug', 'property_count', 'latitude', 'longitude']);

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
