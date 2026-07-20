<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\DataSynchronize\Exporter\Exporter;
use Botble\DataSynchronize\Http\Controllers\ExportController;
use Botble\DataSynchronize\Http\Requests\ExportRequest;
use Botble\RealEstate\Exporters\ProjectExporter;

class ExportProjectController extends ExportController
{
    protected function getExporter(): Exporter
    {
        $exporter = ProjectExporter::make();

        if (request()->has('limit')) {
            $exporter->setLimit((int) request()->input('limit'));
        }

        if (request()->has('status') && request()->input('status') !== '') {
            $exporter->setStatus(request()->input('status'));
        }

        if (request()->has('is_featured') && request()->input('is_featured') !== '') {
            $exporter->setIsFeatured((bool) request()->input('is_featured'));
        }

        if (request()->has('investor_id') && request()->input('investor_id') !== '') {
            $exporter->setInvestorId((int) request()->input('investor_id'));
        }

        if (request()->has('category_id') && request()->input('category_id') !== '') {
            $exporter->setCategoryId((int) request()->input('category_id'));
        }

        if (request()->has(['start_date', 'end_date'])) {
            $exporter->setDateRange(
                request()->input('start_date'),
                request()->input('end_date')
            );
        }

        return $exporter;
    }

    public function store(ExportRequest $request)
    {
        $request->validate([
            'limit' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'in:' . implode(',', BaseStatusEnum::values())],
            'is_featured' => ['nullable', 'boolean'],
            'investor_id' => ['nullable', 'integer', 'exists:re_investors,id'],
            'category_id' => ['nullable', 'integer', 'exists:re_categories,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        return parent::store($request);
    }
}
