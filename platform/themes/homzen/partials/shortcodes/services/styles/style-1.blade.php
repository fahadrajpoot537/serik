<style>
.wrap-service {
    display: flex;
    flex-wrap: wrap;
    gap: 10px; /* spacing between boxes */
}

/* Equal width for 6 boxes */
.box-service.hover-btn-view {
    flex: 0 0 calc((100% / 6) - 10px); /* 6 boxes per row */
    display: flex;
    flex-direction: column; /* stack icon + content vertically */
    justify-content: flex-start; /* start from top */
    align-items: stretch; /* make content stretch full width */
    box-sizing: border-box;
    min-height: 350px; /* optional consistent height */
}

/* Keep icon box fixed size */
.box-service .icon-box {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 150px !important;
}

/* Make content stretch so button always at bottom */
.box-service .content {
    display: flex;
    flex-direction: column;
    justify-content: space-between; /* pushes button to bottom */
    height: 100%;
}

/* Optional hover effect */
.box-service.hover-btn-view:hover {
    transform: translateY(-5px);
    transition: 0.3s;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .box-service.hover-btn-view {
        flex: 0 0 calc(33.333% - 13.33px);
    }
}
@media (max-width: 576px) {
    .box-service.hover-btn-view {
        flex: 0 0 calc(50% - 10px);
    }
}
.tf-sw-services {
    overflow: hidden;
}

.tf-sw-services .swiper-slide {
    height: auto;
}
</style>

<section @class(['flat-section', 'text-center' => $shortcode->centered_content]) @style(["background-color: $shortcode->background_color" => $shortcode->background_color])>
    <div class="container">
         
                     <h2 class="section-title mt-4" style="font-weight: 700;text-align:center;color: #000;">{!! BaseHelper::clean($shortcode->title) !!}</h2>
<div  style="font-weight: 700;text-align:center;">{!! BaseHelper::clean($shortcode->subtitle) !!}</div>
<br>
       @if($services)
    <div class="swiper tf-sw-services wow fadeInUpSmall"
         data-wow-delay=".4s"
         data-wow-duration="2000ms">

        <div class="swiper-wrapper">

            @foreach($services as $service)
                <div class="swiper-slide">
                    <div class="box-service hover-btn-view">

                        <div class="icon-box" style="height: 100px !important;">
                            @if($service['icon_image'])
                                {{ RvMedia::image($service['icon_image'], $service['title'], attributes: ['class' => 'icon', 'data-bb-lazy' => 'false']) }}
                            @elseif($service['icon'])
                                <x-core::icon :name="$service['icon']" class="icon" />
                            @endif
                        </div>

                        <div class="content" style="height: 70%;">
                            <h6>{!! BaseHelper::clean($service['title']) !!}</h6>

                            <p class="description">
                                {!! BaseHelper::clean(nl2br($service['description'])) !!}
                            </p>

                            @if($service['button_url'])
                                <a href="{{ $service['button_url'] }}" class="btn-view style-1">
                                    <span class="text">{{ $service['button_label'] ?? __('Learn More') }}</span>
                                    <x-core::icon name="ti ti-arrow-right" class="icon" />
                                </a>
                            @endif
                        </div>

                    </div>
                </div>
            @endforeach

        </div>
    </div>
@endif

        {!! Theme::partial('shortcodes.services.partials.counters', compact('counters')) !!}
    </div>
</section>




<script>
    
    function initServicesSwiper() {
    if (typeof Swiper === 'undefined') return;

    const el = document.querySelector('.tf-sw-services');
    if (!el) return;

    new Swiper(el, {
        slidesPerView: 3,
        spaceBetween: 20,
        loop: true,

        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },

        speed: 800,

        breakpoints: {
            0: {
                slidesPerView: 2,
                spaceBetween: 10
            },
            576: {
                slidesPerView: 2,
                spaceBetween: 15
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 20
            },
            992: {
                slidesPerView: 4,
                spaceBetween: 20
            }
        }
    });
}

// safe init (Laravel fix)
window.addEventListener('load', function () {
    setTimeout(initServicesSwiper, 200);
});
</script>
