<?php

namespace Botble\RealEstate\Http\Controllers;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\DataSynchronize\Exporter\Exporter;
use Botble\DataSynchronize\Http\Controllers\ExportController;
use Botble\DataSynchronize\Http\Requests\ExportRequest;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Enums\PropertyTypeEnum;
use Botble\RealEstate\Exporters\PropertyExporter;

class ExportPropertyController extends ExportController
{
    protected function getExporter(): Exporter
    {
        $exporter = PropertyExporter::make();

        if (request()->has('limit')) {
            $exporter->setLimit((int) request()->input('limit'));
        }

        if (request()->has('type') && request()->input('type') !== '') {
            $exporter->setType(request()->input('type'));
        }

        if (request()->has('status') && request()->input('status') !== '') {
            $exporter->setStatus(request()->input('status'));
        }

        if (request()->has('moderation_status') && request()->input('moderation_status') !== '') {
            $exporter->setModerationStatus(request()->input('moderation_status'));
        }

        if (request()->has('project_id') && request()->input('project_id') !== '') {
            $exporter->setProjectId((int) request()->input('project_id'));
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
            'type' => ['nullable', 'string', 'in:' . implode(',', PropertyTypeEnum::values())],
            'status' => ['nullable', 'string', 'in:' . implode(',', BaseStatusEnum::values())],
            'moderation_status' => ['nullable', 'string', 'in:' . implode(',', ModerationStatusEnum::values())],
            'project_id' => ['nullable', 'integer', 'exists:re_projects,id'],
            'category_id' => ['nullable', 'integer', 'exists:re_categories,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        return parent::store($request);
    }
}
