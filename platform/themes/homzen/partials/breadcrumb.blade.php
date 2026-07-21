@php
    $backgroundColor = Theme::get('breadcrumbBackgroundColor', theme_option('breadcrumb_background_color', '#f7f7f7'));
    $textColor = Theme::get('breadcrumbTextColor', theme_option('breadcrumb_text_color', '#161e2d'));
    $backgroundImage = Theme::get('breadcrumbBackgroundImage', theme_option('breadcrumb_background_image') ?: null);

    $backgroundImage = $backgroundImage ? RvMedia::getImageUrl($backgroundImage) : null;

    $showBreadcrumb = Theme::get('breadcrumbEnabled', 'yes');
    $breadcrumbStyle = Theme::get('breadcrumbStyle', 'default');
    $pageH1 = \App\Support\PageH1::resolve();
    $isAboutUs = request()->is('about-us');
    $useHeroStyle = $isAboutUs || $backgroundImage;
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

@media (max-width: 991px) {
   #sectionhead{
    height:100px;
}
}


.about-mobile-style {
    padding: 200px 0;
    height: auto !important;
}
.heading-breadcrumb{
    font-size:100px;font-weight: 0;
}

@media (max-width: 991px) {
    .about-mobile-style {
        padding: 40px 0 !important;
        height: auto !important;
    }
    .heading-breadcrumb{
        font-size:40px;font-weight: 0;
    }

}
</style>


@if ($showBreadcrumb === 'yes')
    <section @class([
        'flat-title-page style-2',
        'hero-overlay' => $useHeroStyle,
        'about-mobile-style' => $isAboutUs,
    ])
    id="sectionhead"
    @style([
        "background-color: $backgroundColor",
        "color: $textColor",
        "background-image: url($backgroundImage); background-size: cover; background-position: center !important" => $backgroundImage,
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
                ])>
                    {!! BaseHelper::clean($pageH1) !!}
                </h1>
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
