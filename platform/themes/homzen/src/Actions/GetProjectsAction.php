<?php

namespace Theme\homzen\Actions;

use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GetProjectsAction
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<\Botble\RealEstate\Models\Project|\Illuminate\Database\Eloquent\Model>
     */
    public function handle(
        ?int $limit = 4,
        ?string $categoryId = null,
        bool $featured = false,
        array $categoryIds = []
    ): Collection {
        $model = Project::query()
            ->where(RealEstateHelper::getProjectDisplayQueryConditions())
            ->when(
                $featured,
                fn (Builder $query, string $type) => $query->where('is_featured', $featured) // @phpstan-ignore-line
            )
            ->when(
                $categoryId,
                fn (Builder $query, string $categoryId) => $query->whereRelation('categories', 'id', $categoryId)
            )
            ->when($categoryIds, function (Builder $query, array $categoryIds) {
                return $query->whereHas('categories', fn (Builder $query) => $query->whereIn('id', $categoryIds));
            })
            ->take($limit);

        // Sort by featured projects if the setting is enabled
        if (RealEstateHelper::isEnabledKeepFeaturedPropertiesOnTop()) {
            // First sort by featured status (featured projects first)
            $model = $model->orderByDesc('is_featured');

            // Then sort by featured_priority only for projects where is_featured = 1
            $model = $model->orderByRaw('CASE WHEN is_featured = 1 THEN featured_priority ELSE 0 END DESC');
        }

        return $model
            ->latest()
            ->with([...RealEstateHelper::getProjectRelationsQuery(), 'author'])
            ->get();
    }
}
