<?php

namespace App\Providers;

use App\Services\Seo\CityNavigationService;
use App\Services\Seo\CityResolutionService;
use App\Services\Seo\CitySeoService;
use App\Support\HomepageResponseCache;
use Botble\RealEstate\Services\PropertySearchService;
use Botble\Theme\Facades\Theme;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class SeoNavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CityResolutionService::class);
        $this->app->singleton(CityNavigationService::class);
        $this->app->singleton(CitySeoService::class);
    }

    public function boot(): void
    {
        $this->registerPropertyFilters();
        $this->registerSeoHooks();
    }

    private function registerPropertyFilters(): void
    {
        add_filter('properties_filter_validation_rules', function (array $rules): array {
            $rules['community'] = 'nullable|string|max:255';
            $rules['status'] = 'nullable|string|in:sold,active';
            $rules['open_house'] = 'nullable|boolean';

            return $rules;
        });

        add_filter('properties_filter_query', function ($query, array $filters) {
            $community = trim((string) ($filters['community'] ?? ''));
            if ($community === '') {
                return $query;
            }

            $city = trim((string) ($filters['location'] ?? ''));
            if ($city === '' && ! empty($filters['city_id'])) {
                $cityModel = \Botble\Location\Models\City::query()
                    ->where('id', (int) $filters['city_id'])
                    ->value('name');
                $city = (string) ($cityModel ?? '');
            }

            $search = app(PropertySearchService::class);
            $ids = $search->searchCommunityIds($community, $city !== '' ? $city : null, 15000);

            if ($ids === []) {
                return $query->whereRaw('0 = 1');
            }

            return $query->whereIn('re_properties.id', $ids);
        }, 20, 2);

        add_filter('properties_filter_query', function ($query, array $filters) {
            if (($filters['status'] ?? '') !== 'sold') {
                return $query;
            }

            // Align with Property::isSoldHistory() — never treat ClosePrice alone as sold
            // (AMP stale close data was leaking active For Sale rows into Sold browse).
            $soldStatuses = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];

            return $query->whereIn('MlsStatus', $soldStatuses);
        }, 25, 2);

        add_filter('properties_filter_query', function ($query, array $filters) {
            $openHouse = $filters['open_house'] ?? null;
            if (! filter_var($openHouse, FILTER_VALIDATE_BOOLEAN)) {
                return $query;
            }

            $city = trim((string) ($filters['location'] ?? ''));
            $cacheKey = 'serik_open_house_ids_v4:' . md5(mb_strtolower($city !== '' ? $city : '_all'));

            try {
                $ids = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($city) {
                    $activeStatuses = [
                        'New',
                        'Active',
                        'Ext',
                        'Extension',
                        'Price Change',
                        'Active Under Contract',
                    ];

                    $search = app(PropertySearchService::class);

                    // Narrow candidates by city/district first (PK set), then scan
                    // description/content only inside that set — avoids Ontario-wide
                    // LONGTEXT LIKE which times out (IIS 500 on open_house=1).
                    $candidateIds = [];

                    if ($city !== '' && strcasecmp($city, 'ontario') !== 0) {
                        $districtIds = $search->searchDistrictCityIds($city, 8000);
                        if (is_array($districtIds) && $districtIds !== []) {
                            $candidateIds = $districtIds;
                        } else {
                            $meiliIds = $search->searchCityIds($city, 8000, [
                                'statuses' => $activeStatuses,
                            ]);
                            if (is_array($meiliIds) && $meiliIds !== []) {
                                $candidateIds = $meiliIds;
                            }
                        }
                    }

                    if ($candidateIds === []) {
                        $candidates = \Botble\RealEstate\Models\Property::query()
                            ->select('re_properties.id')
                            ->whereIn('MlsStatus', $activeStatuses);

                        if ($city !== '' && strcasecmp($city, 'ontario') !== 0) {
                            if (! $search->applyCityLocationConstraint($candidates, $city)) {
                                return [];
                            }
                        } else {
                            // Province-wide open house: cap to newest actives only.
                            $maxId = (int) \Botble\RealEstate\Models\Property::query()->max('id');
                            if ($maxId > 80000) {
                                $candidates->where('re_properties.id', '>=', $maxId - 80000);
                            }
                        }

                        $candidateIds = $candidates
                            ->orderByDesc('re_properties.id')
                            ->limit(8000)
                            ->pluck('id')
                            ->map(static fn ($id) => (int) $id)
                            ->all();
                    }

                    if ($candidateIds === []) {
                        return [];
                    }

                    return \Botble\RealEstate\Models\Property::query()
                        ->select('re_properties.id')
                        ->whereIn('re_properties.id', $candidateIds)
                        ->where(function ($q): void {
                            $q->where('description', 'like', '%open house%')
                                ->orWhere('content', 'like', '%open house%');
                        })
                        ->orderByDesc('re_properties.id')
                        ->limit(4000)
                        ->pluck('id')
                        ->map(static fn ($id) => (int) $id)
                        ->all();
                });
            } catch (\Throwable $e) {
                report($e);
                $ids = [];
            }

            return $ids === []
                ? $query->whereRaw('0 = 1')
                : $query->whereIn('re_properties.id', $ids);
        }, 30, 2);
    }

    private function registerSeoHooks(): void
    {
        Event::listen(RouteMatched::class, function (RouteMatched $event): void {
            $request = $event->request;

            if (! $request->isMethod('GET')) {
                return;
            }

            if ($request->routeIs('public.properties', 'public.properties-by-city', 'public.ajax.properties', 'public.seo.ontario')) {
                app(CitySeoService::class)->apply($request);
            }
        });

        // Homepage Popular Searches mount lives in style-5 (under Sold History).
        // Do not append via PAGE_FILTER_FRONT_PAGE_CONTENT — that filter runs
        // before shortcodes expand and would duplicate the mount.

        if (defined('THEME_FRONT_HEADER')) {
            add_filter(THEME_FRONT_HEADER, function (?string $header): ?string {
                $request = request();
                if (! $request->routeIs('public.properties', 'public.properties-by-city', 'public.seo.ontario')) {
                    return $header;
                }

                $city = app(CityResolutionService::class)->resolve();
                if (! $city) {
                    return $header;
                }

                $canonical = app(CitySeoService::class)->canonicalUrl($city, $request);
                $schema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Properties', 'item' => route('public.properties')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $city->name, 'item' => $canonical],
                    ],
                ];

                $script = '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

                return ($header ?? '') . $script;
            }, 50);
        }
    }
}
