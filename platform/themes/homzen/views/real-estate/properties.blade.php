@php
    Theme::layout('full-width');
    Theme::set('pageTitle', __('Properties'));
    Theme::set('breadcrumbEnabled', 'no');
    Theme::addBodyAttributes(['class' => Theme::getBodyAttribute('class') . ' serik-properties-page listing-no-map']);
    Theme::asset()->container('footer')->usePath()->add('nice-select', 'js/jquery.nice-select.min.js');

    use Illuminate\Support\Facades\Cache;

    $isOntarioSeo = request()->routeIs('public.seo.ontario');
    $listingActionUrl = $isOntarioSeo
        ? url()->current()
        : RealEstateHelper::getPropertiesListPageUrl();
    $listingAjaxUrl = $isOntarioSeo
        ? url()->current()
        : route('public.properties');

    if ($isOntarioSeo) {
        $seoH1 = \App\Support\PageH1::resolve() ?: 'Properties for Sale in Ontario';
        $community = trim((string) request('community', ''));
        if ($community !== '') {
            $cityLabel = trim((string) request('location', ''));
            if ($cityLabel === '') {
                $citySlug = \App\Support\SeoLandingUrl::parseCitySlugFromSeo((string) request()->route('seo', '')) ?: 'ontario';
                $cityLabel = ucwords(str_replace('-', ' ', $citySlug));
            }
            $seoH1 = $community . ' Real Estate, ' . $cityLabel;
        }
        Theme::set('pageH1', $seoH1);
        Theme::set('pageTitle', $seoH1);
        Theme::set('pageH1Variant', 'ontario-seo');
    } else {
        Theme::set('pageH1', \App\Support\PageH1::utilityH1ForSlug('properties'));
        Theme::set('pageH1Variant', 'visually-compact');
    }

    $propertyCount = null;
    if (($properties ?? null) instanceof \Illuminate\Pagination\LengthAwarePaginator) {
        $propertyCount = $properties->total();
    } else {
        $homeTypes = (array) request('home_types', []);
        $browseCountKey = 'serik_browse_count:' . md5(json_encode([
            'home_types' => $homeTypes,
            'location' => request('location'),
            'status' => request('status'),
            'open_house' => request('open_house'),
            'community' => request('community'),
        ]));
        $propertyCount = Cache::get($browseCountKey) ?? Cache::get('serik_active_listing_count_v1');
    }
@endphp

@include(Theme::getThemeNamespace('views.real-estate.partials.listing'), [
    'actionUrl' => $listingActionUrl,
    'ajaxUrl' => $listingAjaxUrl,
    'itemLayout' => 'grid',
    'layout' => 'without-map',
    'perPages' => RealEstateHelper::getPropertiesPerPageList(),
    'filterViewPath' => Theme::getThemeNamespace('views.real-estate.partials.filters.properties-toolbar'),
    'itemsViewPath' => Theme::getThemeNamespace('views.real-estate.properties.index'),
    'propertyCount' => $propertyCount,
])
