<?php

namespace App\Actions;

use App\Support\HomepageFeaturedCache;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Services\PropertySearchService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Theme\homzen\Supports\TrebPropertyHelper;
use Throwable;

/**
 * Homepage properties shortcode (style 5) — Meili-first, cached, never throws.
 */
class HomepageFeaturedPropertiesAction
{
    private const CACHE_SECONDS = 600;

    private static function cacheTtl(): int
    {
        return max(60, (int) config('serik.cache.featured_ttl', self::CACHE_SECONDS));
    }

    private const INACTIVE_STATUSES = [
        'Sold', 'Leased', 'Sold Conditional', 'Sold Conditional Escape', 'Leased Conditional',
        'Expired', 'Terminated', 'Suspended', 'Cancelled', 'Canceled', 'Withdrawn',
    ];

    private const SOLD_STATUSES = [
        'Sold', 'Leased', 'Sold Conditional', 'Sold Conditional Escape', 'Leased Conditional',
    ];

    /**
     * @return array{
     *   propertiesForSale: Collection,
     *   propertiesSold: Collection,
     *   visitorCity: ?string
     * }
     */
    public function handle(int $limit = 8): array
    {
        $limit = max(8, min(24, $limit));

        try {
            return $this->handleForLocation($limit, \App\Support\ResolvedLocation::ontarioFallback());
        } catch (Throwable $e) {
            $this->safeLog('error', '[homepage-featured] FAILED: '.$e->getMessage());

            return [
                'propertiesForSale' => new Collection,
                'propertiesSold' => new Collection,
                'visitorCity' => null,
                'locationLabel' => 'Ontario',
                'locationSource' => 'fallback',
            ];
        }
    }

    /**
     * Location-aware fetch used by the homepage AJAX hydrate (not shared full-page HTML).
     *
     * @return array{
     *   propertiesForSale: Collection,
     *   propertiesSold: Collection,
     *   visitorCity: ?string,
     *   locationLabel: string,
     *   locationSource: string
     * }
     */
    public function handleForLocation(int $limit, \App\Support\ResolvedLocation $location): array
    {
        $limit = max(8, min(24, $limit));
        $visitorCity = $location->isFallback() ? null : $location->city;
        if ($visitorCity && in_array(strtolower($visitorCity), ['ontario', 'on'], true)) {
            $visitorCity = null;
        }
        $lat = $location->latitude;
        $lng = $location->longitude;
        $cityKey = $visitorCity ? strtolower((string) $visitorCity) : 'ontario';
        $geoKey = round($lat, 2) . ':' . round($lng, 2);
        $version = HomepageFeaturedCache::version();
        $cacheKey = "homepage_featured_props_v7:{$version}:{$cityKey}:{$geoKey}:{$limit}";

        try {
            return \App\Support\SerikCache::remember($cacheKey, self::cacheTtl(), function () use ($limit, $visitorCity, $lat, $lng, $location) {
                $idPayload = $this->resolveIds($limit, $visitorCity, $lat, $lng);

                return [
                    'propertiesForSale' => $this->hydrate($idPayload['sale'] ?? [], $limit),
                    'propertiesSold' => $this->hydrate($idPayload['sold'] ?? [], $limit),
                    'visitorCity' => $visitorCity,
                    'locationLabel' => $location->publicLabel(),
                    'locationSource' => $location->source,
                ];
            });
        } catch (Throwable $e) {
            $this->safeLog('error', '[homepage-featured] FAILED: '.$e->getMessage());

            return [
                'propertiesForSale' => new Collection,
                'propertiesSold' => new Collection,
                'visitorCity' => $visitorCity,
                'locationLabel' => $location->publicLabel(),
                'locationSource' => $location->source,
            ];
        }
    }

    /**
     * @return array{sale: list<int>, sold: list<int>, source: string}
     */
    private function resolveIds(int $limit, ?string $visitorCity, ?float $lat = null, ?float $lng = null): array
    {
        try {
            $search = app(PropertySearchService::class);
            if ($search->isAvailable()) {
                $saleIds = $this->searchNear($search, false, $limit, $visitorCity, $lat, $lng);
                $soldIds = $this->searchNear($search, true, $limit, $visitorCity, $lat, $lng);

                if ($saleIds !== null || $soldIds !== null) {
                    return [
                        'sale' => array_values(array_filter(array_map('intval', $saleIds ?? []))),
                        'sold' => array_values(array_filter(array_map('intval', $soldIds ?? []))),
                        'source' => 'meilisearch',
                    ];
                }
            }
        } catch (Throwable $e) {
            $this->safeLog('warning', '[homepage-featured] Meili resolve failed: '.$e->getMessage());
        }

        return [
            'sale' => $this->mysqlIds(false, $limit, $visitorCity),
            'sold' => $this->mysqlIds(true, $limit, $visitorCity),
            'source' => 'mysql',
        ];
    }

