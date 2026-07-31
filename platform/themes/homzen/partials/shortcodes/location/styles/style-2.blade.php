<style>
/* Location carousel: compact cards, 5 visible on desktop */
.flat-location-v2 .tf-sw-locations {
    overflow: hidden;
    width: 100%;
}

.flat-location-v2 .tf-sw-locations .swiper-slide {
    height: auto;
    box-sizing: border-box;
}

.flat-location-v2 .box-location-v2 {
    display: block;
    width: 100%;
}

.flat-location-v2 .box-location-v2 .box-img {
    width: 100%;
    aspect-ratio: 4 / 3;
    max-height: 140px !important;
    overflow: hidden;
    border-radius: 14px;
    border: 1px solid rgba(22, 30, 45, 0.08);
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04), 0 8px 24px rgba(16, 24, 40, 0.06);
    background: #f3f4f6;
}

.flat-location-v2 .box-location-v2 .box-img img {
    width: 100% !important;
    height: 100% !important;
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
    display: block;
}

.flat-location-v2 .box-location-v2 .content {
    padding-top: 8px;
}

.flat-location-v2 .box-location-v2 .content .link {
    font-size: 14px !important;
    line-height: 1.35;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@media (min-width: 992px) {
    .flat-location-v2 .box-location-v2 .box-img {
        max-height: 160px !important;
    }
}

@media (max-width: 767px) {
    .flat-location-v2 .box-location-v2 .box-img {
        max-height: 120px !important;
    }

    .flat-location-v2 .box-location-v2 .content .link {
        font-size: 12px !important;
    }
}
</style>

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
    class="flat-section flat-location-v2" 
    @style(["background-color: $shortcode->background_color" => $shortcode->background_color])
>
    <div class="container">
        {!! Theme::partial('shortcode-heading', compact('shortcode')) !!}

        <div class="swiper tf-sw-locations wow fadeInUpSmall"
     data-wow-delay=".4s"
     data-wow-duration="2000ms">

    <div class="swiper-wrapper">

        @foreach($locations as $location)
            <div class="swiper-slide">
                <a href="{{ url('/on/houses-for-sale-in-' . strtolower(urlencode($location->name)) . '/map') }}"
                   class="box-location-v2 hover-img location-item">

                    <div class="box-img img-style">
                        {{ RvMedia::image($location->image, $location->name, 'medium-rectangle', attributes: ['width' => 400, 'height' => 300, 'decoding' => 'async', 'loading' => 'lazy']) }}
                    </div>

                    <div class="content">
                        <h3 class="link">
                            House for sale {{ $location->name }}
                        </h3>
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
        spaceBetween: 16,
        loop: true,
        watchOverflow: true,
        speed: 800,
        autoplay: {
            delay: 2500,
            disableOnInteraction: false,
        },
        breakpoints: {
            0: { slidesPerView: 2, spaceBetween: 10 },
            480: { slidesPerView: 2.3, spaceBetween: 12 },
            576: { slidesPerView: 3, spaceBetween: 12 },
            768: { slidesPerView: 3.5, spaceBetween: 14 },
            992: { slidesPerView: 4, spaceBetween: 16 },
            1200: { slidesPerView: 5, spaceBetween: 16 }
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

window.addEventListener('DOMContentLoaded', function () {
    bootOntarioLocationsSwiper();
});
window.addEventListener('load', function () {
    bootOntarioLocationsSwiper(6);
});
</script>
