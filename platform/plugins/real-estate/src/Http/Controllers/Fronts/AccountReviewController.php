<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Facades\Assets;
use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Tables\Fronts\ReviewTable;
use Closure;
use Illuminate\Http\Request;

class AccountReviewController extends BaseController
{
    public function __construct()
    {
        $this->middleware(function (Request $request, Closure $next) {
            abort_unless(RealEstateHelper::isEnabledReview(), 404);

            return $next($request);
        });
    }

    public function index(ReviewTable $table)
    {
        $this->pageTitle(trans('plugins/real-estate::review.name'));

        Assets::addStylesDirectly('vendor/core/plugins/real-estate/css/review.css');

        return $table->renderTable();
    }
}
