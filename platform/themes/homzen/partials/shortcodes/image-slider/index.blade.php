

<style>
@media (min-width: 768px) {
    .city-swiper-section {
        display: none;
    }
}

.city-card {
    background: #fff;
    padding: 16px;
    border-radius: 14px;
    height: 100%;
    border: 1px solid rgba(22, 30, 45, 0.08);
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04), 0 8px 24px rgba(16, 24, 40, 0.06);
    width: 100%;
}

.city-title {
    display: block;
    font-weight: 600;
    margin-bottom: 10px;
    color: #000;
    font-size: 15px;
    text-decoration: none;
}

.property-types {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.property-types a {
   
    font-size: 12px;
    color: #555;
    text-decoration: none;
}

@media (max-width: 767px) {
    .city-swiper-section .city-swiper {
        overflow: visible;
        padding: 4px 4px 28px;
    }

    .city-swiper-section .city-swiper .swiper-wrapper {
        display: flex;
        align-items: stretch;
    }

    .city-swiper-section .city-swiper .swiper-slide {
        height: auto;
        box-sizing: border-box;
        padding-right: 14px;
    }

    .city-swiper-section .city-card {
        margin: 0;
    }
}

</style>

<section class="flat-section-v4 city-swiper-section">
    <div class="container">
 <h3 class="section-title" style="font-size: 22px; font-weight: 700; letter-spacing: -0.02em;">Homes for Sale in Popular Cities</h3>
        <div class="swiper city-swiper" data-speed="1500">
            <div class="swiper-wrapper">
               

                @php
                    $cities = [
                        'brampton' => 'Brampton',
                        'mississauga' => 'Mississauga',
                        'toronto' => 'Toronto',
                        'hamilton' => 'Hamilton',
                        'kitchener' => 'Kitchener',
                        'ottawa' => 'Ottawa',
                        'vaughan' => 'Vaughan',
                        'oakville' => 'Oakville',
                        'milton' => 'Milton',
                    ];

                    $types = [
                        'Detached' => 'Detached Houses',
                        'Semi-Detached' => 'Semi-Detached',
                        'Condo Townhouse' => 'Townhouses',
                        'Condo Apartment' => 'Condos & Apartments',
                    ];
                @endphp

                @foreach($cities as $slug => $cityName)
                    <div class="swiper-slide">
                        <div class="city-card">

                            <a href="{{ url("/map?transaction=For Sale&city=$slug") }}" class="city-title">
                                {{ $cityName }}
                            </a>

                            <div class="property-types">
                                @foreach($types as $typeKey => $typeLabel)
                                    <a href="{{ url("/map?transaction=For Sale&city=$slug&subtypes=$typeKey") }}">
                                        &gt; {{ $typeLabel }}
                                    </a>
                                @endforeach
                            </div>

                        </div>
                    </div>
                @endforeach

            </div>

            <div class="swiper-pagination"></div>

        </div>

    </div>
</section>





<script>
function initCitySwiperCards() {
    if (typeof Swiper === 'undefined') return;

    document.querySelectorAll('.city-swiper').forEach((el) => {
        if (el.swiper || el.dataset.swiperReady === '1') return;
        el.dataset.swiperReady = '1';

        const speed = parseInt(el.dataset.speed || 2500, 10);

        new Swiper(el, {
            slidesPerView: 1.2,
            spaceBetween: 0,
            loop: true,
            autoplay: {
                delay: speed,
                disableOnInteraction: false,
            },
            speed: 800,
            breakpoints: {
                0: { slidesPerView: 1.2, spaceBetween: 0 },
                576: { slidesPerView: 1.4, spaceBetween: 0 },
            }
        });
    });
}

function bootCitySwiperCards(maxRetries) {
    var retries = 0;
    var limit = maxRetries || 12;
    var tick = function () {
        initCitySwiperCards();
        var pending = document.querySelector('.city-swiper:not([data-swiper-ready="1"])');
        if (!pending) return;
        retries++;
        if (retries < limit) setTimeout(tick, 180);
    };
    tick();
}

window.addEventListener('DOMContentLoaded', function () {
    bootCitySwiperCards();
});
window.addEventListener('load', function () {
    bootCitySwiperCards(6);
});
</script>