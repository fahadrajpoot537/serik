@include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.gallery-grid'), ['model' => $model])

<section class="flat-section-v6 flat-property-detail-v3">
    <div class="container">
        {!! apply_filters('ads_render', null, 'detail_page_before') !!}

        <div class="row">
            <div class="col-lg-8">
                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.header'), ['model' => $model])

                {!! apply_filters('before_single_content_detail', null, $model) !!}

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.description'), ['class' => 'single-property-element', 'model' => $model])

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.video'), ['class' => 'single-property-element', 'model' => $model])

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.features'), ['class' => 'single-property-element', 'model' => $model])

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.facilities'), ['class' => 'single-property-element', 'model' => $model])

                @if (!($model instanceof \Botble\RealEstate\Models\Project))
                    @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.project'), ['class' => 'single-property-element', 'model' => $model])
                @endif

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.map'), ['class' => 'single-property-element', 'model' => $model])

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.floor-plans'), ['class' => 'single-property-element', 'model' => $model])

                {!! apply_filters('after_single_content_detail', null, $model) !!}

                <div class="wrapper-onepage">
                    @include(Theme::getThemeNamespace('views.real-estate.partials.social-sharing'), ['model' => $model])
                </div>

                {!! apply_filters(
                    BASE_FILTER_PUBLIC_COMMENT_AREA,
                    null,
                    $model
                ) !!}

                @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.reviews'), ['model' => $model, 'class' => 'single-property-element'])
            </div>
            <div class="col-lg-4">
                <div class="widget-sidebar wrapper-sidebar-right">
                    {!! apply_filters('ads_render', null, 'detail_page_sidebar_before') !!}

                    @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.contact'), ['class' => 'bg-surface', 'model' => $model])

                    {!! apply_filters('ads_render', null, 'detail_page_sidebar_after') !!}
                </div>
            </div>
        </div>

        {!! apply_filters('ads_render', null, 'detail_page_after') !!}
    </div>
</section>

@include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.related-properties'), ['model' => $model])
