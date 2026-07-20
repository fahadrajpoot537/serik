<?php

namespace Botble\RealEstate\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Models\PropertyVisit;
use Botble\RealEstate\Tables\Fronts\PropertyVisitTable;
use Illuminate\Http\Request;

class AccountPropertyVisitController extends BaseController
{
    public function index(PropertyVisitTable $table)
    {
        $this->pageTitle(trans('plugins/real-estate::property-visit.my_history'));

        return $table->renderTable();
    }

    public function requestDelete(int|string $id, Request $request)
    {
        $visit = PropertyVisit::query()
            ->where('account_id', auth('account')->id())
            ->whereKey($id)
            ->firstOrFail();

        if ($visit->delete_requested_at) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(trans('plugins/real-estate::property-visit.already_requested'));
        }

        $visit->update(['delete_requested_at' => now()]);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/real-estate::property-visit.request_sent'));
    }
}
