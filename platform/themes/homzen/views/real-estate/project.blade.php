@php
    Theme::layout('full-width');
    Theme::set('breadcrumbEnabled', 'no');

    Theme::asset()->usePath()->add('fancybox', 'plugins/fancybox/jquery.fancybox.min.css');
    Theme::asset()->container('footer')->usePath()->add('fancybox', 'plugins/fancybox/jquery.fancybox.min.js');
    Theme::asset()->usePath()->add('leaflet', 'plugins/leaflet/leaflet.css');
    Theme::asset()->container('footer')->usePath()->add('leaflet', 'plugins/leaflet/leaflet.js');

    $style = theme_option('real_estate_project_detail_layout', 1);
    $style = in_array($style, range(1, 4)) ? $style : 1;
    Theme::set('pageTitle', $project->name);
@endphp

@include(Theme::getThemeNamespace("views.real-estate.single-layouts.style-$style"), ['model' => $project])

<template id="map-popup-content">
    <div class="map-popup-content">
        <a class="map-popup-content-thumb" href="{{ $project->url }}">
            {{ RvMedia::image($project->image_thumb, $project->name, lazy: false) }}
            {!! BaseHelper::clean($project->status_html) !!}
        </a>
        <div class="map-popup-content__details">
            <h5 class="map-popup-content__title">
                <a href="{{ $project->url }}" target="_blank" class="map-popup-content__link">{{ $project->name }}</a>
            </h5>
            <div class="map-popup-content__price">{{ $project->category_name }}</div>
            <div class="map-popup-content__city">
                <x-core::icon name="ti ti-map-pin" />
                {{ $project->short_address }}
            </div>
        </div>
    </div>
</template>