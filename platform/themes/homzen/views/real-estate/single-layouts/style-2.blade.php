@php
    Theme::addBodyAttributes(['class' => Theme::getBodyAttribute('class') . ' bg-surface']);
    Theme::asset()->container('footer')->usePath()->add('jquery-one-page-nav', 'js/jquery.one-page-nav.min.js');
    $isProject = $model instanceof \Botble\RealEstate\Models\Project;
@endphp

@include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.gallery-grid'), ['model' => $model])

<section class="flat-categories-single bg-white fixed-cate-single">
    <div class="container">
        <ul class="cate-single-tab">
            <li class="active">
                <a  class="cate-single-item" href="#description">
                    {{ __('Description') }}
                </a>
            </li>
            <li>
                <a  class="cate-single-item" href="#video">
                    {{ __('Video') }}
                </a>
            </li>
            <li>
                <a  class="cate-single-item" href="#amentities">
                    {{ __('Amenities') }}
                </a>
            </li>
            <li>
                <a  class="cate-single-item" href="#nearby">
                    {{ __('Nearby') }}
                </a>
            </li>
            @if (!$isProject && RealEstateHelper::isEnabledProjects() && $model->project_id && ($project = $model->project))
                <li>
                    <a  class="cate-single-item" href="#project">
                        {{ __('Project') }}
                    </a>
                </li>
            @endif
            <li>
                <a  class="cate-single-item" href="#location">
                    {{ __('Location') }}
                </a>
            </li>
            @if (($model->formatted_floor_plans ?? collect())->isNotEmpty())
                <li>
                    <a  class="cate-single-item" href="#floor-plans">
                        {{ __('Floor Plans') }}
                    </a>
                </li>
            @endif
            <li>
                <a  class="cate-single-item" href="#reviews">
                    {{ __('Reviews') }}
                </a>
            </li>
        </ul>
    </div>
</section>

<section class="flat-section-v6 flat-property-detail-v2">
    <div class="container">
        {!! apply_filters('ads_render', null, 'detail_page_before') !!}

        <div class="row">
            <div class="col-lg-8">
                <div class="wrapper-onepage" id="description">
                    <div class="widget-box-header-single">
                        @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.header'), ['model' => $model])
                        @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.description'), ['model' => $model])
                    </div>
                </div>
                {!! apply_filters('before_single_content_detail', null, $model) !!}

                <div class="wrapper-onepage" id="video">
                    @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.video'), ['class' => 'widget-box-single', 'model' => $model])
                </div>
                <div class="wrapper-onepage" id="amentities">
                    @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.features'), ['class' => 'widget-box-single', 'model' => $model])
                </div>
                <div class="wrapper-onepage" id="nearby">
                    @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.facilities'), ['class' => 'widget-box-single', 'model' => $model])
                </div>
                @if (!$isProject && RealEstateHelper::isEnabledProjects() && $model->project_id && ($project = $model->project))
                    <div class="wrapper-onepage" id="project">
                        @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.project'), ['class' => 'widget-box-single', 'model' => $model])
                    </div>
                @endif
                <div class="wrapper-onepage" id="location">
                    @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.map'), ['class' => 'widget-box-single', 'model' => $model])
                </div>
                @if (($model->formatted_floor_plans ?? collect())->isNotEmpty())
                    <div class="wrapper-onepage" id="floor-plans">
                        @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.floor-plans'), ['class' => 'widget-box-single', 'model' => $model])
                    </div>
                @endif
                {!! apply_filters('after_single_content_detail', null, $model) !!}

                <div class="wrapper-onepage">
                    @include(Theme::getThemeNamespace('views.real-estate.partials.social-sharing'), ['model' => $model])
                </div>

                {!! apply_filters(
                    BASE_FILTER_PUBLIC_COMMENT_AREA,
                    null,
                    $model
                ) !!}

                <div class="wrapper-onepage" id="reviews">
                    @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.reviews'), ['model' => $model, 'class' => 'widget-box-single'])
                </div>
            </div>
            <div class="col-lg-4">
                <div class="widget-sidebar wrapper-sidebar-right">
                    {!! apply_filters('ads_render', null, 'detail_page_sidebar_before') !!}

                    @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.contact'), ['class' => 'bg-white', 'model' => $model])

                    {!! apply_filters('ads_render', null, 'detail_page_sidebar_after') !!}
                </div>
            </div>
        </div>

        {!! apply_filters('ads_render', null, 'detail_page_after') !!}
    </div>
</section>

@include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.related-properties'), ['model' => $model])
