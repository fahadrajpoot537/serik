<?php

namespace Botble\RealEstate\Repositories\Interfaces;

use Botble\RealEstate\Models\Property;
use Botble\Support\Repositories\Interfaces\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

interface PropertyInterface extends RepositoryInterface
{
    public function getRelatedProperties(int $propertyId, int $limit = 4, array $with = [], array $extra = []): Collection|LengthAwarePaginator;

    public function getProperties(array $filters = [], array $params = []): Collection|LengthAwarePaginator|Paginator;

    public function getProperty(int $propertyId, array $with = [], array $extra = []): ?Property;

    public function getPropertiesByConditions(array $condition, int $limit = 4, array $with = []): Collection|LengthAwarePaginator;
}
