@php
    use App\Support\ImageAlt;
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
@endphp

@if ($heroMediaUrl)
    @push('header')
        <link rel="preload" as="image" href="{{ $heroMediaUrl }}" fetchpriority="high">
    @endpush
@endif

{{--
  Split portal hero:
  LEFT  = discovery (heading, CTA, stats)
  RIGHT = cashback banner — framed, fully visible, never cropped
--}}
<section class="flat-slider home-2 serik-split-hero" aria-label="{{ __('Ontario property search') }}">
    <div class="container serik-split-hero__container">
        <div class="serik-split-hero__grid">

            {{-- LEFT: property discovery --}}
            <div class="serik-split-hero__left slider-content">
                <div class="heading serik-split-hero__heading">
                    <p class="serik-split-hero__eyebrow">{{ __('Ontario MLS® Property Search') }}</p>
                    <h1 class="subtitle body-1 hero-banner-headline serik-split-hero__title" style="color: {{ $descriptionColor }} !important;">
                        {{ __('Find homes for sale & lease across Ontario') }}
                    </h1>
                    <div class="title title1 serik-split-hero__cashback" style="color: {{ $titleColor }} !important;">
                        @php
                            $cashbackBits = array_values(array_filter(array_map('trim', explode(',', (string) ($shortcode->animation_text ?: '')))));
                            $cashbackLabel = $cashbackBits[0] ?? ($shortcode->title ?: __('Upto 1.5% Cash Back'));
                        @endphp
                        {{ $cashbackLabel }}
                    </div>
                    <p class="serik-split-hero__terms">*{{ __('Terms and Conditions Apply') }}</p>
                    @if ($shortcode->description)
                        <p class="subtitle body-1 serik-split-hero__desc" style="color: {{ $descriptionColor }} !important;">
                            {!! BaseHelper::clean($shortcode->description) !!}
                        </p>
                    @endif
                </div>

                <div class="serik-split-hero__actions">
                    {!! Theme::partial('shortcodes.hero-banner.partials.action-button', ['shortcode' => $shortcode, 'class' => 'serik-split-hero__cta']) !!}
                    <a href="{{ url('/map') }}" class="serik-split-hero__link">{{ __('Browse all listings') }}</a>
                </div>

                <ul class="serik-split-stats" aria-label="{{ __('Highlights') }}">
                    <li><strong>MLS®</strong><span>{{ __('Listings') }}</span></li>
                    <li><strong>GTA+</strong><span>{{ __('Coverage') }}</span></li>
                    <li><strong>1.5%</strong><span>{{ __('Cash Back') }}</span></li>
                    <li><strong>Map</strong><span>{{ __('Search') }}</span></li>
                </ul>
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

@if(is_plugin_active('real-estate') && $shortcode->search_box_enabled)
<div class="flat-tab flat-tab-form cashback-calculator serik-split-calc" id="calculator-buttons" style="scroll-margin-top: 150px;">
    <div class="container">
        <div class="serik-split-calc__card">
            <form id="myForm" class="serik-split-calc__form serik-split-calc__row">
                <div class="serik-split-calc__copy">
                    <strong>{{ __('Estimate your cash back') }}</strong>
                    <span>{{ __('Up to 1.5% rebate') }}</span>
                </div>
                <label class="visually-hidden" for="amount">{{ __('Purchase Price') }}</label>
                <input
                    type="text"
                    class="form-control serik-split-calc__input"
                    placeholder="{{ __('Enter home price') }}"
                    value="{{ BaseHelper::stringify(request()->query('k')) }}"
                    id="amount"
                    required
                    inputmode="decimal"
                    autocomplete="off"
                />
                <div class="serik-split-calc__actions calculator-buttons">
                    <button type="submit" class="serik-split-calc__btn serik-split-calc__btn--primary" onclick="calculatePercentage()">
                        {{ __('Calculate cash back') }}
                    </button>
                    <a href="{{ url('/mortgage-calculator') }}" class="serik-split-calc__btn serik-split-calc__btn--primary">{{ __('Mortgage Calculator') }}</a>
                    <a href="{{ url('/appointment-scheduler') }}" class="serik-split-calc__btn serik-split-calc__btn--primary">{{ __('Schedule an appointment') }}</a>
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
