@php
$order = ['Gary Sodhi', 'Sadaqat Sheikh'];
$accounts = $accounts->sortBy(function ($account) use ($order) {
    $index = array_search($account->name, $order);
    return $index === false ? 999 : $index;
});
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

        <div class="swiper tf-sw-agents d-md-none">
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
        </div>

        <div class="row row-cols-2 row-cols-sm-2 row-cols-md-4 row-cols-lg-4 d-none d-md-flex serik-hp-agents__grid">
            @foreach ($accounts as $account)
                <div class="box col">
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
    </div>
</section>

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
    const el = document.querySelector('#about-agent .tf-sw-agents');
    if (!el || el.dataset.swiperReady === '1') return;
    if (!window.matchMedia('(max-width: 991px)').matches) return;
    el.dataset.swiperReady = '1';
    new Swiper(el, {
        slidesPerView: 1.2,
        spaceBetween: 14,
        loop: true,
        speed: 650,
        autoplay: { delay: 3000, disableOnInteraction: false },
    });
}
function bootAgentsSwiper(maxRetries = 12) {
    let retries = 0;
    const tick = function () {
        initAgentsSwiper();
        const el = document.querySelector('#about-agent .tf-sw-agents');
        if (el && el.dataset.swiperReady === '1') return;
        retries++;
        if (retries < maxRetries) setTimeout(tick, 180);
    };
    tick();
}
window.addEventListener('DOMContentLoaded', function () { bootAgentsSwiper(); });
window.addEventListener('load', function () { bootAgentsSwiper(6); });
</script>
