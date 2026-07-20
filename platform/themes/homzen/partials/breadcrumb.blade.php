@php
    $backgroundColor = Theme::get('breadcrumbBackgroundColor', theme_option('breadcrumb_background_color', '#f7f7f7'));
    $textColor = Theme::get('breadcrumbTextColor', theme_option('breadcrumb_text_color', '#161e2d'));
    $backgroundImage = Theme::get('breadcrumbBackgroundImage', theme_option('breadcrumb_background_image') ?: null);

    $backgroundImage = $backgroundImage ? RvMedia::getImageUrl($backgroundImage) : null;

    $showBreadcrumb = Theme::get('breadcrumbEnabled', 'yes');
    $breadcrumbStyle = Theme::get('breadcrumbStyle', 'default');
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
    background:rgba(0,0,0,0.55); /* adjust opacity here */
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


/* Default (desktop stays same) */
.about-mobile-style {
    padding: 200px 0;
    height: auto !important;
}
.heading-breadcrumb{
    font-size:100px;font-weight: 0;
}

/* Only mobile */
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
    <section class="flat-title-page style-2 hero-overlay {{ request()->is('about-us') ? 'about-mobile-style' : '' }}"
    id="sectionhead"
    @style([
        "background-color: $backgroundColor",
        "background-image: url($backgroundImage); background-size: cover; background-position: center !important",
    ])>

   @if(request()->is('about-us'))
    <div class="overlay"></div>


    <div class="container position-relative">
       

        @if ($breadcrumbStyle !== 'without-title')
            <h1 class="text-center page-title mt-3 mb-0 text-white heading-breadcrumb" style="">
                {!! BaseHelper::clean(Theme::get('pageTitle') ? Theme::get('pageTitle') : SeoHelper::getTitleOnly()) !!}
            </h1>
        @endif

        <p class="text-center text-white fw-semibold fs-5">
            Guiding you through every step of your real estate journey with expertise and integrity.
        </p>
        
         <ul class="breadcrumb text-white">
            @foreach(Theme::breadcrumb()->getCrumbs() as $crumb)
                <li>
                    @if($loop->last)
                        {!! BaseHelper::clean($crumb['label']) !!}
                    @else
                        <a href="{{ $crumb['url'] }}" class="text-white">
                            {!! BaseHelper::clean($crumb['label']) !!}
                        </a>
                        <span class="ms-1">/</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@else
    <div></div>
@endif
</section>
@endif
