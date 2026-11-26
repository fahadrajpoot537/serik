@php
    use App\Support\ImageAlt;
    use App\Support\CmsWebp;
    use App\Support\SerikMediaUrl;

    $titleColor = $shortcode->title_color ?: '#161e2d';
    $descriptionColor = $shortcode->description_color ?: '#5b6573';
    $heroAltContext = trim(strip_tags((string) ($shortcode->title ?: $shortcode->subtitle ?: __('Ontario homes for sale'))));
    $firstSlider = null;
    foreach (range(1, 4) as $si) {
        if ($shortcode->{"slider_image_$si"}) {
            $firstSlider = $shortcode->{"slider_image_$si"};
            break;
        }
    }
    $heroMediaUrl = $firstSlider
        ? SerikMediaUrl::cmsImageUrl($firstSlider, 'large')
        : ($shortcode->background_image ? SerikMediaUrl::cmsImageUrl($shortcode->background_image, 'large') : null);
    $heroSrcset = '';
    if (is_string($heroMediaUrl) && $heroMediaUrl !== '') {
        // Homepage: preload the same resized WebP the <img> will use (never alter original file).
        if (\App\Support\SerikHomepage::isHomepageRequest()) {
            // Displayed ~574px wide — default + preload at 574 (not 720) for Desktop PSI.
            $heroSrcset = \App\Support\SerikHomepageImage::srcset($heroMediaUrl, [360, 480, 574], 68);
            $heroMediaUrl = \App\Support\SerikHomepageImage::optimizedUrl($heroMediaUrl, 480, 68) ?: $heroMediaUrl;
        } else {
            $heroMediaUrl = CmsWebp::preferWebpUrl($heroMediaUrl) ?: $heroMediaUrl;
        }
    }
@endphp

@if ($heroMediaUrl)
    @push('header')
        <link rel="preload" as="image" href="{{ $heroMediaUrl }}" fetchpriority="high">
    @endpush
@endif

{{--
  Split portal hero:
  LEFT  = heading
  RIGHT = cashback banner — framed, fully visible, never cropped
--}}
<style>
    /* First hero slide visible before Swiper initializes; autoplay starts after window load. */
    .serik-split-hero__swiper:not(.swiper-initialized) .swiper-slide {
        opacity: 0;
        pointer-events: none;
    }
    .serik-split-hero__swiper:not(.swiper-initialized) .swiper-slide:first-child {
        opacity: 1;
        pointer-events: auto;
    }
    /* Reserve 4:3 box before image decode (width/height attrs alone were overridden by CSS). */
    .serik-split-hero__frame,
    .serik-split-hero__swiper,
    .serik-split-hero__slide {
        aspect-ratio: 4 / 3;
        width: 100%;
    }
    .serik-split-hero__banner-img {
        width: 100%;
        height: 100%;
        aspect-ratio: 4 / 3;
        object-fit: contain;
        object-position: center center;
        display: block;
    }
    .serik-split-hero__cashback,
    .serik-typewriter {
        min-height: 1.15em;
    }
    .serik-xmas {
        margin: 0 0 14px;
    }
    .serik-xmas__kicker {
        margin: 0 0 6px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #db1d23;
    }
    .serik-xmas__row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .serik-xmas__unit {
        min-width: 58px;
        padding: 6px 8px 5px;
        border-radius: 10px;
        background: #fff;
        border: 1px solid rgba(219, 29, 35, 0.22);
        text-align: center;
        line-height: 1;
    }
    .serik-xmas__unit span {
        display: block;
        font-size: 22px;
        font-weight: 800;
        color: #0b2340;
        font-variant-numeric: tabular-nums;
    }
    .serik-xmas__unit small {
        display: block;
        margin-top: 4px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #db1d23;
    }
    .serik-xmas__done {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 800;
        color: #db1d23;
    }
