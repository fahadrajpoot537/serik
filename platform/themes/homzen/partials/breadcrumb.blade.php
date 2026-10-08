@php
    $backgroundColor = Theme::get('breadcrumbBackgroundColor', theme_option('breadcrumb_background_color', '#f7f7f7'));
    $textColor = Theme::get('breadcrumbTextColor', theme_option('breadcrumb_text_color', '#161e2d'));
    $backgroundImage = Theme::get('breadcrumbBackgroundImage', theme_option('breadcrumb_background_image') ?: null);

    $backgroundImage = $backgroundImage ? RvMedia::getImageUrl($backgroundImage) : null;

    $mappedHero = \App\Support\PageHeroImage::urlForRequest();
    if ($mappedHero) {
        $backgroundImage = $mappedHero;
    }

    $showBreadcrumb = Theme::get('breadcrumbEnabled', 'yes');
    $breadcrumbStyle = Theme::get('breadcrumbStyle', 'default');
    $pageH1 = \App\Support\PageH1::resolve();
    $isAboutUs = request()->is('about-us');
    $isContactUs = request()->is('contact-us');
    $useHeroStyle = $isAboutUs || $backgroundImage;
    $contactCarouselSlides = $isContactUs
        ? collect([1, 2, 3, 4])
            ->map(static fn (int $n): string => asset(sprintf('%02d.webp', $n)))
            ->all()
        : [];
@endphp


<style>
    .hero-overlay{
    position: relative;
}

.hero-overlay .overlay{
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.55);
}

.hero-overlay .container{
    position:relative;
    z-index:2;
}

.hero-overlay,
.hero-overlay a,
.hero-overlay li,
.hero-overlay h1,
.hero-overlay p{
    color:#fff !important;
}


#sectionhead{
    height:300px;
}

/* Contact Us: shorter page hero only (carousel is below) */
#sectionhead.serik-contact-hero-short{
    height:170px;
    min-height:170px;
}
#sectionhead.serik-contact-hero-short .container{
    min-height:170px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    padding-top:.75rem !important;
    padding-bottom:.75rem !important;
}
#sectionhead.serik-contact-hero-short .page-title{
    margin-top:0 !important;
    font-size:clamp(1.45rem, 2.6vw, 2.15rem);
}
#sectionhead.serik-contact-hero-short .breadcrumb{
    margin-top:.45rem !important;
}

@media (max-width: 991px) {
   #sectionhead{
    height: auto;
    min-height: 130px;
   }

   #sectionhead.serik-contact-hero-short{
    height:120px;
    min-height:120px;
   }
   #sectionhead.serik-contact-hero-short .container{
    min-height:120px;
    padding-top:.55rem !important;
    padding-bottom:.55rem !important;
   }
   #sectionhead.serik-contact-hero-short .page-title{
    font-size:clamp(1.2rem, 5vw, 1.55rem);
   }

   .hero-overlay .container {
    padding-top: 1rem !important;
    padding-bottom: 1rem !important;
   }

   .hero-overlay .page-title {
    margin-top: 0 !important;
   }

   .hero-overlay .breadcrumb {
    font-size: 12px;
    line-height: 1.4;
    margin-top: 0.5rem !important;
    margin-bottom: 0;
    justify-content: center;
    flex-wrap: wrap;
   }
}


.about-mobile-style {
    padding: 200px 0;
    height: auto !important;
}
.heading-breadcrumb{
    font-size: clamp(2rem, 4vw, 3.5rem);
    font-weight: 700;
    line-height: 1.15;
}

#page-faqs #sectionhead,
body#page-faqs #sectionhead {
    height: auto;
    min-height: 0;
    padding-bottom: 1.5rem;
}

