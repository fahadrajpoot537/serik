<?php

namespace Botble\RealEstate\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Http\Requests\ReviewRequest;
use Botble\RealEstate\Http\Resources\ReviewResource;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Models\Review;
use Botble\RealEstate\Repositories\Interfaces\ReviewInterface;
use Illuminate\Http\Request;

class ReviewController extends BaseController
{
    public function __construct(protected ReviewInterface $reviewRepository)
    {
    }

    /**
     * List reviews for a property
     *
     * @group Real Estate
     * @urlParam property_id integer required The ID of the property.
     * @queryParam page                 Current page of the collection. Default: 1
     * @queryParam per_page             Maximum number of items to be returned in result set. Default: 10
     * @queryParam order                Order sort attribute ascending or descending. Default: desc. One of: asc, desc
     * @queryParam order_by             Sort collection by object attribute. Default: created_at. One of: created_at, updated_at, star
     */
    public function index(int $property_id, Request $request)
    {
        $property = Property::query()
            ->where([
                'id' => $property_id,
                'status' => BaseStatusEnum::PUBLISHED,
            ])
            ->first();

        if (! $property) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Property not found');
        }

        $data = $this->reviewRepository->advancedGet([
            'condition' => [
                'reviewable_id' => $property_id,
                'reviewable_type' => Property::class,
                'status' => BaseStatusEnum::PUBLISHED,
            ],
            'order_by' => [
                $request->input('order_by', 'created_at') => $request->input('order', 'desc'),
            ],
            'paginate' => [
                'per_page' => min($request->integer('per_page', 10), 100),
                'current_paged' => $request->integer('page', 1),
            ],
            'with' => ['author'],
        ]);

        return $this
            ->httpResponse()
            ->setData(ReviewResource::collection($data))
            ->toApiResponse();
    }

    /**
     * Create a review for a property
     *
     * @group Real Estate
     * @authenticated
     * @urlParam property_id integer required The ID of the property.
     * @bodyParam star integer required The rating (1-5). Example: 5
     * @bodyParam comment string required The review comment. Example: Great property!
     */
    public function store(int $property_id, ReviewRequest $request)
    {
        $property = Property::query()
            ->where([
                'id' => $property_id,
                'status' => BaseStatusEnum::PUBLISHED,
            ])
            ->first();

        if (! $property) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Property not found');
        }

        $account = $request->user();

        // Check if user already reviewed this property
        $existingReview = Review::query()
            ->where([
                'reviewable_id' => $property_id,
                'reviewable_type' => Property::class,
                'author_id' => $account->id,
                'author_type' => get_class($account),
            ])
            ->first();

        if ($existingReview) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage('You have already reviewed this property');
        }

        $review = Review::query()->create([
            'star' => $request->input('star'),
            'comment' => $request->input('comment'),
            'reviewable_id' => $property_id,
            'reviewable_type' => Property::class,
            'author_id' => $account->id,
            'author_type' => get_class($account),
            'status' => BaseStatusEnum::PUBLISHED,
        ]);

        return $this
            ->httpResponse()
            ->setData(new ReviewResource($review->load('author')))
            ->setMessage('Review created successfully')
            ->toApiResponse();
    }

    /**
     * Get review by ID
     *
     * @group Real Estate
     * @urlParam id integer required The ID of the review.
     */
    public function show(int $id)
    {
        $review = Review::query()
            ->where([
                'id' => $id,
                'status' => BaseStatusEnum::PUBLISHED,
            ])
            ->with(['author'])
            ->first();

        if (! $review) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Review not found');
        }

        return $this
            ->httpResponse()
            ->setData(new ReviewResource($review))
            ->toApiResponse();
    }

    /**
     * Update a review
     *
     * @group Real Estate
     * @authenticated
     * @urlParam id integer required The ID of the review.
     * @bodyParam star integer required The rating (1-5). Example: 5
     * @bodyParam comment string required The review comment. Example: Updated review!
     */
    public function update(int $id, ReviewRequest $request)
    {
        $account = $request->user();

        $review = Review::query()
            ->where([
                'id' => $id,
                'author_id' => $account->id,
                'author_type' => get_class($account),
            ])
            ->first();

        if (! $review) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Review not found or you are not authorized to update this review');
        }

        $review->update([
            'star' => $request->input('star'),
            'comment' => $request->input('comment'),
        ]);

        return $this
            ->httpResponse()
            ->setData(new ReviewResource($review->load('author')))
            ->setMessage('Review updated successfully')
            ->toApiResponse();
    }

    /**
     * Delete a review
     *
     * @group Real Estate
     * @authenticated
     * @urlParam id integer required The ID of the review.
     */
    public function destroy(int $id, Request $request)
    {
        $account = $request->user();

        $review = Review::query()
            ->where([
                'id' => $id,
                'author_id' => $account->id,
                'author_type' => get_class($account),
            ])
            ->first();

        if (! $review) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage('Review not found or you are not authorized to delete this review');
        }

        $review->delete();

        return $this
            ->httpResponse()
            ->setMessage('Review deleted successfully')
            ->toApiResponse();
    }
}
