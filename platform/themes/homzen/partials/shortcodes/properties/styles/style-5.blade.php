<style>
    @media (max-width: 768px) {
        .property-top {
            margin-top: 0;
        }
    }

    @media (max-width: 992px) {
        .flat-recommended .row {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            gap: 14px;
            padding-bottom: 8px;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
        }

        .flat-recommended .row::-webkit-scrollbar {
            display: none;
        }

        .prop-box {
            flex: 0 0 72%;
            max-width: 72%;
            scroll-snap-align: start;
            padding-left: 0;
            padding-right: 0;
        }

        .prop-box:first-child {
            margin-left: 4px;
        }

        .prop-box:last-child {
            margin-right: 4px;
        }
    }

    .flat-recommended-v2.property-top {
        padding-top: 22px !important;
        padding-bottom: 22px !important;
        margin: 3px;
    }

    .flat-recommended-v2 .section-heading-block {
        margin-bottom: 10px;
    }

    .flat-recommended-v2 .section-divider {
        margin: 18px 0 16px;
        border-top: 1px solid #e2e8f0;
        padding-top: 0;
    }

    .flat-recommended-v2 .serik-prop-card__media {
        aspect-ratio: 16 / 9;
    }

    .flat-recommended-v2 .serik-prop-card__body {
        padding: 6px 8px 8px;
    }

    .flat-recommended-v2 .serik-prop-card__price {
        font-size: 1rem;
    }

    .flat-recommended-v2 .serik-prop-card__stats {
        font-size: 12px;
        margin-bottom: 3px;
    }

    .flat-recommended-v2 .serik-prop-card__address {
        font-size: 13px;
        margin-bottom: 2px;
    }

    .flat-recommended-v2 .serik-prop-card__mls,
    .flat-recommended-v2 .serik-prop-card__listed-date {
        font-size: 10px;
    }

    .flat-recommended-v2 .serik-prop-card__listed-date {
        margin-top: 4px;
    }

    .flat-recommended-v2 .serik-prop-card__badge,
    .flat-recommended-v2 .serik-prop-card__days {
        font-size: 10px;
        padding: 3px 7px;
    }

    @media (min-width: 992px) {
        .flat-recommended-v2 .prop-box {
            padding-left: 10px;
            padding-right: 10px;
        }
    }

    @media (max-width: 768px) {
        .flat-recommended-v2.property-top {
            padding-top: 18px !important;
            padding-bottom: 18px !important;
        }

        .flat-recommended-v2 .section-heading-block {
            margin-bottom: 10px;
        }

        .flat-recommended-v2 .section-divider {
            margin: 16px 0 14px;
        }

        .flat-recommended-v2 .section-title {
            font-size: 1.35rem !important;
            margin-top: 0 !important;
        }
    }
</style>

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
@endphp

<section class="flat-section-v5 bg-surface flat-recommended flat-recommended-v2 property-top">
    <div class="container">
        <div class="section-heading-block wow fadeIn" data-wow-delay=".2s" data-wow-duration="2000ms">
            <h2 class="section-title mt-0" style="font-weight: 700; text-align: left; color: #000;">{{ $saleHeading }}</h2>
            <div style="text-align: left; color: #666; font-size: 15px;">{{ $saleSubheading }}</div>
        </div>

        @if (isset($propertiesForSale) && $propertiesForSale->isNotEmpty())
            <div class="row g-3 wow fadeInUpSmall mb-2 mb-md-3" data-wow-delay=".2s" data-wow-duration="2000ms">
                @foreach($propertiesForSale as $property)
                    <div class="col-6 col-md-4 col-lg-3 col-xl-3 prop-box">
                        @include(Theme::getThemeNamespace('views.real-estate.properties.item-grid'))
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info text-center mb-3">{{ __('No active properties for sale found.') }}</div>
        @endif

        <div class="section-divider"></div>

        <div class="section-heading-block wow fadeIn" data-wow-delay=".2s" data-wow-duration="2000ms">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="section-title mt-0" style="font-weight: 700; text-align: left; color: #000;">{{ __('Sold History') }}</h2>
                    <div style="text-align: left; color: #666; font-size: 15px;">{{ __('Ontario Residential recently sold/leased properties') }}</div>
                </div>
                @if(!auth('account')->check())
                    <button class="tf-btn primary size-1 js-auth-open-login" style="height: fit-content; text-transform: none;">
                        Login to View Sold History
                    </button>
                @endif
            </div>
        </div>

        @if (isset($propertiesSold) && $propertiesSold->isNotEmpty())
            <div class="row g-3 wow fadeInUpSmall" data-wow-delay=".2s" data-wow-duration="2000ms">
                @foreach($propertiesSold as $property)
                    <div class="col-6 col-md-4 col-lg-3 col-xl-3 prop-box">
                        @include(Theme::getThemeNamespace('views.real-estate.properties.item-grid'))
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info text-center">{{ __('No sold properties found.') }}</div>
        @endif

        @if ($shortcode->button_label && $shortcode->button_url)
            <div class="text-center mt-5">
                <a href="{{ $shortcode->button_url }}" class="tf-btn primary size-1">
                    {{ $shortcode->button_label }}
                </a>
            </div>
        @endif
    </div>
</section>

<script>
    
  document.addEventListener('DOMContentLoaded', () => {

    // Select ALL elements using class instead of id
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