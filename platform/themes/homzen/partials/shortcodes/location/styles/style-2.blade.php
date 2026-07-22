<style>
.grid-location{
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

/* Tablet */
@media (max-width: 991px){
    .grid-location{
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Mobile (2 per row) */
@media (max-width: 576px){
    .grid-location{
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Location cards: shorter height, proportional width */
.flat-location-v2 .box-location-v2 .box-img{
    width:100%;
    aspect-ratio:4 / 3;
    max-height:none !important;
    overflow:hidden;
    border-radius:12px;
}

.flat-location-v2 .box-location-v2 .box-img img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.flat-location-v2 .box-location-v2 .content{
    padding-top:10px;
}

.flat-location-v2 .box-location-v2 .content .link{
    font-size:15px !important;
    line-height:1.35;
    margin:0;
}

@media (max-width: 767px) {
    .flat-location-v2 .box-location-v2 .content .link{
        font-size:13px !important;
    }
}
.tf-sw-locations {
    overflow: hidden;
}

.tf-sw-locations .swiper-wrapper {
    display: flex;
}

.tf-sw-locations .swiper-slide {
    flex-shrink: 0;
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
                        {{ RvMedia::image($location->image, $location->name, 'medium-rectangle') }}
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
 function initLocationsSwiper() {
    if (typeof Swiper === 'undefined') return;

    const el = document.querySelector('.tf-sw-locations');
    if (!el) return;

    new Swiper(el, {
        slidesPerView: 4,
        spaceBetween: 20,
        loop: true,

        autoplay: {
            delay: 2500,
            disableOnInteraction: false,
        },

        speed: 800,

        breakpoints: {
            0: { slidesPerView: 2, spaceBetween: 10 },
            576: { slidesPerView: 2, spaceBetween: 15 },
            768: { slidesPerView: 2, spaceBetween: 20 },
            992: { slidesPerView: 3, spaceBetween: 20 },
            1200: { slidesPerView: 4, spaceBetween: 20 }
        }
    });
}

// run safely multiple times (fixes Laravel rendering issues)
setTimeout(initLocationsSwiper, 300);
setTimeout(initLocationsSwiper, 1000);
</script>
