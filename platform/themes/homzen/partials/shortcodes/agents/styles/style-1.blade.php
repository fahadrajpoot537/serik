@php
$order = ['Gary Sodhi', 'Sadaqat Sheikh'];

$accounts = $accounts->sortBy(function ($account) use ($order) {
    $index = array_search($account->name, $order);
    return $index === false ? 999 : $index;
});
@endphp
<style>
    #about-agent.flat-agents .box-agent {
        gap: 12px;
    }

    #about-agent.flat-agents .box-agent .box-img {
        overflow: hidden;
        border-radius: 14px;
        background: #f8fafc;
        line-height: 0;
        border: 1px solid rgba(22, 30, 45, 0.08);
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04), 0 8px 24px rgba(16, 24, 40, 0.06);
    }

    #about-agent.flat-agents .box-agent .box-img img {
        width: 100%;
        height: auto;
        display: block;
        object-fit: contain;
        object-position: center top;
    }

    #about-agent.flat-agents .box-agent .content h6 {
        font-size: 15px;
        line-height: 1.35;
        margin-bottom: 4px;
        font-weight: 600;
        letter-spacing: -0.01em;
    }

    #about-agent.flat-agents .box-agent .list-info {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }

    #about-agent.flat-agents .box-agent .list-info li {
        margin-bottom: 0 !important;
        font-size: 13px;
    }

    @media (min-width: 992px) {
        #about-agent.flat-agents > .container > .row {
            --bs-gutter-x: 1.25rem;
        }
    }

    @media (max-width: 991px) {
        #about-agent.flat-agents .box-agent .box-img img {
            max-height: none;
        }

        #about-agent .tf-sw-agents {
            overflow: visible;
            padding: 0 4px 8px;
        }

        #about-agent .tf-sw-agents .swiper-wrapper {
            display: flex;
            align-items: stretch;
        }

        #about-agent .tf-sw-agents .swiper-slide {
            height: auto;
            box-sizing: border-box;
            padding-right: 16px;
        }

        #about-agent .tf-sw-agents .box-agent {
            width: 100%;
            margin: 0 !important;
        }
    }
</style>
<section id="about-agent" class="flat-section flat-agents" @style(["background-color: $shortcode->background_color" => $shortcode->background_color])>
    <div class="container">
         @if($shortcode->subtitle)
            <div  style="text-align:center;color: #000;">{!! BaseHelper::clean($shortcode->subtitle) !!}</div>
        @endif

         @if($shortcode->title)
            <h2 class="section-title mt-4" style="font-weight: 700;text-align:center;color: #000;">{!! BaseHelper::clean($shortcode->title) !!}</h2>
           
                <a href="https://serik.ca/about-us#about-agent" class="btn-view button-prop" style="float:right; margin-top:-45px;">
                    <span class="text" style="font-weight: 500;">View All</span>
                    <x-core::icon name="ti ti-arrow-right" class="icon" style="stroke-width: 2" />
                </a>
            
        @endif
         <br>
        <div class="swiper tf-sw-agents d-md-none">
            <div class="swiper-wrapper">
                @foreach ($accounts as $account)
                    <div class="swiper-slide">
                        <div class="box-agent hover-img wow fadeIn" data-wow-delay=".2s" data-wow-duration="2000ms">
                            <div class="box-img img-style mb-2">
                                {{ RvMedia::image($account->avatar_url, $account->name, attributes: ['width' => 300, 'height' => 400, 'decoding' => 'async', 'loading' => 'lazy']) }}
                                {!! Theme::partial('shortcodes.agents.partials.social-links', compact('account')) !!}
                            </div>
                            <div class="content">
                                <div class="info">
                                    @if (\Botble\RealEstate\Facades\RealEstateHelper::isDisabledPublicProfile())
                                        <h6>{{ $account->name }} {!! $account->badge !!}</h6>
                                    @else
                                        <a href="{{ $account->url }}">
                                            <h6 class="link">{{ $account->name }} {!! $account->badge !!}</h6>
                                        </a>
                                    @endif
                                    {!! Theme::partial('shortcodes.agents.partials.info', compact('account')) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-4 row-cols-lg-4 d-none d-md-flex">
            @foreach ($accounts as $account)
                <div class="box col">
                    <div class="box-agent hover-img wow fadeIn" data-wow-delay=".2s" data-wow-duration="2000ms">
                        <div class="box-img img-style mb-2">
                            {{ RvMedia::image($account->avatar_url, $account->name, attributes: ['width' => 300, 'height' => 400, 'decoding' => 'async', 'loading' => 'lazy']) }}
                            {!! Theme::partial('shortcodes.agents.partials.social-links', compact('account')) !!}
                        </div>
                        <div class="content">
                            <div class="info">
                                @if (\Botble\RealEstate\Facades\RealEstateHelper::isDisabledPublicProfile())
                                    <h6>{{ $account->name }} {!! $account->badge !!}</h6>
                                @else
                                    <a href="{{ $account->url }}">
                                        <h6 class="link">{{ $account->name }} {!! $account->badge !!}</h6>
                                    </a>
                                @endif
                                {!! Theme::partial('shortcodes.agents.partials.info', compact('account')) !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>


<script>
function hideButton() {
    if (window.location.pathname.includes("about-us")) {
        const btns = document.querySelectorAll(".btn-view.button-prop");

        btns.forEach(btn => {
            btn.style.setProperty("display", "none", "important");
        });
    }
}

// Run multiple times to beat dynamic rendering
hideButton();
window.addEventListener("load", hideButton);
setTimeout(hideButton, 500);
setTimeout(hideButton, 1500);
</script>

<script>
function initAgentsSwiper() {
    if (typeof Swiper === 'undefined') return;
    const el = document.querySelector('#about-agent .tf-sw-agents');
    if (!el || el.dataset.swiperReady === '1') return;
    if (!window.matchMedia('(max-width: 991px)').matches) return;

    el.dataset.swiperReady = '1';

    new Swiper(el, {
        slidesPerView: 1.15,
        spaceBetween: 0,
        loop: true,
        speed: 650,
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
    });
}

function bootAgentsSwiper(maxRetries = 12) {
    let retries = 0;
    const tick = function () {
        initAgentsSwiper();
        const el = document.querySelector('#about-agent .tf-sw-agents');
        if (el && el.dataset.swiperReady === '1') return;
        retries++;
        if (retries < maxRetries) {
            setTimeout(tick, 180);
        }
    };
    tick();
}

window.addEventListener('DOMContentLoaded', function () {
    bootAgentsSwiper();
});
window.addEventListener('load', function () {
    bootAgentsSwiper(6);
});
</script>