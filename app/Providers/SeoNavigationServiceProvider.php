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
            $ids = $search->searchCommunityIds($community, $city !== '' ? $city : null, 5000);

            if ($ids === []) {
                return $query->whereRaw('0 = 1');
            }

            return $query->whereIn('re_properties.id', $ids);
        }, 20, 2);

        add_filter('properties_filter_query', function ($query, array $filters) {
            if (($filters['status'] ?? '') !== 'sold') {
                return $query;
            }

            $soldStatuses = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];

            return $query->where(function ($q) use ($soldStatuses): void {
                $q->whereIn('MlsStatus', $soldStatuses)
                    ->orWhere(function ($inner): void {
                        $inner->whereNotNull('ClosePrice')->where('ClosePrice', '>', 0);
                    });
            });
        }, 25, 2);

        add_filter('properties_filter_query', function ($query, array $filters) {
            $openHouse = $filters['open_house'] ?? null;
            if (! filter_var($openHouse, FILTER_VALIDATE_BOOLEAN)) {
                return $query;
            }

            $city = trim((string) ($filters['location'] ?? ''));
            $cacheKey = 'serik_open_house_ids_v3:' . md5(mb_strtolower($city !== '' ? $city : '_all'));

            $ids = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($city) {
                $activeStatuses = [
                    'New',
                    'Active',
                    'Ext',
                    'Extension',
                    'Price Change',
                    'Active Under Contract',
                ];

                // Prefer active inventory only — much smaller scan than all MLS history.
                $base = \Botble\RealEstate\Models\Property::query()
                    ->select('re_properties.id')
                    ->whereIn('MlsStatus', $activeStatuses)
                    ->where(function ($q): void {
                        $q->where('description', 'like', '%open house%')
                            ->orWhere('content', 'like', '%open house%');
                    })
                    ->orderByDesc('re_properties.id')
                    ->limit(4000);

                if ($city !== '' && strcasecmp($city, 'ontario') !== 0) {
                    $matched = (clone $base)
                        ->where('location', 'like', '%' . $city . '%')
                        ->pluck('id')
                        ->map(static fn ($id) => (int) $id)
                        ->all();

                    if ($matched !== []) {
                        return $matched;
                    }

                    $districtIds = app(PropertySearchService::class)->searchDistrictCityIds($city, 8000);
                    if (is_array($districtIds) && $districtIds !== []) {
                        return (clone $base)
                            ->whereIn('re_properties.id', $districtIds)
                            ->pluck('id')
                            ->map(static fn ($id) => (int) $id)
                            ->all();
                    }
                }

                return $base->pluck('id')->map(static fn ($id) => (int) $id)->all();
            });

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
