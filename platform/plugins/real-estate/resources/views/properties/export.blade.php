@extends('packages/data-synchronize::export')

@section('export_extra_filters_after')
    @php
        use Botble\Base\Enums\BaseStatusEnum;
        use Botble\RealEstate\Enums\ModerationStatusEnum;
        use Botble\RealEstate\Enums\PropertyTypeEnum;
        use Botble\RealEstate\Models\Project;
        use Botble\RealEstate\Models\Category;

        $types = PropertyTypeEnum::labels();
        $statuses = BaseStatusEnum::labels();
        $moderationStatuses = ModerationStatusEnum::labels();
        $projects = Project::query()->pluck('name', 'id')->toArray();
        $categories = Category::query()->pluck('name', 'id')->toArray();
    @endphp

    <div class="row mb-3">
        <div class="col-md-3">
            <x-core::form.text-input
                name="limit"
                type="number"
                :label="trans('plugins/real-estate::property.export.limit')"
                :placeholder="trans('plugins/real-estate::property.export.limit_placeholder')"
                min="1"
            />
        </div>
        <div class="col-md-3">
            <x-core::form.select
                name="type"
                :label="trans('plugins/real-estate::property.form.type')"
                :options="['' => trans('plugins/real-estate::property.export.all_types')] + $types"
            />
        </div>
        <div class="col-md-3">
            <x-core::form.select
                name="status"
                :label="trans('core/base::forms.status')"
                :options="['' => trans('plugins/real-estate::property.export.all_status')] + $statuses"
            />
        </div>
        <div class="col-md-3">
            <x-core::form.select
                name="moderation_status"
                :label="trans('plugins/real-estate::property.moderation_status')"
                :options="['' => trans('plugins/real-estate::property.export.all_moderation_status')] + $moderationStatuses"
            />
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <x-core::form.select
                name="project_id"
                :label="trans('plugins/real-estate::property.form.project')"
                :options="['' => trans('plugins/real-estate::property.export.all_projects')] + $projects"
            />
        </div>
        <div class="col-md-3">
            <x-core::form.select
                name="category_id"
                :label="trans('plugins/real-estate::property.form.category')"
                :options="['' => trans('plugins/real-estate::property.export.all_categories')] + $categories"
            />
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <label for="start_date" class="form-label">{{ trans('plugins/real-estate::property.export.start_date') }}</label>

            {!! Form::datePicker('start_date', null, ['placeholder' => trans('plugins/real-estate::property.export.start_date_placeholder')]) !!}
        </div>
        <div class="col-md-3">
            <label for="end_date" class="form-label">{{ trans('plugins/real-estate::property.export.end_date') }}</label>

            {!! Form::datePicker('end_date', null, ['placeholder' => trans('plugins/real-estate::property.export.end_date_placeholder')]) !!}
        </div>
    </div>
@stop