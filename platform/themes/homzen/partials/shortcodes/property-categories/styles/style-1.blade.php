<section class="flat-section flat-categories serik-hp-cats" @style(["background-color: $shortcode->background_color" => $shortcode->background_color])>
    <div class="container">
        <div class="serik-hp-section-head">
            {!! Theme::partial('shortcode-heading', [
                'shortcode' => $shortcode,
                'buttonUrl' => url('/map'),
                'buttonLabel' => $shortcode->button_label ?: __('View All'),
                'hasButton' => (bool) $shortcode->button_label,
            ]) !!}
        </div>

        {{-- No WOW here: hiding this before Swiper init collapses height to 0 on mobile --}}
        <div class="wrap-categories serik-hp-cats__wrap">
            <div
                class="swiper tf-sw-categories"
                data-preview-lg="6"
                data-preview-md="4"
                data-preview-sm="3"
                data-space="18"
                {!! Theme::partial('shortcode-slider-attributes', compact('shortcode')) !!}
            >
                <div class="swiper-wrapper">
                    @php
                        $allowedTypes = [
                            'Detached',
                            'Semi-Detached',
                            'Att/Row/Townhouse',
                            'Condo Townhouse',
                            'Condo Apartment',
                            'Duplex',
                        ];
                        $propertySubTypes = \App\Support\RealEstateCountCache::propertySubTypeCounts($allowedTypes);
                    @endphp

                    @foreach ($propertySubTypes as $category)
                        @php
                            $seoUrl = url('/map') . '?subtypes=' . urlencode($category->PropertySubType);
                            $label = $category->PropertySubType === 'Att/Row/Townhouse'
                                ? 'Freehold Townhouse'
                                : $category->PropertySubType;
                        @endphp
                        <div class="swiper-slide">
                            <a href="{{ $seoUrl }}" class="homeya-categories serik-hp-cat-card" title="{{ $category->PropertySubType }}">
                                <div class="content text-center serik-hp-cat-card__content">
                                    <span class="serik-hp-cat-card__count">{{ number_format((int) $category->total) }}</span>
                                    <h6 class="main-heading-cat">{{ $label }}</h6>
                                    <p class="mt-4 text-variant-1 serik-hp-cat-card__meta">
                                        @if ($category->total == 1)
                                            1 Property
                                        @else
                                            {{ $category->total }} Properties
                                        @endif
                                    </p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
