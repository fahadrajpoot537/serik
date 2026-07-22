<section
    class="flat-section flat-testimonial-v4" id="testimonials"
    @style(["background-color: $shortcode->background_color" => $shortcode->background_color])
>



<div class="container">
    
    
    {!! Theme::partial('shortcode-heading', ['shortcode' => $shortcode]) !!}

        @if ($shortcode->description)
            <p class="text-center body-2 mb-5">{!! BaseHelper::clean($shortcode->description) !!}</p>
        @endif
    
    <div class="swiper image-slider"  
         data-preview-lg="2"
         data-preview-md="2"
         data-preview-sm="1"
         data-space="30">
        <div class="swiper-wrapper">
            <div class="swiper-slide">
                <div class="slider-home2 img-animation wow">
                    <img src="https://serik.ca/storage/p1055739jpg-1.jpeg" alt="{{ img_alt(null, 'p1055739jpg-1.jpeg', __('Customer testimonial')) }}">
                </div>
            </div>
            <div class="swiper-slide">
                <div class="slider-home2 img-animation wow">
                    <img src="https://serik.ca/storage/p1055353jpg.jpeg" alt="{{ img_alt(null, 'p1055353jpg.jpeg', __('Customer testimonial')) }}">
                </div>
            </div>
            <div class="swiper-slide">
                <div class="slider-home2 img-animation wow">
                    <img src="https://serik.ca/storage/cashbackdushyantjpg.jpeg" alt="{{ img_alt(null, 'cashbackdushyantjpg.jpeg', __('Customer testimonial')) }}">
                </div>
            </div>
            <div class="swiper-slide">
                <div class="slider-home2 img-animation wow">
                    <img src="https://serik.ca/storage/p1055865-1jpg.jpeg" alt="{{ img_alt(null, 'p1055865-1jpg.jpeg', __('Customer testimonial')) }}">
                </div>
            </div>
        </div>

        <!-- Optional pagination/navigation -->
       
    </div>
</div>


    <div class="container">
        

        <div
            class="swiper tf-sw-testimonial"
            data-preview-lg="2"
            data-preview-md="2"
            data-preview-sm="2"
            data-space="30"
            {!! Theme::partial('shortcode-slider-attributes', compact('shortcode')) !!}
        >
            <div class="swiper-wrapper">
                @foreach ($testimonials as $testimonial)
                    <div class="swiper-slide">
                        <div class="box-tes-item style-2">
                            @include(Theme::getThemeNamespace('partials.shortcodes.testimonials.partials.content'))
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="sw-pagination sw-pagination-testimonial"></div>
        </div>
    </div>
</section>
<script>
    const imageSlider = new Swiper('.image-slider', {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: true,
        autoplay: {
            delay: 3000,      // 3 seconds per slide
            disableOnInteraction: false, // continue autoplay after user interacts
        },
        pagination: {
            el: '.image-slider-pagination',
            clickable: true,
        },
         breakpoints: {
            640: { slidesPerView: 1 },
            768: { slidesPerView: 2 },
            1024: { slidesPerView: 2 },
        },
    });
</script>
