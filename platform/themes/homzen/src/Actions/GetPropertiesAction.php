<?php

namespace Theme\homzen\Actions;

use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GetPropertiesAction
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<\Botble\RealEstate\Models\Property|\Illuminate\Database\Eloquent\Model>
     */
    public function handle(
        ?int $limit = 4,
        ?string $categoryId = null,
        ?string $type = null,
        bool $featured = false,
        array $categoryIds = []
    ): Collection {
        $limit = max(1, min(48, (int) ($limit ?: 4)));

        $model = Property::query()
            ->where(RealEstateHelper::getPropertyDisplayQueryConditions())
            ->when(
                $featured,
                fn (Builder $query) => $query->where('is_featured', true)
            )
            ->when(
                $type,
                fn (Builder $query) => $query->where('type', $type)
            )
            ->when(
                $categoryId,
                fn (Builder $query) => $query->whereRelation('categories', 'id', $categoryId)
            )
            ->when($categoryIds, function (Builder $query) use ($categoryIds) {
                return $query->whereHas('categories', fn (Builder $query) => $query->whereIn('id', $categoryIds));
            })
            ->take($limit);

        if (RealEstateHelper::isEnabledKeepFeaturedPropertiesOnTop()) {
            $model = $model
                ->orderByDesc('is_featured')
                ->orderByRaw('CASE WHEN is_featured = 1 THEN featured_priority ELSE 0 END DESC');
        }

        // Prefer PK order — listing_contract_date DESC is expensive on large tables without covering indexes.
        return $model
            ->orderByDesc('id')
            ->notExpired()
            ->with([...RealEstateHelper::getPropertyRelationsQuery(), 'author'])
            ->get();
    }
}
