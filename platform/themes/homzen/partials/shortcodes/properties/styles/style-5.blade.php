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

    $propertiesForSale = $propertiesForSale ?? collect();
    $propertiesSold = $propertiesSold ?? collect();
    $eagerImageLimit = \App\Support\SerikHomepage::isHomepageRequest() ? 4 : 0;
@endphp

<section class="flat-section-v5 bg-surface flat-recommended flat-recommended-v2 property-top serik-hp-props">
    <div class="container">
        <header class="section-heading-block serik-hp-section-head serik-hp-props__head wow fadeIn" data-wow-delay=".2s" data-wow-duration="2000ms">
            <div class="serik-hp-section-head__copy">
                <h2 class="section-title mt-0">{{ $saleHeading }}</h2>
                <p class="serik-hp-subhead">{{ $saleSubheading }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ url('/map?transaction=For Sale') }}" class="serik-hp-link-btn">{{ __('Browse for Sale') }}</a>
                <a href="{{ url('/map') }}" class="serik-hp-link-btn">{{ __('View on Map') }} <x-core::icon name="ti ti-arrow-right" /></a>
            </div>
        </header>

        @if (isset($propertiesForSale) && $propertiesForSale->isNotEmpty())
            <div class="row g-3 g-lg-4 wow fadeInUpSmall mb-2 mb-md-3 serik-hp-props__grid" data-wow-delay=".2s" data-wow-duration="2000ms">
                @foreach($propertiesForSale as $property)
                    <div class="col-6 col-md-4 col-lg-3 col-xl-3 prop-box">
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
                <p class="serik-hp-subhead">{{ __('Ontario Residential recently sold/leased properties') }}</p>
            </div>
            @if(!auth('account')->check())
                <button type="button" class="tf-btn primary size-1 js-auth-open-login serik-hp-login-cta">
                    {{ __('Login to View Sold History') }}
                </button>
            @endif
        </header>

        @if (isset($propertiesSold) && $propertiesSold->isNotEmpty())
            <div class="row g-3 g-lg-4 wow fadeInUpSmall serik-hp-props__grid" data-wow-delay=".2s" data-wow-duration="2000ms">
                @foreach($propertiesSold as $property)
                    <div class="col-6 col-md-4 col-lg-3 col-xl-3 prop-box">
                        @include(Theme::getThemeNamespace('views.real-estate.properties.item-grid-home'))
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info text-center">{{ __('No sold properties found.') }}</div>
        @endif
    </div>
</section>

@if (\App\Support\SerikHomepage::isHomepageRequest())
    @include(Theme::getThemeNamespace('partials.seo.home-nav-mount'))
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
