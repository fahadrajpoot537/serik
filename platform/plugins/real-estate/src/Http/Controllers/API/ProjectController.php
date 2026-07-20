<?php

namespace Botble\RealEstate\Http\Controllers\API;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Http\Resources\ListProjectResource;
use Botble\RealEstate\Http\Resources\ListPropertyResource;
use Botble\RealEstate\Http\Resources\ProjectResource;
use Botble\RealEstate\Models\Project;
use Botble\Slug\Facades\SlugHelper;
use Illuminate\Http\Request;

class ProjectController extends BaseController
{
    /**
     * List projects
     *
     * @group Real Estate
     * @queryParam page                 Current page of the collection. Default: 1
     * @queryParam per_page             Maximum number of items to be returned in result set. Default: 10
     * @queryParam search               Limit results to those matching a string.
     * @queryParam city_id              Filter by city ID.
     * @queryParam state_id             Filter by state ID.
     * @queryParam country_id           Filter by country ID.
     * @queryParam category_id          Filter by category ID.
     * @queryParam investor_id          Filter by investor ID.
     * @queryParam min_price            Filter by minimum price.
     * @queryParam max_price            Filter by maximum price.
     * @queryParam is_featured          Filter by featured projects (1 or 0).
     * @queryParam order                Order sort attribute ascending or descending. Default: desc. One of: asc, desc
     * @queryParam order_by             Sort collection by object attribute. Default: created_at. One of: created_at, updated_at, name, price_from
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $perPage = min($perPage, 100);

        $projects = RealEstateHelper::getProjectsFilter($perPage);

        return $this
            ->httpResponse()
            ->setData(ListProjectResource::collection($projects))
            ->toApiResponse();
    }

    /**
     * Search projects
     *
     * @bodyParam q string required The search keyword.
     *
     * @group Real Estate
     */
    public function getSearch(Request $request)
    {
        $query = BaseHelper::stringify($request->input('q'));

        $request->merge(['keyword' => $query]);

        $projects = RealEstateHelper::getProjectsFilter();

        $data = [
            'items' => ListProjectResource::collection($projects),
            'query' => $query,
            'count' => $projects->count(),
        ];

        if ($data['count'] > 0) {
            return $this
                ->httpResponse()
                ->setData($data);
        }

        return $this
            ->httpResponse()
            ->setError()
            ->setMessage(trans('core/base::layouts.no_search_result'));
    }

    /**
     * Get project filters
     *
     * @group Real Estate
     * @queryParam page                 Current page of the collection. Default: 1
     * @queryParam per_page             Maximum number of items to be returned in result set. Default: 10
     * @queryParam search               Limit results to those matching a string.
     * @queryParam city_id              Filter by city ID.
     * @queryParam state_id             Filter by state ID.
     * @queryParam country_id           Filter by country ID.
     * @queryParam category_id          Filter by category ID.
     * @queryParam investor_id          Filter by investor ID.
     * @queryParam min_price            Filter by minimum price.
     * @queryParam max_price            Filter by maximum price.
     * @queryParam is_featured          Filter by featured projects (1 or 0).
     * @queryParam order                Order sort attribute ascending or descending. Default: desc. One of: asc, desc
     * @queryParam order_by             Sort collection by object attribute. Default: created_at. One of: created_at, updated_at, name, price_from
     */
    public function getFilters(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $perPage = min($perPage, 100);

        $projects = RealEstateHelper::getProjectsFilter($perPage);

        return $this
            ->httpResponse()
            ->setData(ListProjectResource::collection($projects))
            ->toApiResponse();
    }

    /**
     * Get project by slug
     *
     * @group Real Estate
     * @urlParam slug string required The slug of the project.
     */
    public function findBySlug(string $slug)
    {
        $slug = SlugHelper::getSlug($slug, SlugHelper::getPrefix(Project::class));

        if (! $slug) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Project not found');
        }

        $project = Project::query()
            ->where('id', $slug->reference_id)
            ->where(RealEstateHelper::getProjectDisplayQueryConditions())
            ->with(RealEstateHelper::getProjectRelationsQuery())
            ->first();

        if (! $project) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Project not found');
        }

        return $this
            ->httpResponse()
            ->setData(new ProjectResource($project))
            ->toApiResponse();
    }

    /**
     * Get project by ID
     *
     * @group Real Estate
     * @urlParam id integer required The ID of the project.
     */
    public function show(int $id)
    {
        $project = Project::query()
            ->where('id', $id)
            ->where(RealEstateHelper::getProjectDisplayQueryConditions())
            ->with(RealEstateHelper::getProjectRelationsQuery())
            ->first();

        if (! $project) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Project not found');
        }

        return $this
            ->httpResponse()
            ->setData(new ProjectResource($project))
            ->toApiResponse();
    }

    /**
     * Get properties of a project
     *
     * @group Real Estate
     * @urlParam id integer required The ID of the project.
     * @queryParam page                 Current page of the collection. Default: 1
     * @queryParam per_page             Maximum number of items to be returned in result set. Default: 10
     */
    public function getProperties(int $id, Request $request)
    {
        /**
         * @var Project $project
         */
        $project = Project::query()
            ->where('id', $id)
            ->where(RealEstateHelper::getProjectDisplayQueryConditions())
            ->first();

        if (! $project) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Project not found');
        }

        $perPage = $request->integer('per_page', 10);
        $perPage = min($perPage, 100);

        $properties = $project
            ->properties()
            ->where(RealEstateHelper::getPropertyDisplayQueryConditions())
            ->with(RealEstateHelper::getPropertyRelationsQuery())
            ->paginate($perPage);

        return $this
            ->httpResponse()
            ->setData(ListPropertyResource::collection($properties))
            ->toApiResponse();
    }
}
