
@include(Theme::getThemeNamespace('partials.property-iframe-styles'))
@php




    Theme::layout('full-width');
    Theme::set('breadcrumbEnabled', 'no');

    if (! request()->boolean('iframe')) {
        Theme::asset()->usePath()->add('fancybox', 'plugins/fancybox/jquery.fancybox.min.css');
        Theme::asset()->container('footer')->usePath()->add('fancybox', 'plugins/fancybox/jquery.fancybox.min.js');
    }

    Theme::asset()->usePath()->add('leaflet', 'plugins/leaflet/leaflet.css');
    Theme::asset()->container('footer')->usePath()->add('leaflet', 'plugins/leaflet/leaflet.js');

    $style = theme_option('real_estate_property_detail_layout', 1);
    $style = in_array($style, range(1, 4)) ? $style : 1;
    Theme::set('pageTitle', $property->name);
    Theme::set('pageH1ProvidedByContent', true);
@endphp

@include(Theme::getThemeNamespace("views.real-estate.single-layouts.style-$style"), ['model' => $property])

<template id="map-popup-content">
    <div class="map-listing-item">
        <div class="inner-box">
            <div class="image-box">
                <a href="{{ $property->url }}">
                    {{ RvMedia::image($property->image_thumb, $property->name) }}
                </a>
                {!! BaseHelper::clean($property->status_html) !!}
            </div>
            <div class="content">
                @if($property->short_address)
                    <p class="location">
                        <x-core::icon name="ti ti-map-pin" />
                        {{ $property->short_address }}
                    </p>
                @endif
                <div class="title">
                    <a href="{{ $property->url }}" title="{{ $property->name }}">
                        {{ $property->name }}
                    </a>
                </div>
                @if (!setting('real_estate_hide_price', false))
                    <div class="price">{{ $property->price_html }}</div>
                @endif
                <ul class="list-info">
                    @if ($property->number_bedroom)
                        <li>
                            <x-core::icon name="ti ti-bed" />
                            {{ number_format($property->number_bedroom) }}
                        </li>
                    @endif

                    @if ($property->number_bathroom)
                        <li>
                            <x-core::icon name="ti ti-bath" />
                            {{ number_format($property->number_bathroom) }}
                        </li>
                    @endif

                    @if ($property->square)
                        <li>
                            <x-core::icon name="ti ti-ruler" />
                            {{ $property->square_text }}
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
   document.addEventListener("DOMContentLoaded", function () {
    const iframe = document.querySelector("iframe");

    iframe.addEventListener("load", function () {
        const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        const el = iframeDoc.getElementById("lc_text-widget");

        if (el) {
            el.style.display = "none";
        }
    });
});
</script>
