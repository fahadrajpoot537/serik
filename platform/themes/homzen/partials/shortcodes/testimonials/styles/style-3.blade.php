<section
    class="flat-section-v2 flat-testimonial-v2 wow fadeInUpSmall"
    data-wow-delay=".2s"
    data-wow-duration="2000ms"
    @style(["background-color: $shortcode->background_color" => $shortcode->background_color])
>
    <div class="container">
        @if($shortcode->title || $shortcode->subtitle || $shortcode->description)
            <div class="box-title text-center position-relative">
               
                @if($shortcode->title)
                    <h3 class="section-title mt-4 text-white">
                        {!! BaseHelper::clean($shortcode->title) !!}
                    </h3>
                @endif
                 @if($shortcode->subtitle)
                    <div class="text-subtitle text-white">
                        {!! BaseHelper::clean($shortcode->subtitle) !!}
                    </div>
                @endif

                @if ($shortcode->description)
                    <p class="p-16 body-2 text-white mt-3">{!! BaseHelper::clean($shortcode->description) !!}</p>
                @endif
            </div>
        @endif

        <div
            class="swiper tf-sw-testimonial"
            data-preview-lg="3"
            data-preview-md="2"
            data-preview-sm="2"
            data-space="30"
            {!! Theme::partial('shortcode-slider-attributes', compact('shortcode')) !!}
        >
            <div class="swiper-wrapper">
                @foreach ($testimonials as $testimonial)
                    <div class="swiper-slide">
                        <div class="box-tes-item style-1">
                         <a href="https://www.google.com/search?sca_esv=f9a3c13abd781ae7&sxsrf=ANbL-n7-kDu7-cWD7Inykzfg9UCVNzbd1A:1776804112765&si=AL3DRZEsmMGCryMMFSHJ3StBhOdZ2-6yYkXd_doETEE1OR-qOfvoulo1K3CdIC5M45JUCC4r873m2qwN7EicjGCMgYWtNzBTKNl8PkUaJZYYaU6q_EC5LNKLYfGq1WitFm3vQOmt5TFOzgO3dLn3bfm3a6YNV2Pe8g%3D%3D&q=Serik+Realty+Inc.+Reviews&sa=X&ved=2ahUKEwiV_OeP5_-TAxWkmSsGHSvSEY8Q0bkNegQIRRAH&biw=1482&bih=704&dpr=1.25" target="_blank"> @include(Theme::getThemeNamespace('partials.shortcodes.testimonials.partials.content'))</a>  
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="sw-pagination sw-pagination-testimonial"></div>
        </div>
    </div>
</section>


<script>
const testimonialSwiper = new Swiper('.tf-sw-testimonial', {
    slidesPerView: 1,
    spaceBetween: 30,
    loop: true,
    autoplay: {
        delay: 3000,
        disableOnInteraction: false,
    },
});
    const swiperEl = document.querySelector('.tf-sw-testimonial');

if (swiperEl) {
    swiperEl.addEventListener('mouseenter', () => {
        testimonialSwiper.autoplay.stop();
    });

    swiperEl.addEventListener('mouseleave', () => {
        testimonialSwiper.autoplay.start();
    });
}
</script>
