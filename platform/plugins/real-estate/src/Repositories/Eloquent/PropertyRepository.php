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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

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

    public function getProperties(array $filters = [], array $params = []): Collection|LengthAwarePaginator
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
        ], $filters);

        $orderBy = match ($filters['sort_by']) {
            'date_asc' => [
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
                'created_at' => 'DESC',
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

        // Initialize the model with active properties
        $this->model = $this->originalModel->active();

        // Sort by featured properties if the setting is enabled
        if (RealEstateHelper::isEnabledKeepFeaturedPropertiesOnTop()) {
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
                // Meili down: sargable MLS key only — never LOWER LIKE on name/location.
                $this->model = $this->model->where(function ($q) use ($keyword) {
                    $q->where('external_id', strtoupper($keyword))
                        ->orWhere('unique_id', strtoupper($keyword));
                });
            } elseif ($keyword !== '') {
                $this->model = $this->model->whereRaw('0 = 1');
            }
        }

        if ($filters['type'] !== null) {
            if ($filters['type'] == PropertyTypeEnum::SALE) {
                $this->model = $this->model->where('type', $filters['type']);
            } else {
                $this->model = $this->model->where('type', $filters['type']);
            }
        }

        if ($filters['bedroom']) {
            if ($filters['bedroom'] < 5) {
                $this->model = $this->model->where('number_bedroom', $filters['bedroom']);
            } else {
                $this->model = $this->model->where('number_bedroom', '>=', $filters['bedroom']);
            }
        }

        if ($filters['bathroom']) {
            if ($filters['bathroom'] < 5) {
                $this->model = $this->model->where('number_bathroom', $filters['bathroom']);
            } else {
                $this->model = $this->model->where('number_bathroom', '>=', $filters['bathroom']);
            }
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

        return $this->advancedGet($params);
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
