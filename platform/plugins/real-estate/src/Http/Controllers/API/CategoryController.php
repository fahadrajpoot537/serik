<?php

namespace Botble\RealEstate\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Http\Resources\CategoryResource;
use Botble\RealEstate\Http\Resources\ListCategoryResource;
use Botble\RealEstate\Http\Resources\ListPropertyResource;
use Botble\RealEstate\Models\Category;
use Botble\RealEstate\Repositories\Interfaces\CategoryInterface;
use Botble\Slug\Facades\SlugHelper;
use Illuminate\Http\Request;

class CategoryController extends BaseController
{
    public function __construct(protected CategoryInterface $categoryRepository)
    {
    }

    /**
     * List categories
     *
     * @group Real Estate
     * @queryParam page                 Current page of the collection. Default: 1
     * @queryParam per_page             Maximum number of items to be returned in result set. Default: 10
     * @queryParam search               Limit results to those matching a string.
     * @queryParam parent_id            Filter by parent category ID.
     * @queryParam order                Order sort attribute ascending or descending. Default: asc. One of: asc, desc
     * @queryParam order_by             Sort collection by object attribute. Default: order. One of: created_at, updated_at, name, order
     */
    public function index(Request $request)
    {
        $conditions = ['status' => BaseStatusEnum::PUBLISHED];

        if ($request->has('parent_id')) {
            $conditions['parent_id'] = $request->input('parent_id');
        }

        $data = $this->categoryRepository->advancedGet([
            'condition' => $conditions,
            'order_by' => [
                $request->input('order_by', 'order') => $request->input('order', 'asc'),
            ],
            'paginate' => [
                'per_page' => min($request->integer('per_page', 10), 100),
                'current_paged' => $request->integer('page', 1),
            ],
            'with' => ['slugable'],
        ]);

        return $this
            ->httpResponse()
            ->setData(ListCategoryResource::collection($data))
            ->toApiResponse();
    }

    /**
     * Get category filters
     *
     * @group Real Estate
     * @queryParam parent_id            Filter by parent category ID.
     * @queryParam order                Order sort attribute ascending or descending. Default: asc. One of: asc, desc
     * @queryParam order_by             Sort collection by object attribute. Default: order. One of: created_at, updated_at, name, order
     */
    public function getFilters(Request $request)
    {
        $conditions = ['status' => BaseStatusEnum::PUBLISHED];

        if ($request->has('parent_id')) {
            $conditions['parent_id'] = $request->input('parent_id');
        }

        $data = $this->categoryRepository->advancedGet([
            'condition' => $conditions,
            'order_by' => [
                $request->input('order_by', 'order') => $request->input('order', 'asc'),
            ],
            'with' => ['slugable'],
        ]);

        return $this
            ->httpResponse()
            ->setData(ListCategoryResource::collection($data))
            ->toApiResponse();
    }

    /**
     * Get category by slug
     *
     * @group Real Estate
     * @urlParam slug string required The slug of the category.
     */
    public function findBySlug(string $slug)
    {
        $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Category::class));

        if (! $slug) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Category not found');
        }

        $category = Category::query()
            ->where([
                'id' => $slug->reference_id,
                'status' => BaseStatusEnum::PUBLISHED,
            ])
            ->with(['slugable', 'children'])
            ->first();

        if (! $category) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Category not found');
        }

        return $this
            ->httpResponse()
            ->setData(new CategoryResource($category))
            ->toApiResponse();
    }

    /**
     * Get category by ID
     *
     * @group Real Estate
     * @urlParam id integer required The ID of the category.
     */
    public function show(int $id)
    {
        $category = Category::query()
            ->where([
                'id' => $id,
                'status' => BaseStatusEnum::PUBLISHED,
            ])
            ->with(['slugable', 'children'])
            ->first();

        if (! $category) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Category not found');
        }

        return $this
            ->httpResponse()
            ->setData(new CategoryResource($category))
            ->toApiResponse();
    }

    /**
     * Get properties of a category
     *
     * @group Real Estate
     * @urlParam id integer required The ID of the category.
     * @queryParam page                 Current page of the collection. Default: 1
     * @queryParam per_page             Maximum number of items to be returned in result set. Default: 10
     */
    public function getProperties(int $id, Request $request)
    {
        $category = Category::query()
            ->where([
                'id' => $id,
                'status' => BaseStatusEnum::PUBLISHED,
            ])
            ->first();

        if (! $category) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Category not found');
        }

        $perPage = $request->integer('per_page', 10);
        $perPage = min($perPage, 100);

        $properties = $category->properties()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->with(['features', 'facilities', 'categories', 'author', 'project'])
            ->paginate($perPage);

        return $this
            ->httpResponse()
            ->setData(ListPropertyResource::collection($properties))
            ->toApiResponse();
    }
}
