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
        $seoListingTitle = \App\Support\PageH1::ontarioListingH1();
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
/* Listing SEO nav accordion (AJAX-injected HTML) */
@media (max-width: 767.98px) {
    #serikSeoNavMount .seo-nav-row { row-gap: 0.65rem !important; margin: 0 !important; }
    #serikSeoNavMount .seo-nav-col {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
    }
    #serikSeoNavMount .seo-nav-title {
        margin: 0 !important;
        padding: 0 !important;
        border-bottom: 0 !important;
    }
    #serikSeoNavMount .seo-nav-block {
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        background: #fff !important;
        padding: 0.75rem 0.9rem !important;
        height: auto !important;
    }
    #serikSeoNavMount .seo-nav-toggle {
        display: flex !important;
        width: 100% !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 0.75rem !important;
        border: 0 !important;
        background: transparent !important;
        padding: 0 !important;
        text-align: left !important;
        color: inherit !important;
        font: inherit !important;
        font-weight: 700 !important;
        cursor: pointer !important;
    }
    #serikSeoNavMount .seo-nav-title-static { display: none !important; }
    #serikSeoNavMount .seo-nav-toggle__icon {
        flex-shrink: 0 !important;
        width: 1.5rem !important;
        height: 1.5rem !important;
        border-radius: 999px !important;
        border: 1px solid #cbd5e1 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.05rem !important;
        line-height: 1 !important;
        color: #0255a1 !important;
        font-weight: 600 !important;
        background: #fff !important;
    }
    #serikSeoNavMount .seo-nav-block.is-open .seo-nav-toggle__icon {
        transform: rotate(45deg);
        background: #e8f2fc !important;
    }
    #serikSeoNavMount .seo-nav-list {
        display: none !important;
        margin-top: 0.65rem !important;
        padding-top: 0.55rem !important;
        border-top: 1px solid #e8ecf1 !important;
        max-height: none !important;
        overflow: visible !important;
    }
    #serikSeoNavMount .seo-nav-block.is-open .seo-nav-list {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.25rem !important;
        max-height: none !important;
        overflow: visible !important;
    }
}
@media (min-width: 768px) {
    #serikSeoNavMount .seo-nav-toggle { display: none !important; }
    #serikSeoNavMount .seo-nav-title-static { display: block !important; }
}
</style>
<script>
(function () {
    var mount = document.getElementById('serikSeoNavMount');
    if (!mount || !mount.dataset.url) return;

    function enhanceAccordion(root) {
        if (!root) return;
        root.querySelectorAll('.seo-nav-block').forEach(function (block, index) {
            block.setAttribute('data-seo-nav-block', '');
            var title = block.querySelector('.seo-nav-title');
            var list = block.querySelector('.seo-nav-list');
            if (!title || !list) return;
            if (block.querySelector('[data-seo-nav-toggle]')) return;

            var labelText = (title.textContent || '').replace(/\s+/g, ' ').trim();
            var sectionId = list.id || ('seo-nav-listing-' + index);
            list.id = sectionId;

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'seo-nav-toggle';
            btn.setAttribute('data-seo-nav-toggle', '');
            btn.setAttribute('aria-expanded', 'false');
            btn.setAttribute('aria-controls', sectionId);
            btn.innerHTML = '<span class="seo-nav-toggle__label"></span><span class="seo-nav-toggle__icon" aria-hidden="true">+</span>';
            btn.querySelector('.seo-nav-toggle__label').textContent = labelText;

            var staticTitle = document.createElement('span');
            staticTitle.className = 'seo-nav-title-static';
            staticTitle.textContent = labelText;

            title.textContent = '';
            title.appendChild(btn);
            title.appendChild(staticTitle);
        });
    }

    if (!window.__serikSeoNavAccordionBound) {
        window.__serikSeoNavAccordionBound = true;
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-seo-nav-toggle]');
            if (!btn) return;
            var block = btn.closest('[data-seo-nav-block]');
            if (!block) return;
            e.preventDefault();
            var open = block.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    var load = function () {
        var url = mount.dataset.url;
        var params = new URLSearchParams();
        params.set('context', 'properties');

        var city = mount.dataset.city || '';
        var pathMatch = window.location.pathname.match(
            /\/ontario\/([a-z0-9-]+)-(?:houses|house|townhouses|townhouse|condos|condo|apartments|apartment|detached-houses|semi-detached-houses)-for-(?:sale|lease)\/?$/i
        );
        if (!pathMatch) {
            pathMatch = window.location.pathname.match(/\/ontario\/.+?-for-(?:sale|lease)-in-([a-z0-9-]+)\/?$/i);
        }
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
                enhanceAccordion(mount);
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
