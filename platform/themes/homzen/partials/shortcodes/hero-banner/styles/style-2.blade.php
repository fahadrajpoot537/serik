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
    if (is_string($heroMediaUrl) && $heroMediaUrl !== '') {
        $heroMediaUrl = CmsWebp::preferWebpUrl($heroMediaUrl) ?: $heroMediaUrl;
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
<section class="flat-slider home-2 serik-split-hero" aria-label="{{ __('Ontario property search') }}">
    <div class="container serik-split-hero__container">
        <div class="serik-split-hero__grid">

            {{-- LEFT: property discovery --}}
            <div class="serik-split-hero__left slider-content">
                <div class="heading serik-split-hero__heading">
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
                            <span class="serik-typewriter__text"></span><span class="serik-typewriter__cursor" aria-hidden="true"></span>
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
                                @php $heroSlideIndex++; @endphp
                                <div class="swiper-slide">
                                    <div class="slider-home2 serik-split-hero__slide">
                                        {{ RvMedia::image(
                                            $shortcode->{"slider_image_$i"},
                                            ImageAlt::resolve($shortcode->title, $shortcode->{"slider_image_$i"}, $heroAltContext),
                                            'large',
                                            lazy: $heroSlideIndex > 1,
                                            attributes: $heroSlideIndex === 1
                                                ? ['data-bb-lazy' => 'false', 'fetchpriority' => 'high', 'loading' => 'eager', 'decoding' => 'async', 'width' => 1200, 'height' => 900, 'class' => 'serik-split-hero__banner-img']
                                                : ['data-bb-lazy' => 'true', 'loading' => 'lazy', 'decoding' => 'async', 'width' => 1200, 'height' => 900, 'class' => 'serik-split-hero__banner-img']
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

    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        textEl.textContent = phrases[0];
        return;
    }

    tick();
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
