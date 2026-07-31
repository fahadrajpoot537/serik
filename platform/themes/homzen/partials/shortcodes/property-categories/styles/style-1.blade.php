<style>
@media (max-width: 768px) {
    .main-heading-cat{
        zoom:0.8;
    }
}
#page-home .flat-categories .homeya-categories {
    box-shadow: none;
    background-color: #fff !important;
    padding: 1.15rem 1rem 1rem !important;
}
#page-home .flat-categories .homeya-categories .content {
    zoom: 1 !important;
}
</style>


<section class="flat-section flat-categories" @style(["background-color: $shortcode->background_color" => $shortcode->background_color])>
    <div class="container">
        {!! Theme::partial('shortcode-heading', [
            'shortcode' => $shortcode,
            'buttonUrl' => url('/map'),
            'buttonLabel' => $shortcode->button_label ?: __('View All'),
            'hasButton' => (bool) $shortcode->button_label,
        ]) !!}

        <div class="wrap-categories wow fadeInUpSmall" data-wow-delay=".2s" data-wow-duration="2000ms">
            <div
                class="swiper tf-sw-categories"
                data-preview-lg="6"
                data-preview-md="4"
                data-preview-sm="3"
                data-space="30"
                {!! Theme::partial('shortcode-slider-attributes', compact('shortcode')) !!}
            >
                <div class="swiper-wrapper" style="padding: 20px 5px;">
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
@endphp

<div class="swiper-slide" style="background-color:rgba(255,255,255,0);margin-right:0 !important;">

    <a href="{{ $seoUrl }}"
       class="homeya-categories"
       title="{{ $category->PropertySubType }}">

        <div class="content text-center">

            <h6 class="main-heading-cat">

                {{ $category->PropertySubType === 'Att/Row/Townhouse'
                    ? 'Freehold Townhouse'
                    : $category->PropertySubType }}

            </h6>

            <p class="mt-4 text-variant-1">

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
            <!--div class="box-navigation">
                <div class="navigation style-1 swiper-nav-next nav-next-category">
                    <x-core::icon name="ti ti-chevron-left" />
                </div>
                <div class="navigation style-1 swiper-nav-prev nav-prev-category">
                    <x-core::icon name="ti ti-chevron-right" />
                </div>
            </div-->
        </div>
    </div>
</section>