</style>
<section class="flat-slider home-2 serik-split-hero" aria-label="{{ __('Ontario property search') }}">
    <div class="container serik-split-hero__container">
        <div class="serik-split-hero__grid">

            {{-- LEFT: property discovery --}}
            <div class="serik-split-hero__left slider-content">
                <div class="heading serik-split-hero__heading">
                    <div class="serik-xmas" id="serikXmasCountdown">
                        <p class="serik-xmas__kicker">{{ __('Christmas countdown') }}</p>
                        <div class="serik-xmas__row">
                            <div class="serik-xmas__unit"><span data-xmas="days">--</span><small>{{ __('Days') }}</small></div>
                            <div class="serik-xmas__unit"><span data-xmas="hours">--</span><small>{{ __('Hours') }}</small></div>
                            <div class="serik-xmas__unit"><span data-xmas="minutes">--</span><small>{{ __('Min') }}</small></div>
                            <div class="serik-xmas__unit"><span data-xmas="seconds">--</span><small>{{ __('Sec') }}</small></div>
                        </div>
                        <p class="serik-xmas__done" hidden>{{ __('Merry Christmas') }}</p>
                    </div>
                    <h1 class="subtitle body-1 hero-banner-headline serik-split-hero__title" style="color: {{ $descriptionColor }}; font-weight:700;">
                        {{ __('Top Realtor in Ontario - Buy or Sell Homes and Get') }}
                    </h1>
                    @php
                        $cashbackBits = array_values(array_filter(array_map('trim', explode(',', (string) ($shortcode->animation_text ?: '')))));
                        // Remove space in the hero heading: "CashBack" (not "Cash Back").
                        $cashbackBits = array_map(static fn ($t) => str_replace('Cash Back', 'CashBack', (string) $t), $cashbackBits);
                        if ($cashbackBits === []) {
                            $cashbackBits = [__('Upto 1.5% CashBack')];
                        }
                        $cashbackLabel = $cashbackBits[0];
                    @endphp
                    <h2 class="title title1 serik-split-hero__cashback" style="color: {{ $titleColor }}; font-weight:700;" aria-label="{{ $cashbackLabel }}">
                        <span
                            class="serik-typewriter"
                            id="serikHeroTypewriter"
                            data-phrases="{{ e(json_encode(array_values($cashbackBits), JSON_UNESCAPED_UNICODE)) }}"
                        >
                            <span class="serik-typewriter__text">{{ $cashbackBits[0] }}</span><span class="serik-typewriter__cursor" aria-hidden="true"></span>
                        </span>
                    </h2>
                    <p class="serik-split-hero__terms">*{{ __('Terms and Conditions Apply') }}</p>
                    @if ($shortcode->description)
                        <p class="subtitle body-1 serik-split-hero__desc" style="color: {{ $descriptionColor }} !important;">
                            {!! BaseHelper::clean($shortcode->description) !!}
                        </p>
                    @endif
                </div>

                <div class="serik-split-hero__actions">
                    {!! Theme::partial('shortcodes.hero-banner.partials.action-button', ['shortcode' => $shortcode, 'class' => 'serik-split-hero__cta']) !!}
                </div>
            </div>

            {{-- RIGHT: existing cashback banner — framed, fully visible --}}
            <div class="serik-split-hero__right">
                <div class="serik-split-hero__frame img-banner-right">
                    <div class="swiper slider-sw-home2 serik-split-hero__swiper">
                        <div class="swiper-wrapper">
                            @php $heroSlideIndex = 0; @endphp
                            @foreach (range(1, 4) as $i)
                                @continue(! $shortcode->{"slider_image_$i"})
                                @php
                                    $heroSlideIndex++;
                                    $slidePath = $shortcode->{"slider_image_$i"};
                                    $slideSrcset = '';
                                    $slideSrc = null;
                                    if (\App\Support\SerikHomepage::isHomepageRequest()) {
                                        $rawSlideUrl = SerikMediaUrl::cmsImageUrl($slidePath, 'large');
                                        if (is_string($rawSlideUrl) && $rawSlideUrl !== '') {
                                            $slideSrcset = \App\Support\SerikHomepageImage::srcset($rawSlideUrl, [360, 480, 574], 68);
                                            $slideSrc = \App\Support\SerikHomepageImage::optimizedUrl($rawSlideUrl, 480, 68) ?: $rawSlideUrl;
                                        }
                                    }
                                    // Never reuse slide-1 srcset on later slides (browser would show one image for all).
                                    $slideAttrs = array_filter([
                                        'data-bb-lazy' => 'false',
                                        'fetchpriority' => $heroSlideIndex === 1 ? 'high' : null,
                                        'loading' => $heroSlideIndex === 1 ? 'eager' : 'lazy',
                                        'decoding' => 'async',
                                        'width' => 1200,
                                        'height' => 900,
                                        'class' => 'serik-split-hero__banner-img',
                                        'src' => $slideSrc,
                                        'srcset' => $slideSrcset !== '' ? $slideSrcset : null,
                                        'sizes' => $slideSrcset !== '' ? '(max-width: 768px) 88vw, 480px' : null,
                                    ]);
                                @endphp
                                <div class="swiper-slide">
                                    <div class="slider-home2 serik-split-hero__slide">
                                        {{ RvMedia::image(
                                            $slidePath,
                                            ImageAlt::resolve($shortcode->title, $slidePath, $heroAltContext),
                                            'large',
                                            lazy: false,
                                            attributes: $slideAttrs
                                        ) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($shortcode->background_image)
        <div class="img-banner-left" hidden aria-hidden="true">
            {{ RvMedia::image(
                $shortcode->background_image,
                ImageAlt::resolve($shortcode->title, $shortcode->background_image, $heroAltContext),
                'large',
                lazy: true,
                attributes: ['loading' => 'lazy', 'width' => 400, 'height' => 300]
            ) }}
        </div>
    @endif
</section>

<script>
(function () {
    var root = document.getElementById('serikXmasCountdown');
    if (!root) return;
    var units = {
        days: root.querySelector('[data-xmas="days"]'),
        hours: root.querySelector('[data-xmas="hours"]'),
        minutes: root.querySelector('[data-xmas="minutes"]'),
        seconds: root.querySelector('[data-xmas="seconds"]')
    };
    var row = root.querySelector('.serik-xmas__row');
    var done = root.querySelector('.serik-xmas__done');
    var kicker = root.querySelector('.serik-xmas__kicker');

    function pad(n) {
        return (n < 10 ? '0' : '') + n;
    }

    // Local midnight on 25 December. Each browser uses its own time zone.
    function nextChristmas(now) {
        var year = now.getFullYear();
        var start = new Date(year, 11, 25, 0, 0, 0, 0);
        if (now.getTime() < start.getTime()) {
            return start;
        }
        var endOfChristmas = new Date(year, 11, 26, 0, 0, 0, 0);
        if (now.getTime() < endOfChristmas.getTime()) {
            return null;
        }
        return new Date(year + 1, 11, 25, 0, 0, 0, 0);
    }

    function tick() {
        var now = new Date();
        var target = nextChristmas(now);
        if (!target) {
            if (row) row.hidden = true;
            if (kicker) kicker.hidden = true;
            if (done) done.hidden = false;
            return;
        }
        var total = Math.floor(Math.max(0, target.getTime() - now.getTime()) / 1000);
        if (units.days) units.days.textContent = String(Math.floor(total / 86400));
        if (units.hours) units.hours.textContent = pad(Math.floor((total % 86400) / 3600));
        if (units.minutes) units.minutes.textContent = pad(Math.floor((total % 3600) / 60));
        if (units.seconds) units.seconds.textContent = pad(total % 60);
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
<script>
(function () {
    var root = document.getElementById('serikHeroTypewriter');
    if (!root) return;
    var textEl = root.querySelector('.serik-typewriter__text');
    if (!textEl) return;

    var phrases = [];
    try {
        phrases = JSON.parse(root.getAttribute('data-phrases') || '[]');
    } catch (e) {
        phrases = [];
    }
    if (!phrases.length) {
        phrases = ['Upto 1.5% CashBack'];
    }

    var phraseIndex = 0;
    var charIndex = 0;
    var deleting = false;
    var typeMs = 70;
    var deleteMs = 40;
    var holdMs = 1800;
    var gapMs = 400;

    function tick() {
        var phrase = phrases[phraseIndex] || '';
        if (!deleting) {
            charIndex = Math.min(charIndex + 1, phrase.length);
            textEl.textContent = phrase.slice(0, charIndex);
            if (charIndex === phrase.length) {
                if (phrases.length < 2) {
                    return; // typed once, keep full text with blinking cursor
                }
                deleting = true;
                setTimeout(tick, holdMs);
                return;
            }
            setTimeout(tick, typeMs);
            return;
        }

        charIndex = Math.max(charIndex - 1, 0);
        textEl.textContent = phrase.slice(0, charIndex);
        if (charIndex === 0) {
            deleting = false;
            phraseIndex = (phraseIndex + 1) % phrases.length;
            setTimeout(tick, gapMs);
            return;
        }
        setTimeout(tick, deleteMs);
    }

    // Seed first phrase in HTML to avoid CLS from typing empty → full.
    textEl.textContent = phrases[0];
    charIndex = phrases[0].length;

    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    if (phrases.length < 2) {
        return;
    }

    // Start cycle after paint so LCP/CLS are not affected by typing.
    var startCycle = function () {
        deleting = true;
        setTimeout(tick, holdMs);
    };
    if (document.readyState === 'complete') {
        setTimeout(startCycle, 1200);
    } else {
        window.addEventListener('load', function () {
            setTimeout(startCycle, 1200);
        }, { once: true });
    }
})();
</script>

@if(is_plugin_active('real-estate') && $shortcode->search_box_enabled)
<div class="flat-tab flat-tab-form cashback-calculator serik-split-calc" id="calculator-buttons" style="scroll-margin-top: 150px;">
    <div class="container">
        <div class="serik-split-calc__card">
            <form id="myForm" class="serik-split-calc__form serik-split-calc__row">
                <div class="serik-split-calc__copy">
                    <strong>{{ __('Purchase Price') }}</strong>
                </div>
                <label class="visually-hidden" for="amount">{{ __('Purchase Price') }}</label>
                <input
                    type="text"
                    class="form-control serik-split-calc__input"
                    placeholder="{{ __('Enter Home Price') }}"
                    value="{{ BaseHelper::stringify(request()->query('k')) }}"
                    id="amount"
                    required
                    inputmode="decimal"
                    autocomplete="off"
                />
                <div class="serik-split-calc__actions calculator-buttons">
                    <button type="submit" class="serik-split-calc__btn serik-split-calc__btn--image" onclick="calculatePercentage()" aria-label="{{ __('Calculate cash back') }}">
                        <img
                            src="{{ \App\Support\SerikMediaUrl::toPublic('button-calculate-cashback-1.png') }}"
                            alt="{{ __('Calculate cash back') }}"
                            width="140"
                            height="40"
                            decoding="async"
                            loading="lazy"
                        />
                    </button>
                    <a href="{{ url('/mortgage-calculator') }}" class="serik-split-calc__btn serik-split-calc__btn--image" aria-label="{{ __('Mortgage Calculator') }}">
                        <img
                            src="{{ \App\Support\SerikMediaUrl::toPublic('button-mortgage-calculator-blue-1.png') }}"
                            alt="{{ __('Mortgage Calculator') }}"
                            width="140"
                            height="40"
                            decoding="async"
                            loading="lazy"
                        />
                    </a>
                    <a href="{{ url('/appointment-scheduler') }}" class="serik-split-calc__btn serik-split-calc__btn--image" aria-label="{{ __('Schedule an appointment') }}">
                        <img
                            src="{{ \App\Support\SerikMediaUrl::toPublic('button-copy1-2.png') }}"
                            alt="{{ __('Schedule an appointment') }}"
                            width="140"
                            height="40"
                            decoding="async"
                            loading="lazy"
                        />
                    </a>
                </div>
            </form>
            <div id="result" class="calculator-result serik-split-calc__result"></div>
        </div>
    </div>
</div>

<script>
var form = document.getElementById("myForm");
function handleForm(event) { event.preventDefault(); }
if (form) form.addEventListener('submit', handleForm);

document.addEventListener('DOMContentLoaded', () => {
    const currencyInput = document.getElementById('amount');
    if (!currencyInput) return;
    function formatCurrency(value) {
        let number = value.replace(/[^0-9.]/g, '');
        number = parseFloat(number);
        if (!isNaN(number)) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency', currency: 'USD',
                minimumFractionDigits: 0, maximumFractionDigits: 0
            }).format(number);
        }
        return '';
    }
    currencyInput.addEventListener('input', (e) => {
        e.target.value = formatCurrency(e.target.value);
    });
});

function calculatePercentage() {
    const rawAmount = document.getElementById("amount").value.replace(/[^0-9.]/g, '');
    const amount = parseFloat(rawAmount);
    if (!rawAmount || isNaN(amount)) {
        document.getElementById("result").textContent = "Please enter a valid number.";
        document.getElementById("result").style.display = 'block';
        return;
    }
    const result = (amount * 1.5) / 100;
    document.getElementById("result").style.display = 'block';
    document.getElementById("result").innerHTML =
        "Your Cash Back is Upto $" + result.toLocaleString('en-US', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        }) + "<br> (<span style='color:red;'>*Terms and Conditions Apply</span>)";
    hideResultAfterDelay();
}

function hideResultAfterDelay() {
    setTimeout(function () {
        const amountEl = document.getElementById("amount");
        const resultEl = document.getElementById("result");
        if (amountEl) amountEl.value = null;
        const locationEl = document.getElementById("location");
        if (locationEl) locationEl.value = null;
        if (resultEl) resultEl.style.display = "none";
    }, 30000);
}
</script>
@endif
