@php
$order = [
    'Brampton',
    'Mississauga',
    'Vaughan',
    'Milton',
    'Oakville',
    'Niagara Falls',
    'Toronto',
    'KWC'
];

$locations = $locations->sortBy(function ($location) use ($order) {
    return array_search($location->name, $order);
});
@endphp

<section
    class="flat-section flat-location-v2 serik-hp-locations"
    @style(["background-color: $shortcode->background_color" => $shortcode->background_color])
>
    <div class="container">
        <div class="serik-hp-section-head serik-hp-section-head--center">
            {!! Theme::partial('shortcode-heading', compact('shortcode')) !!}
        </div>

        <div class="swiper tf-sw-locations wow fadeInUpSmall serik-hp-locations__swiper"
             data-wow-delay=".4s"
             data-wow-duration="2000ms">
            <div class="swiper-wrapper">
                @foreach($locations as $location)
                    <div class="swiper-slide">
                        <a href="{{ url('/on/houses-for-sale-in-' . strtolower(urlencode($location->name)) . '/map') }}"
                           class="box-location-v2 hover-img location-item serik-hp-loc-card">
                            <div class="box-img img-style serik-hp-loc-card__media">
                                {{ RvMedia::image($location->image, $location->name, 'medium-rectangle', attributes: ['width' => 400, 'height' => 300, 'decoding' => 'async', 'loading' => 'lazy']) }}
                                <span class="serik-hp-loc-card__veil" aria-hidden="true"></span>
                                <div class="content serik-hp-loc-card__content">
                                    <span class="serik-hp-loc-card__label">{{ __('Homes for sale') }}</span>
                                    <h3 class="link serik-hp-loc-card__title">{{ $location->name }}</h3>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<script>
function initOntarioLocationsSwiper() {
    if (typeof Swiper === 'undefined') return;
    const el = document.querySelector('.flat-location-v2 .tf-sw-locations');
    if (!el || el.dataset.swiperReady === '1' || el.swiper) return;
    el.dataset.swiperReady = '1';
    new Swiper(el, {
        slidesPerView: 5,
        spaceBetween: 18,
        loop: true,
        watchOverflow: true,
        speed: 800,
        autoplay: { delay: 2500, disableOnInteraction: false },
        breakpoints: {
            0: { slidesPerView: 1.35, spaceBetween: 12 },
            480: { slidesPerView: 2.1, spaceBetween: 12 },
            576: { slidesPerView: 2.5, spaceBetween: 14 },
            768: { slidesPerView: 3.2, spaceBetween: 16 },
            992: { slidesPerView: 4, spaceBetween: 18 },
            1200: { slidesPerView: 5, spaceBetween: 18 }
        }
    });
}
function bootOntarioLocationsSwiper(maxRetries) {
    var retries = 0;
    var limit = maxRetries || 12;
    var tick = function () {
        initOntarioLocationsSwiper();
        var el = document.querySelector('.flat-location-v2 .tf-sw-locations');
        if (el && (el.dataset.swiperReady === '1' || el.swiper)) return;
        retries++;
        if (retries < limit) setTimeout(tick, 180);
    };
    tick();
}
window.addEventListener('DOMContentLoaded', function () { bootOntarioLocationsSwiper(); });
window.addEventListener('load', function () { bootOntarioLocationsSwiper(6); });
</script>
