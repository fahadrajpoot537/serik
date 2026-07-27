@php
    $isSerikToolbar = str_contains($filterViewPath ?? '', 'properties-toolbar');

    Theme::set('breadcrumbEnabled', $isSerikToolbar ? 'no' : 'yes');

    Theme::addBodyAttributes(['class' => Theme::getBodyAttribute('class') . ' listing-no-map']);

    Theme::asset()->container('footer')->usePath()->add('nice-select', 'js/jquery.nice-select.min.js');

    $itemLayout = request()->input('layout', $itemLayout ?? 'grid');

    $seoNavCitySlug = \App\Support\SeoLandingUrl::parseCitySlugFromSeo((string) request()->route('seo', ''))
        ?: (string) (request('city') ?: '');
    if ($seoNavCitySlug === '' || $seoNavCitySlug === 'ontario') {
        $seoNavCitySlug = app(\App\Services\Seo\CityResolutionService::class)->resolveSlug() ?: 'toronto';
    }
    $seoNavCommunity = trim((string) request('community', ''));
    $seoNavLocation = trim((string) (request('location') ?: ''));
    $seoListingTitle = null;
    if (request()->routeIs('public.seo.ontario')) {
        $seoListingTitle = \App\Support\PageH1::resolve()
            ?: ('Houses for Sale in ' . ($seoNavLocation !== '' ? $seoNavLocation : ucwords(str_replace('-', ' ', $seoNavCitySlug))));
        if ($seoNavCommunity !== '') {
            $cityLabel = $seoNavLocation !== ''
                ? $seoNavLocation
                : ucwords(str_replace('-', ' ', $seoNavCitySlug));
            $seoListingTitle = $seoNavCommunity . ' Real Estate, ' . $cityLabel;
        }
    }
@endphp

<form
    action="{{ $actionUrl }}"
    data-url="{{ $ajaxUrl }}"
    method="get"
    class="filter-form"
>
    @csrf

    <input type="hidden" name="page" value="{{ BaseHelper::stringify(request()->integer('page')) }}" />
    <input type="hidden" name="layout" value="{{ BaseHelper::stringify(request()->input('layout')) }}" />

    <section @class(['serik-properties-filters-section' => $isSerikToolbar])>
        @if ($isSerikToolbar)
            @include($filterViewPath, [
                'style' => 2,
                'actionUrl' => $actionUrl,
                'propertyCount' => $propertyCount ?? null,
                'seoListingTitle' => $seoListingTitle,
                'seoNavLocation' => $seoNavLocation,
                'seoNavCommunity' => $seoNavCommunity,
            ])
        @else
        <div class="container">
            <div class="search-box-offcanvas container">
                <div class="search-box-offcanvas-backdrop"></div>
                <div class="search-box-offcanvas-content">
                    <div class="search-box-offcanvas-header">
                        <h3>{{ __('Filter') }}</h3>

                        <button type="button" class="btn-close" data-bb-toggle="toggle-filter-offcanvas"></button>
                    </div>

                    <div class="wrap-filter-search">
                        @include($filterViewPath, ['style' => 2])
                    </div>
                </div>
            </div>
        </div>
        @endif
    </section>

    <section class="flat-section-v5 flat-recommended flat-recommended-v2 serik-ontario-listing">
        <div class="container-fluid px-3 px-lg-4">
            @if (! str_contains($filterViewPath ?? '', 'properties-toolbar'))
                @include(Theme::getThemeNamespace('views.real-estate.partials.listing-top'))
            @endif

            {!! apply_filters('ads_render', null, 'listing_page_before') !!}

            <div class="position-relative" data-bb-toggle="data-listing">
                @include($itemsViewPath, compact('itemLayout'))
            </div>

            {!! apply_filters('ads_render', null, 'listing_page_after') !!}

            {{-- Load SEO nav after listings; pass city + community so sections match current page --}}
            <div
                id="serikSeoNavMount"
                data-url="{{ route('public.ajax.seo-city-navigation', array_filter([
                    'context' => 'properties',
                    'city' => $seoNavCitySlug,
                    'community' => $seoNavCommunity !== '' ? $seoNavCommunity : null,
                ])) }}"
                data-city="{{ $seoNavCitySlug }}"
                data-community="{{ $seoNavCommunity }}"
                aria-hidden="true"
            ></div>
        </div>
    </section>
</form>

@include(Theme::getThemeNamespace('partials.property-detail-modal'))

@once
<style>
.serik-ontario-listing.flat-recommended-v2 {
    margin-top: 0 !important;
    padding-top: 20px;
    padding-bottom: 48px;
}
.serik-ontario-listing [data-bb-toggle="data-listing"] {
    margin-top: 8px;
}
</style>
<script>
(function () {
    var mount = document.getElementById('serikSeoNavMount');
    if (!mount || !mount.dataset.url) return;

    var load = function () {
        var url = mount.dataset.url;
        var params = new URLSearchParams();
        params.set('context', 'properties');

        var city = mount.dataset.city || '';
        var pathMatch = window.location.pathname.match(/\/ontario\/.+?-for-(?:sale|lease)-in-([a-z0-9-]+)\/?$/i);
        if (pathMatch && pathMatch[1] && pathMatch[1] !== 'ontario') {
            city = pathMatch[1];
        }
        if (city) {
            params.set('city', city);
        }

        var community = mount.dataset.community || '';
        try {
            var qsCommunity = new URLSearchParams(window.location.search).get('community');
            if (qsCommunity) {
                community = qsCommunity;
            }
        } catch (e) {}
        if (community) {
            params.set('community', community);
        }

        url = url.split('?')[0] + '?' + params.toString();

        fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'text/html' } })
            .then(function (r) { return r.ok ? r.text() : ''; })
            .then(function (html) {
                if (!html) return;
                mount.innerHTML = html;
                mount.removeAttribute('aria-hidden');
            })
            .catch(function () {});
    };

    if ('requestIdleCallback' in window) {
        requestIdleCallback(load, { timeout: 1500 });
    } else {
        setTimeout(load, 100);
    }
})();
</script>
@endonce
