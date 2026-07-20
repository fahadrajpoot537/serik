<?php

namespace Botble\RealEstate\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Http\Resources\FacilityResource;
use Botble\RealEstate\Models\Facility;
use Botble\RealEstate\Repositories\Interfaces\FacilityInterface;
use Illuminate\Http\Request;

class FacilityController extends BaseController
{
    public function __construct(protected FacilityInterface $facilityRepository)
    {
    }

    /**
     * List facilities
     *
     * @group Real Estate
     * @queryParam page                 Current page of the collection. Default: 1
     * @queryParam per_page             Maximum number of items to be returned in result set. Default: 10
     * @queryParam search               Limit results to those matching a string.
     * @queryParam order                Order sort attribute ascending or descending. Default: asc. One of: asc, desc
     * @queryParam order_by             Sort collection by object attribute. Default: name. One of: created_at, updated_at, name
     */
    public function index(Request $request)
    {
        $conditions = ['status' => BaseStatusEnum::PUBLISHED];

        $data = $this->facilityRepository->advancedGet([
            'condition' => $conditions,
            'order_by' => [
                $request->input('order_by', 'name') => $request->input('order', 'asc'),
            ],
            'paginate' => [
                'per_page' => min($request->integer('per_page', 10), 100),
                'current_paged' => $request->integer('page', 1),
            ],
        ]);

        return $this
            ->httpResponse()
            ->setData(FacilityResource::collection($data))
            ->toApiResponse();
    }

    /**
     * Get all facilities (without pagination)
     *
     * @group Real Estate
     * @queryParam search               Limit results to those matching a string.
     * @queryParam order                Order sort attribute ascending or descending. Default: asc. One of: asc, desc
     * @queryParam order_by             Sort collection by object attribute. Default: name. One of: created_at, updated_at, name
     */
    public function all(Request $request)
    {
        $conditions = ['status' => BaseStatusEnum::PUBLISHED];

        $data = $this->facilityRepository->advancedGet([
            'condition' => $conditions,
            'order_by' => [
                $request->input('order_by', 'name') => $request->input('order', 'asc'),
            ],
        ]);

        return $this
            ->httpResponse()
            ->setData(FacilityResource::collection($data))
            ->toApiResponse();
    }

    /**
     * Get facility by ID
     *
     * @group Real Estate
     * @urlParam id integer required The ID of the facility.
     */
    public function show(int $id)
    {
        $facility = Facility::query()
            ->where([
                'id' => $id,
                'status' => BaseStatusEnum::PUBLISHED,
            ])
            ->first();

        if (! $facility) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Facility not found');
        }

        return $this
            ->httpResponse()
            ->setData(new FacilityResource($facility))
            ->toApiResponse();
    }
}
