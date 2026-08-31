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
    transform: translateY(-4px);
    transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}

#page-home .tf-sw-services .box-service.hover-btn-view {
    background: #fff;
    border: 1px solid rgba(22, 30, 45, 0.08);
    border-radius: 14px;
    box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04), 0 8px 24px rgba(16, 24, 40, 0.06);
    padding: 1.25rem 1rem;
    min-height: auto;
    position: relative;
    transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.3s cubic-bezier(0.22, 1, 0.36, 1), border-color 0.2s ease;
}
#page-home .tf-sw-services .box-service.hover-btn-view:hover,
#page-home .tf-sw-services .box-service.hover-btn-view:has(.serik-service-card__link:focus-visible) {
    border-color: var(--serik-reference-blue, #0b4c9f);
    box-shadow: 0 4px 16px rgba(11, 76, 159, 0.12);
}
.serik-service-card__link {
    position: absolute;
    inset: 0;
    z-index: 1;
    border-radius: inherit;
}
.serik-service-card__link:focus {
    outline: none;
}
.serik-service-card__link:focus-visible {
    outline: 3px solid var(--serik-reference-blue, #0b4c9f);
    outline-offset: 3px;
}
.serik-service-card__cta {
    pointer-events: none;
    position: relative;
    z-index: 0;
}
.tf-sw-services .serik-service-slider__controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    margin-top: 1.25rem;
    position: relative;
    z-index: 2;
}
.tf-sw-services .serik-service-slider__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.5rem;
    min-height: 2.5rem;
    padding: 0.35rem 0.7rem;
    border: 1px solid rgba(22, 30, 45, 0.16);
    border-radius: 999px;
    background: #fff;
    color: var(--serik-reference-navy, #0b2340);
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
}
.tf-sw-services .serik-service-slider__btn:hover {
    border-color: var(--serik-reference-blue, #0b4c9f);
    color: var(--serik-reference-blue, #0b4c9f);
}
.tf-sw-services .serik-service-slider__btn:focus-visible {
    outline: 3px solid var(--serik-reference-blue, #0b4c9f);
    outline-offset: 2px;
}
.serik-service-card__busy {
    position: absolute;
    inset: auto 0.75rem 0.75rem auto;
    z-index: 2;
    font-size: 0.75rem;
    color: var(--serik-reference-navy, #0b2340);
    opacity: 0;
    pointer-events: none;
}
.serik-service-card.is-opening .serik-service-card__busy {
    opacity: 1;
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

<section @class(['flat-section', 'serik-hp-services', 'text-center' => $shortcode->centered_content]) @style(["background-color: $shortcode->background_color" => $shortcode->background_color])>
    <div class="container">
        <header class="serik-hp-section-head serik-hp-section-head--center">
            @if($shortcode->subtitle)
                <p class="serik-hp-eyebrow">{!! BaseHelper::clean($shortcode->subtitle) !!}</p>
            @endif
            <h2 class="section-title mt-0">{!! BaseHelper::clean($shortcode->title) !!}</h2>
        </header>
       @if($services)
    @php
        $iconPx = (int) ($iconImageSize ?: 80);
    @endphp
    <div class="swiper tf-sw-services wow fadeInUpSmall"
         data-wow-delay=".4s"
         data-wow-duration="2000ms"
         data-serik-service-slider="1">

        <div class="swiper-wrapper">

            @foreach($services as $service)
                @php
                    $ctaLabel = $service['button_label'] ?? __('Learn More');
                    $ctaUrl = $service['button_url'] ?? '';
                    $cardTitle = trim(strip_tags((string) ($service['title'] ?? '')));
                    $serviceKey = $service['service_key'] ?? null;
                    $cardLabel = trim($ctaLabel . ($cardTitle !== '' ? ': ' . $cardTitle : ''));
                @endphp
                <div class="swiper-slide">
                    <div class="box-service hover-btn-view serik-service-card" @if($serviceKey) data-service-key="{{ $serviceKey }}" @endif>

                        <div class="icon-box" style="height: 100px !important;">
                            @if($service['icon_image'])
                                {{ RvMedia::image($service['icon_image'], $service['title'], attributes: [
                                    'class' => 'icon',
                                    'data-bb-lazy' => 'false',
                                    'width' => $iconPx,
                                    'height' => $iconPx,
                                    'decoding' => 'async',
                                    'loading' => 'lazy',
                                ]) }}
                            @elseif($service['icon'])
                                <x-core::icon :name="$service['icon']" class="icon" />
                            @endif
                        </div>

                        <div class="content" style="height: 70%;">
                            <h6>{!! BaseHelper::clean($service['title']) !!}</h6>

                            <p class="description">
                                {!! BaseHelper::clean(nl2br($service['description'])) !!}
                            </p>

                            @if($ctaUrl)
                                <span class="btn-view style-1 serik-service-card__cta" aria-hidden="true">
                                    <span class="text">{{ $ctaLabel }}</span>
                                    <x-core::icon name="ti ti-arrow-right" class="icon" />
                                </span>
                            @endif
                        </div>

                        @if($ctaUrl)
                            <a href="{{ $ctaUrl }}"
                               class="serik-service-card__link"
                               data-serik-service-card="1"
                               @if($serviceKey) data-service-key="{{ $serviceKey }}" @endif
                               aria-label="{{ $cardLabel }}">
                                <span class="visually-hidden">{{ $cardLabel }}</span>
                            </a>
                            <span class="serik-service-card__busy" aria-hidden="true">Opening…</span>
                        @endif

                    </div>
                </div>
            @endforeach

        </div>
        <div class="serik-service-slider__controls" data-serik-service-controls>
            <button type="button" class="serik-service-slider__btn serik-service-slider__prev" aria-label="{{ __('Previous services') }}">{{ __('Previous') }}</button>
            <button type="button" class="serik-service-slider__btn serik-service-slider__pause" hidden aria-pressed="false" aria-label="{{ __('Pause service slider') }}">{{ __('Pause') }}</button>
            <button type="button" class="serik-service-slider__btn serik-service-slider__next" aria-label="{{ __('Next services') }}">{{ __('Next') }}</button>
        </div>
    </div>
@endif

        {!! Theme::partial('shortcodes.services.partials.counters', compact('counters')) !!}
    </div>
</section>




<script>
(function () {
    var AUTOPLAY_MS = 3000;
    var instance = null;
    var wantedAutoplay = true;
    var pointerInside = false;
    var focusInside = false;
    var interacting = false;
    var resumeTimer = null;
    var bootTimer = null;

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function rootEl() {
        return document.querySelector('.tf-sw-services[data-serik-service-slider]');
    }

    function pauseBtn(el) {
        return el ? el.querySelector('.serik-service-slider__pause') : null;
    }

    function setPauseUi(el, paused) {
        var btn = pauseBtn(el);
        if (!btn || btn.hidden) return;
        btn.setAttribute('aria-pressed', paused ? 'true' : 'false');
        btn.setAttribute('aria-label', paused ? 'Play service slider' : 'Pause service slider');
        btn.textContent = paused ? 'Play' : 'Pause';
    }

    function shouldRunAutoplay() {
        return wantedAutoplay && !prefersReducedMotion() && !pointerInside && !focusInside && !interacting && document.visibilityState !== 'hidden';
    }

    function applyAutoplayState() {
        if (!instance || !instance.autoplay) return;
        if (shouldRunAutoplay()) {
            instance.autoplay.start();
            setPauseUi(rootEl(), false);
        } else {
            instance.autoplay.stop();
            setPauseUi(rootEl(), true);
        }
    }

    function scheduleResume() {
        clearTimeout(resumeTimer);
        resumeTimer = setTimeout(function () {
            interacting = false;
            applyAutoplayState();
        }, 1200);
    }

    function destroyInstance() {
        clearTimeout(resumeTimer);
        if (instance && typeof instance.destroy === 'function') {
            try { instance.destroy(true, true); } catch (e) {}
        }
        instance = null;
        var el = rootEl();
        if (el) {
            delete el.dataset.serikSwiperReady;
            el.serikServiceSliderBound = false;
        }
    }

    function bindCardClicks(el) {
        if (el.dataset.serikCardClickBound === '1') return;
        el.dataset.serikCardClickBound = '1';
        var startX = 0;
        var startY = 0;
        el.addEventListener('pointerdown', function (e) {
            startX = e.clientX;
            startY = e.clientY;
        }, { passive: true });
        el.addEventListener('click', function (e) {
            var link = e.target.closest('.serik-service-card__link');
            if (!link || e.target.closest('.serik-service-slider__controls')) return;
            var dx = Math.abs(e.clientX - startX);
            var dy = Math.abs(e.clientY - startY);
            if (dx > 8 || dy > 8) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button === 1) return;
            var card = link.closest('.serik-service-card');
            if (card) {
                card.classList.add('is-opening');
                window.setTimeout(function () { card.classList.remove('is-opening'); }, 400);
            }
        });
        el.addEventListener('pointerenter', function () { pointerInside = true; applyAutoplayState(); });
        el.addEventListener('pointerleave', function () { pointerInside = false; scheduleResume(); applyAutoplayState(); });
        el.addEventListener('focusin', function () { focusInside = true; applyAutoplayState(); });
        el.addEventListener('focusout', function (e) {
            if (el.contains(e.relatedTarget)) return;
            focusInside = false;
            scheduleResume();
            applyAutoplayState();
        });
    }

    function initServicesSwiper() {
        if (typeof Swiper === 'undefined') return;
        var el = rootEl();
        if (!el) return;
        if (el.swiper || el.dataset.serikSwiperReady === '1') return;
        el.dataset.serikSwiperReady = '1';

        wantedAutoplay = !prefersReducedMotion();
        var autoplayDelay = AUTOPLAY_MS;

        instance = new Swiper(el, {
            slidesPerView: 3,
            spaceBetween: 20,
            loop: true,
            speed: 800,
            watchOverflow: true,
            preventClicks: true,
            preventClicksPropagation: true,
            threshold: 10,
            autoplay: wantedAutoplay ? {
                delay: autoplayDelay,
                disableOnInteraction: true,
                pauseOnMouseEnter: true
            } : false,
            navigation: {
                nextEl: el.querySelector('.serik-service-slider__next'),
                prevEl: el.querySelector('.serik-service-slider__prev')
            },
            breakpoints: {
                0: { slidesPerView: 2, spaceBetween: 10 },
                576: { slidesPerView: 2, spaceBetween: 15 },
                768: { slidesPerView: 2, spaceBetween: 20 },
                992: { slidesPerView: 3, spaceBetween: 24 }
            },
            on: {
                init: function () {
                    var btn = pauseBtn(el);
                    if (btn && wantedAutoplay && autoplayDelay > 0) {
                        btn.hidden = false;
                        setPauseUi(el, false);
                    }
                }
            }
        });

        bindCardClicks(el);

        var pause = pauseBtn(el);
        if (pause && !pause.dataset.bound) {
            pause.dataset.bound = '1';
            pause.addEventListener('click', function () {
                interacting = true;
                wantedAutoplay = !wantedAutoplay;
                applyAutoplayState();
                if (wantedAutoplay) scheduleResume();
            });
        }

        applyAutoplayState();
    }

    function onVisibility() {
        applyAutoplayState();
    }

    function onResize() {
        if (!instance) {
            initServicesSwiper();
            return;
        }
        try { instance.update(); } catch (e) {}
        applyAutoplayState();
    }

    function bootServicesSwiper() {
        clearTimeout(bootTimer);
        bootTimer = setTimeout(initServicesSwiper, 80);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootServicesSwiper, { once: true });
    } else {
        bootServicesSwiper();
    }
    window.addEventListener('load', bootServicesSwiper, { once: true });
    document.addEventListener('visibilitychange', onVisibility);
    window.addEventListener('resize', onResize);
    window.addEventListener('pagehide', destroyInstance);
})();
</script>