/* Contact Us: content-aligned row, seamless loop (no end gap), side fade */
.serik-contact-carousel{
    --scc-gap:14px;
    --scc-card-w:clamp(150px, 17vw, 220px);
    width:100%;
    padding:1.25rem 0 1.75rem;
    background:transparent;
}
.serik-contact-carousel__viewport{
    position:relative;
    width:100%;
    overflow:hidden;
    -webkit-mask-image:linear-gradient(90deg, transparent 0%, #000 8%, #000 92%, transparent 100%);
    mask-image:linear-gradient(90deg, transparent 0%, #000 8%, #000 92%, transparent 100%);
}
.serik-contact-carousel__track{
    display:flex;
    width:max-content;
    animation:serikContactMarquee 32s linear infinite;
    will-change:transform;
}
.serik-contact-carousel__track:hover{
    animation-play-state:paused;
}
.serik-contact-carousel__group{
    display:flex;
    flex:0 0 auto;
    gap:var(--scc-gap);
    /* Trailing gap matches card gap so group2 butts flush → no empty seam on loop */
    padding-inline-end:var(--scc-gap);
}
.serik-contact-carousel__card{
    flex:0 0 var(--scc-card-w);
    width:var(--scc-card-w);
    aspect-ratio:3 / 4;
    border-radius:14px;
    overflow:hidden;
    background:#e8eef5;
    box-shadow:0 10px 28px rgba(11,35,64,.12);
}
.serik-contact-carousel__card img{
    display:block;
    width:100%;
    height:100%;
    object-fit:cover;
}
/* Exactly one group width = -50% of track (two equal groups) */
@keyframes serikContactMarquee{
    from{transform:translate3d(0,0,0)}
    to{transform:translate3d(-50%,0,0)}
}
@media (max-width:991px){
    .serik-contact-carousel{
        --scc-gap:10px;
        --scc-card-w:clamp(120px, 32vw, 170px);
        padding:1rem 0 1.35rem;
    }
}
@media (prefers-reduced-motion:reduce){
    .serik-contact-carousel__track{animation:none}
}

@media (max-width: 991px) {
    .about-mobile-style {
        padding: 40px 0 !important;
        height: auto !important;
    }
    .heading-breadcrumb{
        font-size: clamp(1.35rem, 5.5vw, 2rem);
        line-height: 1.2;
        font-weight: 600;
    }

}
</style>


@if ($showBreadcrumb === 'yes')
    <section @class([
        'flat-title-page style-2',
        'hero-overlay' => $useHeroStyle,
        'about-mobile-style' => $isAboutUs,
        'serik-contact-hero-short' => $isContactUs,
    ])
    id="sectionhead"
    @style([
        "background-color: $backgroundColor",
        "color: $textColor",
        "background-image: url('{$backgroundImage}'); background-size: cover; background-position: center !important" => $backgroundImage,
    ])>

        @if ($useHeroStyle)
            <div class="overlay"></div>
        @endif

        <div class="container position-relative py-3 py-md-4">
            @if ($breadcrumbStyle !== 'without-title' && $pageH1)
                <h1 @class([
                    'page-title mt-3 mb-0',
                    'text-center text-white heading-breadcrumb' => $useHeroStyle,
                    'text-start' => ! $useHeroStyle,
                    'serik-page-h1' => true,
                ])>
                    {!! BaseHelper::clean($pageH1) !!}
                </h1>
            @endif

            @php
                $heroIntro = Theme::get('pageHeroIntro');
                $isFaqs = request()->is('faqs');
            @endphp
            @if ($isFaqs && $heroIntro)
                <p class="serik-page-intro text-center {{ $useHeroStyle ? 'text-white' : '' }}">
                    {!! BaseHelper::clean($heroIntro) !!}
                </p>
            @endif

            @if ($isAboutUs)
                <p class="text-center text-white fw-semibold fs-5">
                    Guiding you through every step of your real estate journey with expertise and integrity.
                </p>
            @endif

            <ul @class(['breadcrumb', 'text-white' => $useHeroStyle, 'mt-3' => $pageH1])>
                @foreach(Theme::breadcrumb()->getCrumbs() as $crumb)
                    <li>
                        @if($loop->last)
                            {!! BaseHelper::clean($crumb['label']) !!}
                        @else
                            <a href="{{ $crumb['url'] }}" @class(['text-white' => $useHeroStyle])>
                                {!! BaseHelper::clean($crumb['label']) !!}
                            </a>
                            <span class="ms-1">/</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

@if ($isContactUs && $contactCarouselSlides)
    <section class="serik-contact-carousel" aria-label="{{ __('Contact gallery') }}">
        <div class="container">
            <div class="serik-contact-carousel__viewport">
                <div class="serik-contact-carousel__track">
                    {{-- Two identical groups → translateX(-50%) loops with zero gap --}}
                    @foreach ([false, true] as $isClone)
                        <div class="serik-contact-carousel__group" @if ($isClone) aria-hidden="true" @endif>
                            @foreach ($contactCarouselSlides as $i => $slideUrl)
                                <div class="serik-contact-carousel__card">
                                    <img
                                        src="{{ $slideUrl }}"
                                        alt=""
                                        loading="{{ (! $isClone && $i < 4) ? 'eager' : 'lazy' }}"
                                        decoding="async"
                                        width="240"
                                        height="320"
                                    >
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
