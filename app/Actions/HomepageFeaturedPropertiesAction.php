<?php

namespace App\Actions;

use App\Support\HomepageFeaturedCache;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Services\PropertySearchService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Theme\homzen\Supports\TrebPropertyHelper;
use Theme\homzen\Supports\VisitorCityHelper;
use Throwable;

/**
 * Homepage properties shortcode (style 5) — Meili-first, cached, never throws.
 */
class HomepageFeaturedPropertiesAction
{
    private const CACHE_SECONDS = 600;

    private const INACTIVE_STATUSES = [
        'Sold', 'Leased', 'Sold Conditional', 'Sold Conditional Escape',
        'Expired', 'Terminated', 'Suspended',
    ];

    private const SOLD_STATUSES = [
        'Sold', 'Leased', 'Sold Conditional', 'Sold Conditional Escape',
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
        $visitorCity = null;

        try {
            $visitorCity = class_exists(VisitorCityHelper::class)
                ? VisitorCityHelper::get()
                : null;
        } catch (Throwable) {
            $visitorCity = null;
        }

        $cityKey = $visitorCity ? strtolower((string) $visitorCity) : 'ontario';
        $version = HomepageFeaturedCache::version();
        $cacheKey = "homepage_featured_props_v4:{$version}:{$cityKey}:{$limit}";

        try {
            return Cache::remember($cacheKey, self::CACHE_SECONDS, function () use ($limit, $visitorCity) {
                $idPayload = $this->resolveIds($limit, $visitorCity);

                return [
                    'propertiesForSale' => $this->hydrate($idPayload['sale'] ?? [], $limit),
                    'propertiesSold' => $this->hydrate($idPayload['sold'] ?? [], $limit),
                    'visitorCity' => $visitorCity,
                ];
            });
        } catch (Throwable $e) {
            $this->safeLog('error', '[homepage-featured] FAILED: '.$e->getMessage());

            return [
                'propertiesForSale' => new Collection,
                'propertiesSold' => new Collection,
                'visitorCity' => $visitorCity,
            ];
        }
    }

    /**
     * @return array{sale: list<int>, sold: list<int>, source: string}
     */
    private function resolveIds(int $limit, ?string $visitorCity): array
    {
        try {
            $search = app(PropertySearchService::class);
            if ($search->isAvailable()) {
                $meiliOpts = [
                    'limit' => $limit,
                    'residential_only' => true,
                    'sort' => ['listing_contract_ts:desc'],
                ];

                if ($visitorCity && strcasecmp($visitorCity, 'ontario') !== 0 && strcasecmp($visitorCity, 'on') !== 0) {
                    $meiliOpts['city'] = ucwords(strtolower($visitorCity));
                }

                $saleIds = $search->searchIds('', array_merge($meiliOpts, [
                    'exclude_statuses' => self::INACTIVE_STATUSES,
                ]));

                $soldIds = $search->searchIds('', array_merge($meiliOpts, [
                    'status' => 'Sold',
                    'sort' => ['close_ts:desc', 'listing_contract_ts:desc'],
                ]));

                if ($saleIds === [] && isset($meiliOpts['city'])) {
                    unset($meiliOpts['city']);
                    $saleIds = $search->searchIds('', array_merge($meiliOpts, [
                        'exclude_statuses' => self::INACTIVE_STATUSES,
                    ]));
                    $soldIds = $search->searchIds('', array_merge($meiliOpts, [
                        'status' => 'Sold',
                        'sort' => ['close_ts:desc', 'listing_contract_ts:desc'],
                    ]));
                }

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
