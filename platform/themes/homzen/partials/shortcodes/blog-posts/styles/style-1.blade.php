<style>
    .tf-sw-blog {
        overflow: visible;
    }

    .tf-sw-blog .swiper-slide {
        height: auto;
    }

    @media (max-width: 991px) {
        .tf-sw-blog {
            padding: 0 4px 8px;
        }

        .tf-sw-blog .swiper-wrapper {
            display: flex;
            align-items: stretch;
        }

        .tf-sw-blog .swiper-slide {
            box-sizing: border-box;
            padding-right: 16px;
        }

        .tf-sw-blog .flat-blog-item {
            width: 100%;
            margin: 0 !important;
        }
    }
</style>

<section class="flat-section-v3 flat-latest-new" @style(["background-color: $shortcode->background_color" => $shortcode->background_color])>
    <div class="container" >
        
            <div  style="text-align:center;color: #000;">{!! BaseHelper::clean($shortcode->subtitle) !!}</div>
     
        
            <h2 class="section-title mt-4" style="font-weight: 700;text-align:center;color: #000;">{!! BaseHelper::clean($shortcode->title) !!}</h2>
       
       
        
        <a href="{{ get_blog_page_url() }}" class="btn-view button-prop" style="float:right; margin-top:-45px;">
                <span class="text" style="font-weight: 500;">View All</span>
                <x-core::icon name="ti ti-arrow-right" class="icon" style="stroke-width: 2" />
            </a>
        
      <br>

              @include(Theme::getThemeNamespace('views.blog.partials.posts'), ['carouselOnMobile' => true])
    </div>
</section>


<script>
function initBlogSwiper() {
    if (typeof Swiper === 'undefined') return;

    const el = document.querySelector('.tf-sw-blog');
    if (!el || el.dataset.swiperReady === '1') return;

    const isMobile = window.matchMedia('(max-width: 991px)').matches;
    if (!isMobile) return;

    el.dataset.swiperReady = '1';

    new Swiper(el, {
        slidesPerView: 1.12,
        spaceBetween: 0,
        loop: true,
        speed: 650,
        autoplay: {
            delay: 3200,
            disableOnInteraction: false,
        },
    });
}

function bootBlogSwiper(maxRetries = 12) {
    let retries = 0;
    const tick = function () {
        initBlogSwiper();
        const el = document.querySelector('.tf-sw-blog');
        if (el && el.dataset.swiperReady === '1') return;
        retries++;
        if (retries < maxRetries) {
            setTimeout(tick, 180);
        }
    };
    tick();
}

window.addEventListener('DOMContentLoaded', function () {
    bootBlogSwiper();
});
window.addEventListener('load', function () {
    bootBlogSwiper(6);
});
</script>