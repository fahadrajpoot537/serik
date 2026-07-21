<style>
    .tf-sw-blog {
    overflow: hidden;
}

.tf-sw-blog .swiper-slide {
    height: auto;
}
</style>

<section class="flat-section-v3 flat-latest-new" @style(["background-color: $shortcode->background_color" => $shortcode->background_color])>
    <div class="container" >
        
            <div  style="text-align:center;color: #000;">{!! BaseHelper::clean($shortcode->subtitle) !!}</div>
     
        
            <h2 class="section-title mt-4" style="font-weight: 700;text-align:center;color: #000;">{!! BaseHelper::clean($shortcode->title) !!}</h2>
       
       
        
        <a href="{{ url('/blog') }}" class="btn-view button-prop" style="float:right; margin-top:-45px;">
                <span class="text" style="font-weight: 500;">View All</span>
                <x-core::icon name="ti ti-arrow-right" class="icon" style="stroke-width: 2" />
            </a>
        
      <br>

              @include(Theme::getThemeNamespace('views.blog.partials.posts'))
    </div>
</section>


<script>
    function initBlogSwiper() {
    if (typeof Swiper === 'undefined') return;

    const el = document.querySelector('.tf-sw-blog');
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
                slidesPerView: 1,
                spaceBetween: 10
            },
            576: {
                slidesPerView: 1.5,
                spaceBetween: 15
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 20
            },
            992: {
                slidesPerView: 3,
                spaceBetween: 20
            }
        }
    });
}

window.addEventListener('load', function () {
    setTimeout(initBlogSwiper, 200);
});
</script>