    /**
     * @return list<int>
     */
    private function mysqlIds(bool $sold, int $limit, ?string $visitorCity): array
    {
        $q = Property::query()
            ->select(['id'])
            ->where('moderation_status', ModerationStatusEnum::APPROVED);

        if (class_exists(TrebPropertyHelper::class)) {
            $excluded = TrebPropertyHelper::excludedCommercialSubTypes();
            $q->where(function ($w) use ($excluded) {
                $w->whereNull('PropertySubType')
                    ->orWhereNotIn('PropertySubType', array_merge(
                        $excluded,
                        array_map(static fn ($v) => $v.' ', $excluded)
                    ));
            });
        }

        if ($sold) {
            $q->where(function ($w) {
                $w->whereIn('MlsStatus', self::SOLD_STATUSES)
                    ->orWhere('ClosePrice', '>', 0);
            });
        } else {
            $q->whereNotIn('MlsStatus', self::INACTIVE_STATUSES)
                ->where(function ($w) {
                    $w->whereNull('ClosePrice')->orWhere('ClosePrice', '<=', 0);
                });
        }

        if ($visitorCity && strcasecmp($visitorCity, 'ontario') !== 0 && strcasecmp($visitorCity, 'on') !== 0) {
            $city = str_replace(['%', '_'], '', $visitorCity);
            if ($city !== '') {
                $q->where('location', 'like', '%'.$city.'%');
            }
        }

        return $q->orderByDesc('id')
            ->limit($limit)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Progressive geo radius, then city name, then Ontario-wide.
     *
     * @return list<int>|null
     */
    private function searchNear(
        PropertySearchService $search,
        bool $sold,
        int $limit,
        ?string $visitorCity,
        ?float $lat,
        ?float $lng
    ): ?array {
        $base = [
            'limit' => $limit,
            'residential_only' => true,
        ];

        if ($sold) {
            $base['status'] = 'Sold';
            $base['sort'] = ['close_ts:desc', 'listing_contract_ts:desc'];
        } else {
            $base['exclude_statuses'] = self::INACTIVE_STATUSES;
            $base['transaction'] = 'For Sale';
            $base['sort'] = ['listing_contract_ts:desc'];
        }

        $radii = [15, 30, 60, 120];
        if ($lat !== null && $lng !== null && abs($lat) > 0.01 && abs($lng) > 0.01) {
            $geoSort = [sprintf('_geoPoint(%F, %F):asc', $lat, $lng)];
            if ($sold) {
                $geoSort[] = 'close_ts:desc';
            } else {
                $geoSort[] = 'listing_contract_ts:desc';
            }

            $best = null;
            foreach ($radii as $km) {
                $ids = $search->searchIds('', array_merge($base, [
                    'geo_lat' => $lat,
                    'geo_lng' => $lng,
                    'geo_radius_km' => $km,
                    'sort' => $geoSort,
                ]));
                if (! is_array($ids) || $ids === []) {
                    continue;
                }
                if (count($ids) >= $limit) {
                    return $ids;
                }
                $best = $ids;
            }

            if (is_array($best) && $best !== []) {
                return $best;
            }

            // Radius filter can miss sold/leased docs without _geo. Sort by
            // distance anyway so homepage sold is still nearest-first.
            $nearIds = $search->searchIds('', array_merge($base, [
                'sort' => $geoSort,
            ]));
            if (is_array($nearIds) && $nearIds !== []) {
                return $nearIds;
            }
        }

        if ($visitorCity && strcasecmp($visitorCity, 'ontario') !== 0 && strcasecmp($visitorCity, 'on') !== 0) {
            $ids = $search->searchIds('', array_merge($base, [
                'city' => ucwords(strtolower($visitorCity)),
            ]));
            if (is_array($ids) && $ids !== []) {
                return $ids;
            }
        }

        return $search->searchIds('', $base);
    }

    /**
     * @param  list<int>  $ids
     */
    private function hydrate(array $ids, int $limit): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return new Collection;
        }

        $ids = array_slice($ids, 0, $limit);
        $order = array_flip($ids);
        $relations = [...RealEstateHelper::getPropertyRelationsQuery(), 'author'];

        return Property::query()
            ->whereIn('id', $ids)
            ->with($relations)
            ->get()
            ->sortBy(static fn (Property $p) => $order[$p->id] ?? 9999)
            ->values();
    }

    private function safeLog(string $level, string $message): void
    {
        try {
            Log::{$level}($message);
        } catch (Throwable) {
            // IIS often locks daily log files — never break the homepage for that.
        }
    }
}
