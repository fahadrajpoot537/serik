<?php

namespace Botble\RealEstate\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\RepositoryHelper;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Http\Resources\AccountResource;
use Botble\RealEstate\Http\Resources\ListAccountResource;
use Botble\RealEstate\Http\Resources\ListProjectResource;
use Botble\RealEstate\Http\Resources\ListPropertyResource;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Repositories\Interfaces\AccountInterface;
use Illuminate\Http\Request;

class AccountController extends BaseController
{
    public function __construct(protected AccountInterface $accountRepository)
    {
    }

    /**
     * List agents/accounts
     *
     * @group Real Estate
     * @queryParam page                 Current page of the collection. Default: 1
     * @queryParam per_page             Maximum number of items to be returned in result set. Default: 10
     * @queryParam search               Limit results to those matching a string.
     * @queryParam is_featured          Filter by featured agents (1 or 0).
     * @queryParam order                Order sort attribute ascending or descending. Default: desc. One of: asc, desc
     * @queryParam order_by             Sort collection by object attribute. Default: created_at. One of: created_at, updated_at, first_name, last_name
     */
    public function index(Request $request)
    {
        $conditions = [
            'is_public_profile' => true,
        ];

        if ($request->has('is_featured')) {
            $conditions['is_featured'] = (bool) $request->input('is_featured');
        }

        $data = $this->accountRepository->advancedGet([
            'condition' => $conditions,
            'order_by' => [
                $request->input('order_by', 'created_at') => $request->input('order', 'desc'),
            ],
            'paginate' => [
                'per_page' => min($request->integer('per_page', 10), 100),
                'current_paged' => $request->integer('page', 1),
            ],
            'with' => ['avatar'],
            'withCount' => [
                'properties' => function ($query) {
                    return RepositoryHelper::applyBeforeExecuteQuery($query, $query->getModel());
                },
                'projects' => function ($query) {
                    return RepositoryHelper::applyBeforeExecuteQuery($query, $query->getModel());
                },
            ],
        ]);

        return $this
            ->httpResponse()
            ->setData(ListAccountResource::collection($data))
            ->toApiResponse();
    }

    /**
     * Get account profile
     *
     * @group Real Estate
     * @authenticated
     */
    public function profile(Request $request)
    {
        $account = $request->user();

        if (! $account instanceof Account) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(401)
                ->setMessage('Unauthorized');
        }

        return $this
            ->httpResponse()
            ->setData(new AccountResource($account))
            ->toApiResponse();
    }

    /**
     * Get account by ID
     *
     * @group Real Estate
     * @urlParam id integer required The ID of the account.
     */
    public function show(int $id)
    {
        $account = Account::query()
            ->where([
                'id' => $id,
                'is_public_profile' => true,
            ])
            ->with(['avatar'])
            ->withCount([
                'properties' => function ($query) {
                    return RepositoryHelper::applyBeforeExecuteQuery($query, $query->getModel());
                },
                'projects' => function ($query) {
                    return RepositoryHelper::applyBeforeExecuteQuery($query, $query->getModel());
                },
            ])
            ->first();

        if (! $account) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Account not found');
        }

        return $this
            ->httpResponse()
            ->setData(new AccountResource($account))
            ->toApiResponse();
    }

    /**
     * Get properties of an account
     *
     * @group Real Estate
     * @urlParam id integer required The ID of the account.
     * @queryParam page                 Current page of the collection. Default: 1
     * @queryParam per_page             Maximum number of items to be returned in result set. Default: 10
     */
    public function getProperties(int $id, Request $request)
    {
        $account = Account::query()
            ->where([
                'id' => $id,
                'is_public_profile' => true,
            ])
            ->first();

        if (! $account) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Account not found');
        }

        $perPage = $request->integer('per_page', 10);
        $perPage = min($perPage, 100);

        $properties = $account->properties()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->where('moderation_status', ModerationStatusEnum::APPROVED)
            ->with(['features', 'facilities', 'categories', 'project'])
            ->paginate($perPage);

        return $this
            ->httpResponse()
            ->setData(ListPropertyResource::collection($properties))
            ->toApiResponse();
    }

    /**
     * Get projects of an account
     *
     * @group Real Estate
     * @urlParam id integer required The ID of the account.
     * @queryParam page                 Current page of the collection. Default: 1
     * @queryParam per_page             Maximum number of items to be returned in result set. Default: 10
     */
    public function getProjects(int $id, Request $request)
    {
        $account = Account::query()
            ->where([
                'id' => $id,
                'is_public_profile' => true,
            ])
            ->first();

        if (! $account) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Account not found');
        }

        $perPage = $request->integer('per_page', 10);
        $perPage = min($perPage, 100);

        $projects = $account->projects()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->with(['investor', 'category'])
            ->paginate($perPage);

        return $this
            ->httpResponse()
            ->setData(ListProjectResource::collection($projects))
            ->toApiResponse();
    }
}
