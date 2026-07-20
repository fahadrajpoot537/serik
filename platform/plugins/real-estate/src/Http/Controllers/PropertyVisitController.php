<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\RealEstate\Models\PropertyVisit;
use Botble\RealEstate\Tables\PropertyVisitTable;
use Exception;
use Illuminate\Http\Request;

class PropertyVisitController extends BaseController
{
    public function index(PropertyVisitTable $dataTable)
    {
        $this->pageTitle(trans('plugins/real-estate::property-visit.name'));

        if (request()->ajax()) {
            return $dataTable->ajax();
        }

        return $dataTable->renderTable();
    }

    public function approveDelete(int|string $id, Request $request)
    {
        try {
            $visit = PropertyVisit::query()->findOrFail($id);

            $visit->update([
                'delete_approved_by' => $request->user()->getKey(),
                'delete_approved_at' => now(),
            ]);

            $visit->delete();

            return $this
                ->httpResponse()
                ->setMessage(trans('plugins/real-estate::property-visit.approved_delete'));
        } catch (Exception $exception) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function destroy(int|string $id)
    {
        try {
            $visit = PropertyVisit::query()->withTrashed()->findOrFail($id);
            $visit->forceDelete();

            return $this
                ->httpResponse()
                ->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
}
