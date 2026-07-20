@php
    Theme::layout('full-width');
    Theme::set('pageTitle', __('Properties'));
@endphp

<h1 class="d-none">{{ __('Properties677') }}</h1>

@include(Theme::getThemeNamespace('views.real-estate.partials.listing'), [
    'actionUrl' => RealEstateHelper::getPropertiesListPageUrl(),
    'ajaxUrl' => route('public.properties'),
    'mapUrl' => route('public.ajax.properties.map'),
    'itemLayout' => request()->input('layout', 'grid'),
    'layout' => theme_option('real_estate_property_listing_layout', 'top-map'),
    'perPages' => RealEstateHelper::getPropertiesPerPageList(),
    'filterViewPath' => Theme::getThemeNamespace('views.real-estate.partials.filters.property-search-box'),
    'itemsViewPath' => Theme::getThemeNamespace('views.real-estate.properties.index'),
])

@include(Theme::getThemeNamespace('views.real-estate.partials.property-map-content'))


<script>
/*document.addEventListener("DOMContentLoaded", function () {
    if (typeof L !== 'undefined' && document.getElementById('map')) {
        const map = L.map('map').setView([43.6532, -79.3832], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);
    }
});*/
</script>