<section
    class="flat-section-v2 flat-testimonial-v2 wow fadeInUpSmall serik-hp-reviews"
    data-wow-delay=".2s"
    data-wow-duration="2000ms"
    @style(["background-color: $shortcode->background_color" => $shortcode->background_color])
>
    <div class="container">
        @if($shortcode->title || $shortcode->subtitle || $shortcode->description)
            <div class="box-title text-center position-relative serik-hp-section-head serik-hp-section-head--center">
                @if($shortcode->subtitle)
                    <div class="text-subtitle text-white serik-hp-eyebrow serik-hp-eyebrow--light">
                        {!! BaseHelper::clean($shortcode->subtitle) !!}
                    </div>
                @endif
                @if($shortcode->title)
                    <h3 class="section-title mt-2 text-white">
                        {!! BaseHelper::clean($shortcode->title) !!}
                    </h3>
                @endif
                @if ($shortcode->description)
                    <p class="p-16 body-2 text-white mt-3 serik-hp-reviews__desc">{!! BaseHelper::clean($shortcode->description) !!}</p>
                @endif
            </div>
        @endif

        <div
            class="swiper tf-sw-testimonial"
            data-preview-lg="3"
            data-preview-md="2"
            data-preview-sm="1"
            data-space="24"
            {!! Theme::partial('shortcode-slider-attributes', compact('shortcode')) !!}
        >
            <div class="swiper-wrapper">
                @foreach ($testimonials as $testimonial)
                    <div class="swiper-slide">
                        <div class="box-tes-item style-1 serik-hp-review-card">
                            <a href="https://www.google.com/search?sca_esv=f9a3c13abd781ae7&sxsrf=ANbL-n7-kDu7-cWD7Inykzfg9UCVNzbd1A:1776804112765&si=AL3DRZEsmMGCryMMFSHJ3StBhOdZ2-6yYkXd_doETEE1OR-qOfvoulo1K3CdIC5M45JUCC4r873m2qwN7EicjGCMgYWtNzBTKNl8PkUaJZYYaU6q_EC5LNKLYfGq1WitFm3vQOmt5TFOzgO3dLn3bfm3a6YNV2Pe8g%3D%3D&q=Serik+Realty+Inc.+Reviews&sa=X&ved=2ahUKEwiV_OeP5_-TAxWkmSsGHSvSEY8Q0bkNegQIRRAH&biw=1482&bih=704&dpr=1.25" target="_blank" rel="noopener noreferrer">
                                @include(Theme::getThemeNamespace('partials.shortcodes.testimonials.partials.content'))
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="sw-pagination sw-pagination-testimonial"></div>
        </div>
    </div>
</section>

<script>
(function () {
    function bindTestimonialHover(el, swiperInstance) {
        if (!el || !swiperInstance || !swiperInstance.autoplay) {
            return;
        }
        if (el.dataset.acHoverBound === '1') {
            return;
        }
        el.dataset.acHoverBound = '1';
        el.addEventListener('mouseenter', function () {
            if (swiperInstance.autoplay) {
                swiperInstance.autoplay.stop();
            }
        });
        el.addEventListener('mouseleave', function () {
            if (swiperInstance.autoplay) {
                swiperInstance.autoplay.start();
            }
        });
    }

    function initTestimonialSwiper() {
        if (typeof Swiper === 'undefined') {
            return false;
        }
        const el = document.querySelector('.tf-sw-testimonial');
        if (!el) {
            return true;
        }
        // Reuse instance if theme script.js already initialized this slider
        if (el.swiper) {
            bindTestimonialHover(el, el.swiper);
            return true;
        }
        if (el.dataset.swiperReady === '1') {
            return true;
        }
        el.dataset.swiperReady = '1';
        const testimonialSwiper = new Swiper(el, {
            slidesPerView: 1,
            spaceBetween: 24,
            loop: true,
            autoplay: { delay: 3000, disableOnInteraction: false },
            breakpoints: {
                768: { slidesPerView: 2 },
                1200: { slidesPerView: 3 }
            }
        });
        bindTestimonialHover(el, testimonialSwiper);
        return true;
    }

    function bootTestimonialSwiper(maxRetries) {
        var retries = 0;
        var limit = maxRetries || 12;
        var tick = function () {
            if (initTestimonialSwiper()) {
                return;
            }
            retries++;
            if (retries < limit) {
                setTimeout(tick, 180);
            }
        };
        tick();
    }

    window.addEventListener('DOMContentLoaded', function () { bootTestimonialSwiper(); });
    window.addEventListener('load', function () { bootTestimonialSwiper(6); });
})();
</script>
