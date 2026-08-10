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
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Support\SerikCache;
use Illuminate\Support\Facades\Cache;

class PropertyRepository extends RepositoriesAbstract implements PropertyInterface
{
    public function getRelatedProperties(int $propertyId, int $limit = 4, array $with = [], array $extra = []): Collection|LengthAwarePaginatorContract
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

    public function getProperties(array $filters = [], array $params = []): Collection|LengthAwarePaginatorContract|Paginator
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
            'subtypes' => null,
        ], $filters);

        $isBrowseListing = request()->routeIs(
            'public.properties',
            'public.ajax.properties',
            'public.ajax.properties.map',
            'public.seo.ontario'
        ) || request()->is('properties', 'properties/*', 'ontario', 'ontario/*');

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
        $wantSold = ($filters['status'] ?? request()->input('status')) === 'sold';

        if ($wantSold) {
            // Sold MLS rows are often status=draft (hidden from active browse).
            $this->model = $this->originalModel
                ->where('re_properties.moderation_status', ModerationStatusEnum::APPROVED)
                ->residential();
        } else {
            $this->model = $this->originalModel->active()->residential();
        }

        // Active MLS browse only — sold SEO landings must skip mlsActive().
        if ($isBrowseListing && ! $wantSold) {
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

        if ($filters['type'] !== null && $filters['type'] !== '') {
            $type = (string) $filters['type'];

            if ($type === PropertyTypeEnum::SALE || $type === 'sale') {
                // MLS rows use TransactionType; Botble `type` is often null/empty.
                $this->model = $this->model->where(function (Builder $query): void {
                    $query->where('TransactionType', 'For Sale')
                        ->orWhere(function (Builder $q): void {
                            $q->where(function (Builder $inner): void {
                                $inner->whereNull('TransactionType')->orWhere('TransactionType', '');
                            })->where(function (Builder $inner): void {
                                $inner->where('type', PropertyTypeEnum::SALE)
                                    ->orWhereNull('type')
                                    ->orWhere('type', '');
                            });
                        });
                });
            } elseif (in_array($type, [PropertyTypeEnum::RENT, 'rent', 'lease'], true)) {
                // MLS source of truth — do not OR Botble `type` (can leak For Sale rows).
                $this->model = $this->model->whereIn('TransactionType', ['For Lease', 'For Sub-Lease']);
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
                // Province-wide house/condo/townhouse browse: prefer Meili ID set
                // so MySQL avoids a slow subtype filesort across the whole MLS table.
                $appliedMeili = false;
                if (
                    $isBrowseListing
                    && empty($filters['location'])
                    && empty($filters['city_id'])
                    && empty($filters['city'])
                    && empty($filters['keyword'])
                ) {
                    $search = app(\Botble\RealEstate\Services\PropertySearchService::class);
                    $ids = $search->searchIds('', [
                        'limit' => max(200, ((int) ($params['paginate']['per_page'] ?? 12)) * 20),
                        'subtypes' => $subtypes,
                        'statuses' => [
                            'New',
                            'Active',
                            'Ext',
                            'Extension',
                            'Price Change',
                            'Active Under Contract',
                        ],
                        'sort' => ['id:desc'],
                    ]);
                    if (is_array($ids) && $ids !== []) {
                        $this->model = $this->model->whereIn('re_properties.id', $ids);
                        $appliedMeili = true;
                    }
                }

                if (! $appliedMeili) {
                    $this->model = $this->model->whereIn('PropertySubType', $subtypes);
                }
            }
        }

        // Explicit MLS PropertySubType filter (Property Type dropdown).
        if (! empty($filters['subtypes'])) {
            $explicitSubtypes = array_values(array_unique(array_filter(array_map(
                static fn ($v) => trim((string) $v),
                (array) $filters['subtypes']
            ))));
            if ($explicitSubtypes !== [] && ! in_array('All', $explicitSubtypes, true)) {
                $this->model = $this->model->whereIn('PropertySubType', $explicitSubtypes);
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

            // open_house listings are often older IDs; Meili city caps (newest N)
            // would drop them. City is applied inside the open_house filter instead.
            $skipMeiliCity = filter_var($filters['open_house'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $search = app(\Botble\RealEstate\Services\PropertySearchService::class);
            $cityOpts = [
                'statuses' => [
                    'New',
                    'Active',
                    'Ext',
                    'Extension',
                    'Price Change',
                    'Active Under Contract',
                ],
            ];
            $typeForCity = (string) ($filters['type'] ?? '');
            if (in_array($typeForCity, [PropertyTypeEnum::RENT, 'rent', 'lease'], true)) {
                $cityOpts['transactions'] = ['For Lease', 'For Sub-Lease'];
            } elseif ($typeForCity === PropertyTypeEnum::SALE || $typeForCity === 'sale') {
                $cityOpts['transaction'] = 'For Sale';
            }

            $subtypeMap = [
                'house' => ['Detached', 'Semi-Detached', 'Link', 'Rural Residential', 'Farm'],
                'condo' => ['Condo Apartment', 'Condo Townhouse', 'Detached Condo', 'Leasehold Condo', 'Common Element Condo', 'Co-Ownership Apartment'],
                'townhouse' => ['Att/Row/Townhouse', 'Condo Townhouse'],
            ];
            $meiliSubtypes = [];
            foreach ((array) ($filters['home_types'] ?? []) as $homeType) {
                if (isset($subtypeMap[$homeType])) {
                    $meiliSubtypes = array_merge($meiliSubtypes, $subtypeMap[$homeType]);
                }
            }
            $meiliSubtypes = array_values(array_unique($meiliSubtypes));
            if ($meiliSubtypes !== []) {
                $cityOpts['subtypes'] = $meiliSubtypes;
            }
            if (! empty($filters['subtypes']) && is_array($filters['subtypes'])) {
                $cityOpts['subtypes'] = array_values(array_unique(array_merge(
                    $cityOpts['subtypes'] ?? [],
                    array_map('strval', $filters['subtypes'])
                )));
            }
            if (isset($filters['min_price']) && (float) $filters['min_price'] > 0) {
                $cityOpts['min_price'] = (float) $filters['min_price'];
            }
            if (isset($filters['max_price']) && (float) $filters['max_price'] > 0) {
                $cityOpts['max_price'] = (float) $filters['max_price'];
            }
            // Push bed/bath into Meili so city ID sets stay small (avoids 20k whereIn + slow MySQL).
            $minBed = (int) ($filters['bedroom'] ?? $filters['min_bedroom'] ?? 0);
            if ($minBed > 0) {
                $cityOpts['min_bedrooms'] = $minBed;
            }
            $minBath = (int) ($filters['bathroom'] ?? $filters['min_bathroom'] ?? 0);
            if ($minBath > 0) {
                $cityOpts['min_bathrooms'] = $minBath;
            }

            // Page-aware Meili window — default landings need newest IDs, not 20k.
            $page = max(1, (int) request()->input('page', 1));
            $perPage = max(12, (int) request()->input('per_page', 12));
            $meiliCityLimit = min(8000, max(800, ($page * $perPage) + 1500));

            if (
                ! $skipMeiliCity
                && $locationSearch !== ''
                && $search->constrainQueryToCity($this->model, $locationSearch, $meiliCityLimit, true, $cityOpts)
            ) {
                // Meili / district city IDs applied (strict: empty city = no rows).
            } elseif (! $skipMeiliCity && $locationSearch !== '' && RealEstateHelper::isEnabledZipCode()) {
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

    protected function browseListingPaginate(array $params, array $filters): LengthAwarePaginatorContract|Paginator
    {
        $paginate = $params['paginate'] ?? [];
        $perPage = max(1, (int) ($paginate['per_page'] ?? 12));
        $page = max(1, (int) ($paginate['current_paged'] ?? 1));
        $pageName = $paginate['page_name'] ?? 'page';

        $query = $this->model;

        // Lean columns for listing cards — avoid SELECT * on 90k+ MLS table.
        $select = $params['select'] ?? ['*'];
        if ($select === ['*'] || $select === ['re_properties.*']) {
            $select = [
                're_properties.id',
                're_properties.name',
                're_properties.location',
                're_properties.images',
                're_properties.image_val',
                're_properties.price',
                're_properties.currency_id',
                're_properties.number_bedroom',
                're_properties.number_bathroom',
                're_properties.square',
                're_properties.MlsStatus',
                're_properties.TransactionType',
                're_properties.PropertySubType',
                're_properties.BedroomsBelowGrade',
                're_properties.broker',
                're_properties.external_id',
                're_properties.unique_id',
                're_properties.latitude',
                're_properties.longitude',
                're_properties.listing_contract_date',
                're_properties.created_at',
                're_properties.ClosePrice',
                're_properties.status',
                're_properties.moderation_status',
                're_properties.is_featured',
            ];
        }

        $query = $query->select($select);

        if (! empty($params['with'])) {
            $query = $query->with($params['with']);
        }

        $query = $this->applyBeforeExecuteQuery($query);

        $items = $this->browseListingFetchPage($query, $filters, $page, $perPage);
        $total = $this->resolveBrowseListingTotal($query, $filters);

        return new LengthAwarePaginator(
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

    /**
     * Prefer Meili ID page (sort id:desc) then hydrate — avoids MySQL scanning
     * ~140k active rows for LIMIT 15 (measured 0.5–5s). Falls back to SQL.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Support\Collection<int, \Botble\RealEstate\Models\Property>
     */
    protected function browseListingFetchPage($query, array $filters, int $page, int $perPage)
    {
        $meiliIds = $this->browsePageIdsViaMeili($filters, $page, $perPage);
        if (is_array($meiliIds)) {
            if ($meiliIds === []) {
                return collect();
            }

            $rows = (clone $query)
                ->whereIn('re_properties.id', $meiliIds)
                ->get()
                ->keyBy('id');

            return collect($meiliIds)
                ->map(fn ($id) => $rows->get((int) $id))
                ->filter()
                ->values();
        }

        return (clone $query)->forPage($page, $perPage)->get();
    }

    /**
     * @return int[]|null
     */
    protected function browsePageIdsViaMeili(array $filters, int $page, int $perPage): ?array
    {
        if (! empty($filters['open_house']) || ($filters['status'] ?? '') === 'sold') {
            return null;
        }

        // Keyword path already applies Meili/SQL whereIn on the builder — don't
        // replace pagination with a second Meili window.
        if (trim((string) ($filters['keyword'] ?? '')) !== '') {
            return null;
        }

        // Keyword / heavy filters already constrain via whereIn elsewhere;
        // only accelerate the common Ontario-wide / city / type browse path.
        $opts = [
            'residential_only' => true,
            'statuses' => [
                'New',
                'Active',
                'Ext',
                'Extension',
                'Price Change',
                'Active Under Contract',
            ],
            'limit' => max(1, $perPage),
            'offset' => max(0, ($page - 1) * $perPage),
            'sort' => ['id:desc'],
        ];

        $location = trim((string) ($filters['location'] ?? ''));
        if ($location !== '') {
            $parts = explode(',', $location);
            $city = trim($parts[0]);
            if ($city !== '' && strcasecmp($city, 'ontario') !== 0) {
                $opts['city'] = $city;
            }
        }

        $type = (string) ($filters['type'] ?? '');
        if (in_array($type, [PropertyTypeEnum::RENT, 'rent', 'lease'], true)) {
            $opts['transactions'] = ['For Lease', 'For Sub-Lease'];
        } elseif ($type === PropertyTypeEnum::SALE || $type === 'sale') {
            $opts['transaction'] = 'For Sale';
        }

        $subtypeMap = [
            'house' => ['Detached', 'Semi-Detached', 'Link', 'Rural Residential', 'Farm'],
            'condo' => ['Condo Apartment', 'Condo Townhouse', 'Detached Condo', 'Leasehold Condo', 'Common Element Condo', 'Co-Ownership Apartment'],
            'townhouse' => ['Att/Row/Townhouse', 'Condo Townhouse'],
        ];
        $subtypes = [];
        foreach ((array) ($filters['home_types'] ?? []) as $homeType) {
            if (isset($subtypeMap[$homeType])) {
                $subtypes = array_merge($subtypes, $subtypeMap[$homeType]);
            }
        }
        if (! empty($filters['subtypes']) && is_array($filters['subtypes'])) {
            $subtypes = array_merge($subtypes, array_map('strval', $filters['subtypes']));
        }
        $subtypes = array_values(array_unique($subtypes));
        if ($subtypes !== []) {
            $opts['subtypes'] = $subtypes;
        }

        if (isset($filters['min_price']) && (float) $filters['min_price'] > 0) {
            $opts['min_price'] = (float) $filters['min_price'];
        }
        if (isset($filters['max_price']) && (float) $filters['max_price'] > 0) {
            $opts['max_price'] = (float) $filters['max_price'];
        }
        if (! empty($filters['bedroom'])) {
            $opts['min_bedrooms'] = (int) $filters['bedroom'];
        }
        if (! empty($filters['bathroom'])) {
            $opts['min_bathrooms'] = (int) $filters['bathroom'];
        }

        return app(\Botble\RealEstate\Services\PropertySearchService::class)
            ->searchIds('', $opts);
    }

    protected function resolveBrowseListingTotal(Builder $query, array $filters): int
    {
        $unfiltered = ! $this->browseListingHasFilters($filters);

        if ($unfiltered) {
            $cached = Cache::get('serik_active_listing_count_v1');
            if ($cached !== null) {
                return (int) $cached;
            }

            // Soft last-known total: serve instantly and refresh after response.
            // Prevents 10–14s COUNT on cold TTFB while keeping totals stable.
            $lastKnown = Cache::get('serik_active_listing_count_v1:last');
            if ($lastKnown !== null) {
                $this->scheduleBrowseListingTotalRefresh($query, $filters, true);

                return (int) $lastKnown;
            }
        }

        $cacheKey = 'serik_browse_count:' . md5(json_encode($this->browseListingCountSignature($filters)));
        $ttl = $unfiltered ? 600 : 300;

        $total = (int) SerikCache::remember($cacheKey, $ttl, function () use ($query, $filters, $unfiltered) {
            $meiliTotal = $this->estimateBrowseTotalViaMeili($filters);
            if ($meiliTotal !== null) {
                if ($unfiltered) {
                    Cache::put('serik_active_listing_count_v1', $meiliTotal, 600);
                    Cache::put('serik_active_listing_count_v1:last', $meiliTotal, 86400);
                }

                return $meiliTotal;
            }

            $mysqlTotal = (int) (clone $query)->toBase()->count('re_properties.id');
            if ($unfiltered) {
                Cache::put('serik_active_listing_count_v1', $mysqlTotal, 600);
                Cache::put('serik_active_listing_count_v1:last', $mysqlTotal, 86400);
            }

            return $mysqlTotal;
        }, 45);

        return $total;
    }

    /**
     * Background refresh for soft-served listing totals (does not block TTFB).
     */
    protected function scheduleBrowseListingTotalRefresh(Builder $query, array $filters, bool $unfiltered): void
    {
        static $scheduled = false;
        if ($scheduled || ! $unfiltered) {
            return;
        }
        $scheduled = true;

        app()->terminating(function (): void {
            $lock = Cache::lock('serik:browse-count-refresh:active', 90);
            if (! $lock->get()) {
                return;
            }

            try {
                $meiliTotal = app(\Botble\RealEstate\Services\PropertySearchService::class)
                    ->searchEstimatedTotal('', [
                        'residential_only' => true,
                        'statuses' => [
                            'New',
                            'Active',
                            'Ext',
                            'Extension',
                            'Price Change',
                            'Active Under Contract',
                        ],
                    ]);

                $total = $meiliTotal;
                if ($total === null) {
                    $total = (int) Property::query()
                        ->active()
                        ->residential()
                        ->mlsActive()
                        ->toBase()
                        ->count('re_properties.id');
                }

                $total = (int) $total;
                $signature = md5(json_encode([
                    'unfiltered' => true,
                    'statuses' => 'mlsActive',
                    'residential' => true,
                ]));
                Cache::put('serik_browse_count:' . $signature, $total, 600);
                Cache::put('serik_active_listing_count_v1', $total, 600);
                Cache::put('serik_active_listing_count_v1:last', $total, 86400);
            } catch (\Throwable $e) {
                report($e);
            } finally {
                optional($lock)->release();
            }
        });
    }

    /**
     * Prefer Meilisearch estimatedTotalHits for browse counts — avoids MySQL
     * COUNT over the full active residential set (often 10–14s on cold local DBs).
     * Works for city filters and unfiltered Ontario-wide active browse.
     */
    protected function estimateBrowseTotalViaMeili(array $filters): ?int
    {
        if (! empty($filters['open_house']) || ($filters['status'] ?? '') === 'sold') {
            return null;
        }

        $opts = [
            'residential_only' => true,
            'statuses' => [
                'New',
                'Active',
                'Ext',
                'Extension',
                'Price Change',
                'Active Under Contract',
            ],
        ];

        $location = trim((string) ($filters['location'] ?? ''));
        if ($location !== '') {
            $parts = explode(',', $location);
            $city = trim($parts[0]);
            if ($city !== '' && strcasecmp($city, 'ontario') !== 0) {
                $opts['city'] = $city;
            }
        }

        $type = (string) ($filters['type'] ?? '');
        if (in_array($type, [PropertyTypeEnum::RENT, 'rent', 'lease'], true)) {
            $opts['transactions'] = ['For Lease', 'For Sub-Lease'];
        } elseif ($type === PropertyTypeEnum::SALE || $type === 'sale') {
            $opts['transaction'] = 'For Sale';
        }

        $subtypeMap = [
            'house' => ['Detached', 'Semi-Detached', 'Link', 'Rural Residential', 'Farm'],
            'condo' => ['Condo Apartment', 'Condo Townhouse', 'Detached Condo', 'Leasehold Condo', 'Common Element Condo', 'Co-Ownership Apartment'],
            'townhouse' => ['Att/Row/Townhouse', 'Condo Townhouse'],
        ];
        $subtypes = [];
        foreach ((array) ($filters['home_types'] ?? []) as $homeType) {
            if (isset($subtypeMap[$homeType])) {
                $subtypes = array_merge($subtypes, $subtypeMap[$homeType]);
            }
        }
        if (! empty($filters['subtypes']) && is_array($filters['subtypes'])) {
            $subtypes = array_merge($subtypes, array_map('strval', $filters['subtypes']));
        }
        $subtypes = array_values(array_unique($subtypes));
        if ($subtypes !== []) {
            $opts['subtypes'] = $subtypes;
        }

        if (isset($filters['min_price']) && (float) $filters['min_price'] > 0) {
            $opts['min_price'] = (float) $filters['min_price'];
        }
        if (isset($filters['max_price']) && (float) $filters['max_price'] > 0) {
            $opts['max_price'] = (float) $filters['max_price'];
        }
        if (! empty($filters['bedroom'])) {
            $opts['min_bedrooms'] = (int) $filters['bedroom'];
        }
        if (! empty($filters['bathroom'])) {
            $opts['min_bathrooms'] = (int) $filters['bathroom'];
        }

        return app(\Botble\RealEstate\Services\PropertySearchService::class)
            ->searchEstimatedTotal('', $opts);
    }

    protected function browseListingHasFilters(array $filters): bool
    {
        if (! empty($filters['open_house']) || ($filters['status'] ?? '') === 'sold' || trim((string) ($filters['community'] ?? '')) !== '') {
            return true;
        }

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
            'subtypes',
            'features',
            'category_ids',
            'locations',
            'type',
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
            'subtypes',
            'features',
            'category_ids',
            'locations',
            'open_house',
            'status',
            'community',
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

    public function getPropertiesByConditions(array $condition, int $limit = 4, array $with = []): Collection|LengthAwarePaginatorContract
    {
        $limit = $limit > 1 ? $limit : 4;

        // @phpstan-ignore-next-line
        $this->model = $this->originalModel->active()->residential();

        $params = [
            'condition' => $condition,
            'with' => $with,
            'take' => $limit,
            'order_by' => ['created_at' => 'DESC'],
        ];

        return $this->advancedGet($params);
    }
}
