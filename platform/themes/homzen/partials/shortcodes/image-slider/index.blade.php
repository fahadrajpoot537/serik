

<style>
@media (min-width: 768px) {
    .city-swiper-section {
        display: none;
    }
}

.city-card {
    background: #fff;
    padding: 16px;
    border-radius: 10px;
    
    box-shadow: rgba(50, 50, 93, 0.25) 0px 50px 100px -20px, rgba(0, 0, 0, 0.3) 0px 30px 60px -30px, rgba(10, 37, 64, 0.35) 0px -2px 6px 0px inset;
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

/* CRITICAL SWIPER FIX */

</style>

<section class="flat-section-v4 city-swiper-section">
    <div class="container">
 <h3 style="    font-size: 22px;
    font-weight: 800;">Homes for Sale in Popular Cities</h3>
        <div class="swiper city-swiper" data-speed="1500">
            <div class="swiper-wrapper">
               

                @php
                    $cities = [
                        'brampton' => 'Brampton',
                        'mississauga' => 'Mississauga',
                        'toronto' => 'Toronto',
                        'brampton' => 'Hamilton',
                        'mississauga' => 'Kitchener',
                        'toronto' => 'Ottawa',
                         'brampton' => 'Vaughan',
                        'mississauga' => 'Oakville',
                        'toronto' => 'Milton',
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
function initLocationsSwiper() {
    if (typeof Swiper === 'undefined') return;

    document.querySelectorAll('.city-swiper').forEach((el) => {

        // prevent double init
        if (el.swiper) return;

        const speed = parseInt(el.dataset.speed || 2500);

        new Swiper(el, {
            slidesPerView: 1,
            spaceBetween: 5,
            loop: true,

            autoplay: {
                delay: speed,
                disableOnInteraction: false,
            },

            speed: 800,

            breakpoints: {
                0: { slidesPerView: 2 },
                576: { slidesPerView: 2 },
                768: { slidesPerView: 2 },
                992: { slidesPerView: 2 },
                1200: { slidesPerView: 2 }
            }
        });
    });
}

// safer init (Laravel / AJAX friendly)
setTimeout(initLocationsSwiper, 300);
setTimeout(initLocationsSwiper, 1000);
</script>