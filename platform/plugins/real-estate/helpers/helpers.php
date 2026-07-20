<?php

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Supports\SortItemsWithChildrenHelper;
use Botble\RealEstate\Models\Category;
use Botble\RealEstate\Repositories\Interfaces\CategoryInterface;
use Botble\Support\Services\Cache\Cache;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

if (! function_exists('get_property_categories')) {
    function get_property_categories(array $args = []): array
    {
        $indent = Arr::get($args, 'indent', '——');
        $conditions = Arr::get($args, 'conditions', []);
        $select = Arr::get($args, 'select', ['*']);
        $loadSlug = Arr::get($args, 'with_slug', true);

        $locale = app()->getLocale();
        $cacheKey = md5('property_categories_' . $locale . '_' . serialize($select) . serialize($conditions) . $indent . ($loadSlug ? '1' : '0'));

        return cache()->remember($cacheKey, 3600, function () use ($select, $conditions, $indent, $loadSlug) {
            $repo = app(CategoryInterface::class);

            if (! $loadSlug && $select !== ['*']) {
                $model = $repo->getModel();
                $categories = $model->select($select)->where($conditions)
                    ->oldest('order')
                    ->latest('is_default')
                    ->latest('created_at')
                    ->get();
            } else {
                $categories = $repo->getCategories($select, [
                    'order' => 'ASC',
                    'is_default' => 'DESC',
                    'created_at' => 'DESC',
                ], $conditions);
            }

            $categories = sort_item_with_children($categories);

            foreach ($categories as $category) {
                $depth = (int) $category->depth;
                $indentText = str_repeat($indent, $depth);
                $category->indent_text = $indentText;
            }

            return $categories;
        });
    }
}

if (! function_exists('get_property_categories_with_children')) {
    function get_property_categories_with_children(): array
    {
        $categories = app(CategoryInterface::class)
            ->allBy(['status' => BaseStatusEnum::PUBLISHED], [], ['id', 'name', 'parent_id']);

        return app(SortItemsWithChildrenHelper::class)
            ->setChildrenProperty('child_cats')
            ->setItems($categories)
            ->sort();
    }
}

if (! function_exists('get_property_categories_related_ids')) {
    function get_property_categories_related_ids(
        int|string|null $categoryId,
        array &$results = [],
        array|Collection|null $categories = null
    ): array {
        if ($categories instanceof Collection) {
            $list = $categories->where('parent_id', $categoryId);
            foreach ($list as $item) {
                $results[] = $item->id;

                $children = $categories->where('parent_id', $item->id);
                if ($children && $children->count()) {
                    $results = get_property_categories_related_ids($item->id, $results, $children);
                }
            }

            return $results;
        }

        $categories = app(CategoryInterface::class)->allBy([
            'status' => BaseStatusEnum::PUBLISHED,
        ], [], ['id', 'parent_id']);

        $category = $categories->firstWhere('id', $categoryId);

        if ($category) {
            $results[] = $categoryId;
            $results = get_property_categories_related_ids($categoryId, $results, $categories);
        }

        return array_filter($results);
    }
}

if (! function_exists('get_property_categories_for_select')) {
    function get_property_categories_for_select(): array
    {
        $cache = Cache::make(Category::class);

        $locale = app()->getLocale();
        $cacheKey = 'property_categories_for_select_' . $locale . '_' . md5($cache->generateCacheKeyFromInput());

        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $query = Category::query()
            ->toBase()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->select([
                're_categories.id',
                're_categories.name',
                'parent_id',
            ])
            ->oldest('order')
            ->latest('is_default')
            ->latest('created_at');

        $categories = $query->get();

        $groupedCategories = collect($categories)->groupBy('parent_id');

        $result = [];

        $buildTree = function ($parentId = 0, $depth = 0) use (&$buildTree, $groupedCategories, &$result): void {
            $items = $groupedCategories->get($parentId);

            if ($items) {
                foreach ($items as $item) {
                    $item->indent_text = str_repeat('↳', $depth);
                    $result[] = $item;

                    $buildTree($item->id, $depth + 1);
                }
            }
        };

        $buildTree();

        $cache->put($cacheKey, $result, Carbon::now()->addHours(2));

        return $result;
    }
}
