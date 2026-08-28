@php
    $googleReviewsUrl = 'https://www.google.com/search?q=Serik+Realty+Inc.+Reviews';
    $featureImage = $shortcode->background_image
        ? RvMedia::getImageUrl($shortcode->background_image, null, false, Theme::asset()->url('images/serik-category-01.jpg'))
        : asset('pictures/testimonials-team.jpg');
    $intro = $shortcode->description ?: $shortcode->subtitle;
    $cards = $testimonials->take(3);
@endphp

<section
    class="flat-section-v2 flat-testimonial-v2 wow fadeInUpSmall serik-hp-reviews serik-hp-reviews--stories"
    data-wow-delay=".2s"
    data-wow-duration="2000ms"
    id="testimonials"
>
    <div class="container">
        <div class="serik-hp-reviews__layout">
            <figure class="serik-hp-reviews__photo">
                <img
                    src="{{ $featureImage }}"
                    alt="{{ img_alt(null, 'testimonials-team.jpg', __('Serik Realty team')) }}"
                    width="640"
                    height="800"
                    loading="lazy"
                >
            </figure>

            <div class="serik-hp-reviews__copy">
                <p class="serik-hp-reviews__eyebrow">{{ __('Testimonials') }}</p>
                <h2 class="serik-hp-reviews__title">
                    {{ __('Success Stories From') }}
                    <span>{{ __('Our Client') }}</span>
                </h2>
                @if ($intro)
                    <p class="serik-hp-reviews__intro">{!! BaseHelper::clean($intro) !!}</p>
                @endif
            </div>

            <div class="serik-hp-reviews__cards">
                @foreach ($cards as $testimonial)
                    @php
                        $quote = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $testimonial->content)));
                    @endphp
                    <article class="serik-hp-review-card">
                        <a
                            class="serik-hp-review-card__link"
                            href="{{ $googleReviewsUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <span class="serik-hp-review-card__quote" aria-hidden="true">&ldquo;</span>
                            @if ($quote !== '')
                                <p class="serik-hp-review-card__text">{{ $quote }}</p>
                            @endif
                            <span class="serik-hp-review-card__more">{{ __('Read Google reviews') }}</span>
                            <div class="serik-hp-review-card__person">
                                <div class="serik-hp-review-card__avatar">
                                    {{ RvMedia::image($testimonial->image, $testimonial->name, null, true, ['loading' => 'eager'], null, false) }}
                                </div>
                                <div class="serik-hp-review-card__meta">
                                    <div class="serik-hp-review-card__name">{{ $testimonial->name }}</div>
                                    @if ($testimonial->company)
                                        <p class="serik-hp-review-card__role">{{ $testimonial->company }}</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
