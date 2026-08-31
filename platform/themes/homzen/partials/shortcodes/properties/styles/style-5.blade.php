@php
    $visitorCity = $visitorCity ?? null;
    try {
        if ($visitorCity === null && class_exists(\Theme\homzen\Supports\VisitorCityHelper::class)) {
            $visitorCity = \Theme\homzen\Supports\VisitorCityHelper::get();
        }
    } catch (\Throwable) {
        $visitorCity = null;
    }

    $saleHeading = $visitorCity
        ? __('Featured Properties in :city', ['city' => $visitorCity])
        : __('Properties for Sale');
    $saleSubheading = $visitorCity
        ? __('Latest listings near you in :city', ['city' => $visitorCity])
        : __('Ontario Residential properties currently available for sale');
    $locationLabel = $locationLabel ?? ($visitorCity ?: null);
    $locationSource = $locationSource ?? ($visitorCity ? 'session' : 'fallback');
    $featuredHydrate = ! empty($featuredHydrate);

    $propertiesForSale = $propertiesForSale ?? collect();
    $propertiesSold = $propertiesSold ?? collect();
    $eagerImageLimit = \App\Support\SerikHomepage::isHomepageRequest() ? 3 : 0;
    $homepageCardLimit = 6;
@endphp

<section class="flat-section-v5 bg-surface flat-recommended flat-recommended-v2 property-top serik-hp-props" data-ssr-city="{{ strtolower((string) ($visitorCity ?: 'ontario')) }}" data-hydrate-url="{{ $featuredHydrate ? '' : route('public.ajax.homepage-featured-properties') }}">
    <div class="container">
        <header class="section-heading-block serik-hp-section-head serik-hp-props__head wow fadeIn" data-wow-delay=".2s" data-wow-duration="2000ms">
            <div class="serik-hp-section-head__copy">
                <h2 class="section-title mt-0">{{ $saleHeading }}</h2>
                <p class="serik-hp-subhead">{{ $saleSubheading }}</p>
                @if($locationLabel)
                    <p class="serik-hp-near-label mb-0">{{ $locationSource === 'fallback' ? __('Showing properties near Ontario') : __('Showing properties near :city', ['city' => $locationLabel]) }}</p>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ url('/map?transaction=For Sale') }}" class="serik-hp-link-btn">{{ __('Browse for Sale') }}</a>
                <a href="{{ url('/map') }}" class="serik-hp-link-btn">{{ __('View on Map') }} <x-core::icon name="ti ti-arrow-right" /></a>
            </div>
        </header>

        @if (isset($propertiesForSale) && $propertiesForSale->isNotEmpty())
            <div class="row g-3 g-lg-4 wow fadeInUpSmall mb-2 mb-md-3 serik-hp-props__grid" data-wow-delay=".2s" data-wow-duration="2000ms">
                @foreach($propertiesForSale->take($homepageCardLimit) as $property)
                    <div class="col-6 col-md-6 col-lg-4 prop-box">
                        @include(Theme::getThemeNamespace('views.real-estate.properties.item-grid-home'), [
                            'lazyImage' => $loop->iteration > $eagerImageLimit,
                        ])
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info text-center mb-3">{{ __('No active properties for sale found.') }}</div>
        @endif

        <div class="section-divider serik-hp-props__divider" role="separator"></div>

        <header class="section-heading-block serik-hp-section-head serik-hp-props__head wow fadeIn" data-wow-delay=".2s" data-wow-duration="2000ms">
            <div class="serik-hp-section-head__copy">
                <h2 class="section-title mt-0">{{ __('Sold History') }}</h2>
                <p class="serik-hp-subhead">{{ $visitorCity
                    ? __('Recently sold and leased homes near :city', ['city' => $visitorCity])
                    : __('Ontario Residential recently sold/leased properties') }}</p>
            </div>
            @if(! (auth('account')->check() || auth()->check()))
                <button type="button" class="tf-btn primary size-1 js-auth-open-login serik-hp-login-cta">
                    {{ __('Login to View Sold History') }}
                </button>
            @endif
        </header>

        @if (isset($propertiesSold) && $propertiesSold->isNotEmpty())
            <div class="row g-3 g-lg-4 wow fadeInUpSmall serik-hp-props__grid" data-wow-delay=".2s" data-wow-duration="2000ms">
                @foreach($propertiesSold->take($homepageCardLimit) as $property)
                    <div class="col-6 col-md-6 col-lg-4 prop-box">
                        @include(Theme::getThemeNamespace('views.real-estate.properties.item-grid-home'))
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info text-center">{{ __('No sold properties found.') }}</div>
        @endif
    </div>
</section>

@if (! $featuredHydrate && \App\Support\SerikHomepage::isHomepageRequest())
    @include(Theme::getThemeNamespace('partials.seo.home-nav-mount'))
@endif

