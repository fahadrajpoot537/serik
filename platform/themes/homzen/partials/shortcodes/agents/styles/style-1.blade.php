@php
    // Keep CMS / shortcode account_ids order — do not re-sort here.
    $agentCount = $accounts->count();
@endphp

<section id="about-agent" class="flat-section flat-agents serik-hp-agents" @style(["background-color: $shortcode->background_color" => $shortcode->background_color])>
    <div class="container">
        <header class="serik-hp-section-head serik-hp-section-head--center">
            @if($shortcode->subtitle)
                <p class="serik-hp-eyebrow">{!! BaseHelper::clean($shortcode->subtitle) !!}</p>
            @endif
            @if($shortcode->title)
                <h2 class="section-title mt-0">{!! BaseHelper::clean($shortcode->title) !!}</h2>
                <a href="https://serik.ca/about-us#about-agent" class="btn-view button-prop serik-hp-link-btn">
                    <span class="text">{{ __('View All') }}</span>
                    <x-core::icon name="ti ti-arrow-right" class="icon" style="stroke-width: 2" />
                </a>
            @endif
        </header>

        <div class="serik-hp-agents__carousel" data-agent-count="{{ $agentCount }}">
            <div class="swiper tf-sw-agents">
                <div class="swiper-wrapper">
                    @foreach ($accounts as $account)
                        <div class="swiper-slide">
                            <div class="box-agent hover-img wow fadeIn serik-hp-agent-card" data-wow-delay=".2s" data-wow-duration="2000ms">
                                <div class="box-img img-style mb-2 serik-hp-agent-card__media">
                                    {{ RvMedia::image($account->avatar_url, $account->name, attributes: ['width' => 300, 'height' => 400, 'decoding' => 'async', 'loading' => 'lazy']) }}
                                    {!! Theme::partial('shortcodes.agents.partials.social-links', compact('account')) !!}
                                </div>
                                <div class="content serik-hp-agent-card__body">
                                    <div class="info">
                                        @if (\Botble\RealEstate\Facades\RealEstateHelper::isDisabledPublicProfile())
                                            <h6>{{ $account->name }} {!! $account->badge !!}</h6>
                                        @else
                                            <a href="{{ $account->url }}"><h6 class="link">{{ $account->name }} {!! $account->badge !!}</h6></a>
                                        @endif
                                        {!! Theme::partial('shortcodes.agents.partials.info', compact('account')) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="sw-pagination sw-pagination-agents"></div>
            </div>
            @if($agentCount > 1)
                <button type="button" class="serik-hp-agents__nav serik-hp-agents__nav--prev nav-prev-agents" aria-label="{{ __('Previous') }}">
                    <x-core::icon name="ti ti-chevron-left" />
                </button>
                <button type="button" class="serik-hp-agents__nav serik-hp-agents__nav--next nav-next-agents" aria-label="{{ __('Next') }}">
                    <x-core::icon name="ti ti-chevron-right" />
                </button>
            @endif
        </div>
    </div>
</section>

<style>
.serik-hp-agents__carousel {
    position: relative;
}
.serik-hp-agents__carousel .tf-sw-agents {
    overflow: hidden;
    padding-bottom: 2rem;
}
.serik-hp-agents__nav {
    position: absolute;
    top: 42%;
    transform: translateY(-50%);
    z-index: 5;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 999px;
    border: 1px solid rgba(22, 30, 45, 0.12);
    background: #fff;
    color: #161e2d;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 18px rgba(16, 24, 40, 0.08);
    cursor: pointer;
}
.serik-hp-agents__nav--prev { left: -0.35rem; }
.serik-hp-agents__nav--next { right: -0.35rem; }
@media (min-width: 992px) {
    .serik-hp-agents__nav--prev { left: -0.75rem; }
    .serik-hp-agents__nav--next { right: -0.75rem; }
}
.serik-hp-agents__carousel .sw-pagination-agents {
    position: static;
    margin-top: 0.75rem;
    text-align: center;
}
</style>

<script>
function hideButton() {
    if (window.location.pathname.includes("about-us")) {
        document.querySelectorAll(".btn-view.button-prop").forEach(btn => {
            btn.style.setProperty("display", "none", "important");
        });
    }
}
hideButton();
window.addEventListener("load", hideButton);
setTimeout(hideButton, 500);
setTimeout(hideButton, 1500);

function initAgentsSwiper() {
    if (typeof Swiper === 'undefined') return;
    const root = document.querySelector('#about-agent .serik-hp-agents__carousel');
    const el = document.querySelector('#about-agent .tf-sw-agents');
    if (!root || !el || el.dataset.swiperReady === '1') return;
    const count = parseInt(root.getAttribute('data-agent-count') || '0', 10);
    el.dataset.swiperReady = '1';
    new Swiper(el, {
        slidesPerView: 1.15,
        spaceBetween: 14,
        loop: count > 4,
        speed: 650,
        watchOverflow: true,
        autoplay: count > 1 ? { delay: 3500, disableOnInteraction: false, pauseOnMouseEnter: true } : false,
        pagination: {
            el: '#about-agent .sw-pagination-agents',
            clickable: true,
        },
        navigation: {
            nextEl: '#about-agent .nav-next-agents',
            prevEl: '#about-agent .nav-prev-agents',
        },
        breakpoints: {
            576: { slidesPerView: 2, spaceBetween: 16 },
            768: { slidesPerView: 3, spaceBetween: 18 },
            992: { slidesPerView: 4, spaceBetween: 20 },
        },
    });
}
function bootAgentsSwiper(maxRetries = 16) {
    let retries = 0;
    const tick = function () {
        initAgentsSwiper();
        const el = document.querySelector('#about-agent .tf-sw-agents');
        if (el && el.dataset.swiperReady === '1') return;
        retries++;
        if (retries < maxRetries) setTimeout(tick, 160);
    };
    tick();
}
window.addEventListener('DOMContentLoaded', function () { bootAgentsSwiper(); });
window.addEventListener('load', function () { bootAgentsSwiper(8); });
</script>
