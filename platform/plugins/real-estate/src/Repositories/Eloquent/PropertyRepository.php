<?php

namespace Botble\RealEstate\Repositories\Eloquent;

use Botble\Base\Models\BaseQueryBuilder;
use Botble\Language\Facades\Language;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Enums\PropertyTypeEnum;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Repositories\Interfaces\PropertyInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PropertyRepository extends RepositoriesAbstract implements PropertyInterface
{
    public function getRelatedProperties(int $propertyId, int $limit = 4, array $with = [], array $extra = []): Collection|LengthAwarePaginator
    {
        $limit = $limit > 1 ? $limit : 4;
        $currentProperty = $this->findById($propertyId, ['categories']);

        $this->model = $this->originalModel;

        // @phpstan-ignore-next-line
        $this->model = $this->model
            ->where('id', '<>', $propertyId)
            ->active();

        if ($currentProperty && $currentProperty->categories->count()) {
            $categoryIds = $currentProperty->categories->pluck('id')->toArray();

            $this->model
                ->whereHas('categories', function ($query) use ($categoryIds): void {
                    $query->whereIn('category_id', $categoryIds);
                })
                ->where('type', $currentProperty->type);
        }

        $params = array_merge([
            'condition' => [],
            'order_by' => [
                'created_at' => 'DESC',
            ],
            'take' => $limit,
            'with' => $with,
        ], $extra);

        return $this->advancedGet($params);
    }

    public function getProperties(array $filters = [], array $params = []): Collection|LengthAwarePaginator|Paginator
    {
        $filters = array_merge([
            'keyword' => null,
            'type' => null,
            'bedroom' => null,
            'bathroom' => null,
            'floor' => null,
            'min_square' => null,
            'max_square' => null,
            'min_price' => null,
            'max_price' => null,
            'project' => null,
            'project_id' => null,
            'category_id' => null,
            'author_id' => null,
            'city_id' => null,
            'city' => null,
            'state' => null,
            'state_id' => null,
            'location' => null,
            'zip_code' => null,
            'sort_by' => null,
            'features' => null,
            'home_types' => null,
        ], $filters);

        $isBrowseListing = request()->routeIs('public.properties', 'public.ajax.properties', 'public.ajax.properties.map')
            || request()->is('properties', 'properties/*');

        $orderBy = match ($filters['sort_by']) {
            'date_asc' => $isBrowseListing
                ? ['re_properties.id' => 'ASC']
                : [
                    'listing_modified_at' => 'ASC',
                    'created_at' => 'ASC',
                ],
            'price_asc' => [
                'price' => 'ASC',
            ],
            'price_desc' => [
                'price' => 'DESC',
            ],
            'name_asc' => [
                'name' => 'ASC',
            ],
            'name_desc' => [
                'name' => 'DESC',
            ],
            default => [
                're_properties.id' => 'DESC',
            ],
        };

        $params = array_merge([
            'condition' => [],
            'order_by' => [
                'created_at' => 'DESC',
            ],
            'take' => null,
            'paginate' => [
                'per_page' => 10,
                'current_paged' => 1,
            ],
            'select' => [
                '*',
            ],
            'with' => [],
        ], $params);

        // Initialize the model with active residential properties
        $this->model = $this->originalModel->active()->residential();

        if (
            request()->routeIs('public.properties', 'public.ajax.properties', 'public.ajax.properties.map')
            || request()->is('properties', 'properties/*')
        ) {
            $this->model = $this->model->mlsActive();
        }

        // Featured ordering forces a full-table filesort on 90k+ MLS rows — skip on browse listing.
        if (RealEstateHelper::isEnabledKeepFeaturedPropertiesOnTop() && ! $isBrowseListing) {
            // First sort by featured status (featured properties first)
            $this->model = $this->model->orderByDesc('is_featured');

            // Then sort by featured_priority only for properties where is_featured = 1
            $this->model = $this->model->orderByRaw('CASE WHEN is_featured = 1 THEN featured_priority ELSE 0 END DESC');
        }

        foreach ($orderBy as $column => $direction) {
            $this->model = $this->model->orderBy($column, $direction);
        }

        // @phpstan-ignore-next-line
        if ($filters['keyword'] !== null) {
            $keyword = trim((string) $filters['keyword']);
            $search = app(\Botble\RealEstate\Services\PropertySearchService::class);

            if ($keyword !== '' && $search->constrainQueryToKeyword($this->model, $keyword, 500)) {
                // Meili IDs applied via whereIn / empty-result guard.
            } elseif ($keyword !== '' && preg_match('/^[a-z]\d+$/i', $keyword)) {
                $ingested = app(\Botble\RealEstate\Services\LiveTrebPropertyFallbackService::class)
                    ->ingestByListingKey($keyword, true, false);

                if ($ingested) {
                    $this->model = $this->model->where('id', $ingested->id);
                } else {
                    // Meili down: sargable MLS key only — never LOWER LIKE on name/location.
                    $this->model = $this->model->where(function ($q) use ($keyword) {
                        $q->where('external_id', strtoupper($keyword))
                            ->orWhere('unique_id', strtoupper($keyword));
                    });
                }
            } elseif ($keyword !== '') {
                $this->model = $this->model->whereRaw('0 = 1');
            }
        }

        if ($filters['type'] !== null) {
            if ($filters['type'] == PropertyTypeEnum::SALE) {
                // MLS ingest often leaves type NULL; treat those as for-sale listings.
                $this->model = $this->model->where(function (Builder $query) {
                    $query->where('type', PropertyTypeEnum::SALE)->orWhereNull('type');
                });
            } else {
                $this->model = $this->model->where('type', $filters['type']);
            }
        }

        if (! empty($filters['home_types'])) {
            $subtypeMap = [
                'house' => ['Detached', 'Semi-Detached', 'Link', 'Rural Residential', 'Farm'],
                'condo' => ['Condo Apartment', 'Condo Townhouse', 'Detached Condo', 'Leasehold Condo', 'Common Element Condo', 'Co-Ownership Apartment'],
                'townhouse' => ['Att/Row/Townhouse', 'Condo Townhouse'],
            ];
            $subtypes = [];
            foreach ((array) $filters['home_types'] as $homeType) {
                if (isset($subtypeMap[$homeType])) {
                    $subtypes = array_merge($subtypes, $subtypeMap[$homeType]);
                }
            }
            $subtypes = array_values(array_unique($subtypes));
            if ($subtypes !== []) {
                $this->model = $this->model->whereIn('PropertySubType', $subtypes);
            }
        }

        if ($filters['bedroom']) {
            $this->model = $this->model->where('number_bedroom', '>=', $filters['bedroom']);
        }

        if ($filters['bathroom']) {
            $this->model = $this->model->where('number_bathroom', '>=', $filters['bathroom']);
        }

        if ($filters['floor']) {
            if ($filters['floor'] < 5) {
                $this->model = $this->model->where('number_floor', $filters['floor']);
            } else {
                $this->model = $this->model->where('number_floor', '>=', $filters['floor']);
            }
        }

        if ($filters['min_square'] !== null || $filters['max_square'] !== null) {
            $this->model = $this->model
                ->where(function (Builder $query) use ($filters) {
                    $minSquare = Arr::get($filters, 'min_square');
                    $maxSquare = Arr::get($filters, 'max_square');

                    if ($minSquare !== null) {
                        $query = $query->where('square', '>=', $minSquare);
                    }

                    if ($maxSquare !== null) {
                        $query = $query->where('square', '<=', $maxSquare);
                    }

                    return $query;
                });
        }

        if ($filters['min_price'] !== null || $filters['max_price'] !== null) {
            $this->model = $this->model
                ->where(function (Builder $query) use ($filters) {
                    $minPrice = Arr::get($filters, 'min_price');
                    $maxPrice = Arr::get($filters, 'max_price');

                    if ($minPrice !== null) {
                        $query = $query->where('price', '>=', $minPrice);
                    }

                    if ($maxPrice !== null) {
                        $query = $query->where('price', '<=', $maxPrice);
                    }

                    return $query;
                });
        }

        if ($filters['city'] !== null) {
            $this->model = $this->model->whereHas('city', function ($query) use ($filters): void {
                $query->where('slug', $filters['city']);
            });
        }

        if ($filters['state'] !== null) {
            $this->model = $this->model->whereHas('state', function ($query) use ($filters): void {
                $query->where('slug', $filters['state']);
            });
        }

        if ($filters['project'] !== null) {
            $this->model = $this->model->where(function (BaseQueryBuilder $query) use ($filters): void {
                $query
                    ->where('project_id', $filters['project'])
                    ->orWhereHas('project', function (BaseQueryBuilder $query) use ($filters): void {
                        $query->addSearch('re_projects.name', $filters['project'], false, false);
                    });
            });
        }

        if ($filters['project_id'] !== null) {
            $this->model = $this->model->where('project_id', $filters['project_id']);
        }

        if ($filters['author_id'] !== null) {
            $this->model = $this->model
                ->where('author_id', $filters['author_id'])
                ->where('author_type', Account::class);
        }

        if ($filters['category_id'] !== null) {
            $categoryIds = get_property_categories_related_ids($filters['category_id']);
            $this->model = $this->model
                ->whereHas('categories', function ($query) use ($categoryIds): void {
                    $query->whereIn('category_id', $categoryIds);
                });
        }

        if ($filters['state_id']) {
            $this->model = $this->model->where('state_id', $filters['state_id']);
        }

        if ($filters['city_id']) {
            $this->model = $this->model->where('city_id', $filters['city_id']);
        } elseif ($filters['location']) {
            $locationData = explode(',', $filters['location']);
            $locationSearch = count($locationData) > 1
                ? trim($locationData[0])
                : trim($filters['location']);

            $search = app(\Botble\RealEstate\Services\PropertySearchService::class);
            if ($locationSearch !== '' && $search->constrainQueryToCity($this->model, $locationSearch, 5000, true)) {
                // Meili city IDs applied (strict: empty city = no rows for filter browse).
            } elseif ($locationSearch !== '' && RealEstateHelper::isEnabledZipCode()) {
                $this->model = $this->model->where('zip_code', $locationSearch);
            }
            // Meili down + no zip: leave unconstrained (do not blank the whole list).
        }

        if ($filters['zip_code'] !== null) {
            $this->model = $this->model->where('zip_code', $filters['zip_code']);
        }

        if (count($filters['category_ids'] ?? [])) {
            $categoryIds = $filters['category_ids'];

            $this->model = $this->model
                ->whereHas('categories', function (Builder $query) use ($categoryIds): void {
                    $query->whereIn('category_id', $categoryIds);
                });
        }

        if ($filters['locations'] ?? []) {
            $locationsSearch = array_values(array_filter(array_map('trim', (array) $filters['locations'])));
            $search = app(\Botble\RealEstate\Services\PropertySearchService::class);
            $allIds = [];
            $meiliOk = false;

            foreach ($locationsSearch as $location) {
                $hit = $search->searchCityIds($location, 3000);
                if ($hit === null) {
                    $hit = $search->searchIds($location, ['limit' => 3000, 'residential_only' => true]);
                }
                if ($hit === null) {
                    continue;
                }
                $meiliOk = true;
                $allIds = array_merge($allIds, $hit);
            }

            if ($meiliOk) {
                $allIds = array_values(array_unique(array_map('intval', $allIds)));
                $this->model = $allIds === []
                    ? $this->model->whereRaw('0 = 1')
                    : $this->model->whereIn('id', $allIds);
            } elseif ($locationsSearch !== [] && RealEstateHelper::isEnabledZipCode()) {
                $this->model = $this->model->whereIn('zip_code', $locationsSearch);
            } elseif ($locationsSearch !== []) {
                $this->model = $this->model->whereRaw('0 = 1');
            }
        }

        if ($filters['features'] !== null) {
            $features = array_filter((array) $filters['features']);

            if ($features) {
                $propertyIds = $this
                    ->getModel()
                    ->toBase()
                    ->select('re_properties.id')
                    ->join('re_property_features', 're_properties.id', '=', 're_property_features.property_id')
                    ->whereIn('re_property_features.feature_id', $features)
                    ->groupBy('re_properties.id')
                    ->havingRaw('COUNT(DISTINCT re_property_features.feature_id) = ' . count($features))
                    ->pluck('re_properties.id')
                    ->all();

                $this->model = $this->model->whereIn('id', $propertyIds);
            }
        }

        $this->model = apply_filters('properties_filter_query', $this->model, $filters, $params);

        if ($isBrowseListing && Arr::get($params, 'paginate.type') === 'simplePaginate') {
            return $this->browseListingPaginate($params, $filters);
        }

        return $this->advancedGet($params);
    }

    protected function browseListingPaginate(array $params, array $filters): LengthAwarePaginator
    {
        $paginate = $params['paginate'] ?? [];
        $perPage = max(1, (int) ($paginate['per_page'] ?? 12));
        $page = max(1, (int) ($paginate['current_paged'] ?? 1));
        $pageName = $paginate['page_name'] ?? 'page';

        $query = $this->model;

        if (! empty($params['select'])) {
            $query = $query->select($params['select']);
        }

        if (! empty($params['with'])) {
            $query = $query->with($params['with']);
        }

        $query = $this->applyBeforeExecuteQuery($query);
        $total = $this->resolveBrowseListingTotal($query, $filters);

        $items = (clone $query)->forPage($page, $perPage)->get();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
                'query' => request()->query(),
            ]
        );
    }

    protected function resolveBrowseListingTotal(Builder $query, array $filters): int
    {
        if (! $this->browseListingHasFilters($filters)) {
            $cached = Cache::get('serik_active_listing_count_v1');

            if ($cached !== null) {
                return (int) $cached;
            }
        }

        $cacheKey = 'serik_browse_count:' . md5(json_encode($this->browseListingCountSignature($filters)));

        return (int) Cache::remember($cacheKey, 300, function () use ($query) {
            return (clone $query)->toBase()->count('re_properties.id');
        });
    }

    protected function browseListingHasFilters(array $filters): bool
    {
        foreach ([
            'keyword',
            'bedroom',
            'bathroom',
            'floor',
            'min_price',
            'max_price',
            'min_square',
            'max_square',
            'project',
            'project_id',
            'category_id',
            'author_id',
            'city_id',
            'city',
            'state',
            'state_id',
            'location',
            'zip_code',
            'home_types',
            'features',
            'category_ids',
            'locations',
        ] as $key) {
            $value = $filters[$key] ?? null;

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function browseListingCountSignature(array $filters): array
    {
        $signature = Arr::only($filters, [
            'keyword',
            'type',
            'bedroom',
            'bathroom',
            'floor',
            'min_price',
            'max_price',
            'min_square',
            'max_square',
            'project',
            'project_id',
            'category_id',
            'author_id',
            'city_id',
            'city',
            'state',
            'state_id',
            'location',
            'zip_code',
            'sort_by',
            'home_types',
            'features',
            'category_ids',
            'locations',
        ]);

        ksort($signature);

        return $signature;
    }

    public function getProperty(int $propertyId, array $with = [], array $extra = []): ?Property
    {
        $params = array_merge([
            'condition' => [
                'id' => $propertyId,
                'moderation_status' => ModerationStatusEnum::APPROVED,
            ],
            'with' => $with,
            'take' => 1,
        ], $extra);

        // @phpstan-ignore-next-line
        $this->model = $this->originalModel->notExpired();

        return $this->advancedGet($params);
    }

    public function getPropertiesByConditions(array $condition, int $limit = 4, array $with = []): Collection|LengthAwarePaginator
    {
        $limit = $limit > 1 ? $limit : 4;

        // @phpstan-ignore-next-line
        $this->model = $this->originalModel->active();

        $params = [
            'condition' => $condition,
            'with' => $with,
            'take' => $limit,
            'order_by' => ['created_at' => 'DESC'],
        ];

        return $this->advancedGet($params);
    }
}