@if (! $featuredHydrate)
<script>
(function () {
  var section = document.querySelector('.serik-hp-props[data-hydrate-url]');
  if (!section || !section.dataset.hydrateUrl || section.dataset.hydrated === '1') return;

  function visitorCity() {
    try {
      var cookie = (document.cookie.match(/(?:^|;\s*)serik_visitor_city=([^;]+)/) || [])[1];
      if (cookie) return decodeURIComponent(cookie.replace(/\+/g, ' ')).trim();
    } catch (e) {}
    try {
      var loc = window.SerikVisitorLocation && window.SerikVisitorLocation.getSessionLocation && window.SerikVisitorLocation.getSessionLocation();
      if (loc && loc.city) return String(loc.city).trim();
    } catch (e2) {}
    return '';
  }

  function slug(value) {
    return String(value || '').toLowerCase().replace(/\s+/g, '-');
  }

  function finiteCoord(value) {
    var n = Number(value);
    return Number.isFinite(n) ? n : null;
  }

  function hydrateWith(city, lat, lng) {
    if (section.dataset.hydrated === '1') return;
    var ssr = (section.dataset.ssrCity || 'ontario').toLowerCase();
    var hasCoords = finiteCoord(lat) != null && finiteCoord(lng) != null;
    var citySlug = slug(city);
    if (!hasCoords && (!city || citySlug === ssr || citySlug === 'ontario')) return;
    section.dataset.hydrated = '1';
    var url = section.dataset.hydrateUrl;
    var params = [];
    if (city && citySlug !== 'ontario') params.push('city=' + encodeURIComponent(citySlug));
    if (hasCoords) {
      params.push('lat=' + encodeURIComponent(String(lat)));
      params.push('lng=' + encodeURIComponent(String(lng)));
    }
    if (!params.length) {
      section.dataset.hydrated = '0';
      return;
    }
    url += (url.indexOf('?') >= 0 ? '&' : '?') + params.join('&');
    fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
      .then(function (res) { return res.ok ? res.text() : ''; })
      .then(function (html) {
        if (!html) {
          section.dataset.hydrated = '0';
          return;
        }
        var wrap = document.createElement('div');
        wrap.innerHTML = html;
        var next = wrap.querySelector('.serik-hp-props');
        if (next && section.parentNode) {
          next.dataset.hydrated = '1';
          section.replaceWith(next);
          if (typeof window.serikInitWishlist === 'function') {
            window.serikInitWishlist();
          }
        } else {
          section.dataset.hydrated = '0';
        }
      })
      .catch(function () {
        section.dataset.hydrated = '0';
      });
  }

  function fromLocation(loc) {
    if (!loc || loc.source === 'unavailable' || loc.source === 'default' || loc.source === 'fallback') {
      return false;
    }
    var lat = finiteCoord(loc.lat);
    var lng = finiteCoord(loc.lng);
    var city = loc.city ? String(loc.city).trim() : visitorCity();
    if (lat == null || lng == null) {
      if (!city) return false;
      hydrateWith(city);
      return true;
    }
    hydrateWith(city, lat, lng);
    return true;
  }

  function hydrateFromApi() {
    fetch('/api/v1/visitor-location', { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (res) { return res.ok ? res.json() : null; })
      .then(function (data) {
        if (fromLocation(data)) return;
        var city = visitorCity();
        if (city) hydrateWith(city);
      })
      .catch(function () {});
  }

  function hydrate(event) {
    if (section.dataset.hydrated === '1') return;
    if (event && event.detail && fromLocation(event.detail)) return;

    if (window.SerikVisitorLocation && typeof window.SerikVisitorLocation.detectLocation === 'function') {
      window.SerikVisitorLocation.detectLocation({ preferCached: true, preferBrowser: false })
        .then(function (loc) {
          if (fromLocation(loc)) return;
          hydrateFromApi();
        })
        .catch(hydrateFromApi);
      return;
    }

    try {
      var cached = window.SerikVisitorLocation && window.SerikVisitorLocation.getSessionLocation && window.SerikVisitorLocation.getSessionLocation();
      if (fromLocation(cached)) return;
    } catch (e) {}

    var city = visitorCity();
    if (city) {
      hydrateWith(city);
      return;
    }
    hydrateFromApi();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', hydrate);
  } else {
    hydrate();
  }
  document.addEventListener('serik:visitor-location', hydrate);
})();
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
    const currencyInputs = document.querySelectorAll('.price_main');
    function formatCurrency(value) {
        let number = value.replace(/[^0-9.]/g, '');
        number = parseFloat(number);
        if (!isNaN(number)) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(number);
        }
        return '';
    }
    currencyInputs.forEach((currencyInput) => {
        currencyInput.addEventListener('input', (e) => {
            e.target.value = formatCurrency(e.target.value);
        });
    });
});
</script>
