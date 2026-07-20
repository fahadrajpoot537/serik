<style>
@media (max-width: 768px) {
    .main-heading-cat{
        zoom:0.8;
    }
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
    'Duplex'
];

// Fetch from DB and order by custom sequence
$propertySubTypes = \Illuminate\Support\Facades\DB::table('re_properties')
    ->select('PropertySubType', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
    ->whereIn('PropertySubType', $allowedTypes)
    ->groupBy('PropertySubType')
    ->orderByRaw("FIELD(PropertySubType, 'Detached','Semi-Detached','Att/Row/Townhouse','Condo Townhouse','Condo Apartment','Duplex')")
    ->get();
@endphp


@foreach ($propertySubTypes as $category)

@php
    $seoUrl = url('/map') . '?subtypes=' . urlencode($category->PropertySubType);
@endphp

<div class="swiper-slide" style="background-color:rgba(255,255,255,0);margin-right:0 !important;">

    <a href="{{ $seoUrl }}"
       class="homeya-categories"
       title="{{ $category->PropertySubType }}"
       style="box-shadow:0 4px 8px rgba(0,0,0,0.2),0 6px 20px rgba(0,0,0,0.19);background-color:#ffeaeb;padding:15px 12px 12px;">

        <div class="content text-center" style="zoom:0.6;">

            <h6 class="main-heading-cat" style="height:60px;">

                {{ $category->PropertySubType === 'Att/Row/Townhouse'
                    ? 'Freehold Townhouse'
                    : $category->PropertySubType }}

            </h6>

            <p class="mt-4 text-variant-1" style="height:35px;">

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
