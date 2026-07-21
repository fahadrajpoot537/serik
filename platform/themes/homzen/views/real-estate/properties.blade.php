@php
    Theme::layout('full-width');
    Theme::set('pageTitle', __('Properties'));
    Theme::set('pageH1', \App\Support\PageH1::utilityH1ForSlug('properties'));
    Theme::set('breadcrumbEnabled', 'no');
    Theme::set('pageH1Variant', 'visually-compact');
    Theme::addBodyAttributes(['class' => Theme::getBodyAttribute('class') . ' serik-properties-page listing-no-map']);
    Theme::asset()->container('footer')->usePath()->add('nice-select', 'js/jquery.nice-select.min.js');

    use Botble\RealEstate\Models\Property;
    use Illuminate\Support\Facades\Cache;

    $propertyCount = ($properties ?? null) instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? $properties->total()
        : Cache::get('serik_active_listing_count_v1');
@endphp

@include(Theme::getThemeNamespace('views.real-estate.partials.listing'), [
    'actionUrl' => RealEstateHelper::getPropertiesListPageUrl(),
    'ajaxUrl' => route('public.properties'),
    'itemLayout' => 'grid',
    'layout' => 'without-map',
    'perPages' => RealEstateHelper::getPropertiesPerPageList(),
    'filterViewPath' => Theme::getThemeNamespace('views.real-estate.partials.filters.properties-toolbar'),
    'itemsViewPath' => Theme::getThemeNamespace('views.real-estate.properties.index'),
    'propertyCount' => $propertyCount,
])
