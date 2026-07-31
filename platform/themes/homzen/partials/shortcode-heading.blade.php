@php
    $animation ??= true;
    $centered ??= true;
    $buttonLabel ??= $shortcode->button_label;
    $buttonUrl ??= $shortcode->button_url;
    $hasButton ??= $buttonLabel && $buttonUrl;
    $headingTag = $headingTag ?? 'h2';
    $pageTitle = $shortcode->title ?: ($defaultTitle ?? null);
@endphp


<style>
    @media (max-width: 768px) {
    .button-prop {
      display:none;
    }

    /* Categories "View All" must stay usable without overlapping the title */
    #page-home .flat-categories .btn-view.button-prop {
      display: inline-flex !important;
      float: none !important;
      margin: 0.65rem 0 0 !important;
      position: static !important;
    }
}

#page-home .section-title {
    letter-spacing: -0.02em;
}

#page-home .btn-view.button-prop {
    transition: color 0.25s cubic-bezier(0.22, 1, 0.36, 1), gap 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}
</style>

@if($pageTitle || $shortcode->subtitle)
    <div style="display: block;margin-bottom:30px;"
        @class(['text-center' => $centered && ! $hasButton, 'wow fadeIn' => $animation, 'style-1' => $hasButton, $class ?? null])
        @if($animation)
            data-wow-delay=".2s" data-wow-duration="2000ms"
        @endif
    >
        @if($hasButton)
            <div class="box-left">
        @endif
      
        @if($pageTitle)
            <{{ $headingTag }} class="section-title mt-4" style="font-weight: 700;text-align:left;color: #000;">{!! BaseHelper::clean($pageTitle) !!}</{{ $headingTag }}>
        @endif
          @if($shortcode->subtitle)
            <div  style="text-align:left;color: #000;">{!! BaseHelper::clean($shortcode->subtitle) !!}</div>
        @endif
        @if($hasButton )
            </div>

            <a href="{{ $buttonUrl }}" class="btn-view button-prop" style="float:right; margin-top:-70px;">
                <span class="text" style="font-weight: 500;">{{ $buttonLabel }}</span>
                <x-core::icon name="ti ti-arrow-right" class="icon" style="stroke-width: 2" />
            </a>
        @endif
        
        @if($shortcode->subtitle == 'Latest News' )
           

            <a href="{{ get_blog_page_url() }}" class="btn-view button-prop" style="float:right; margin-top:-70px;">
                <span class="text" style="font-weight: 700;">View All</span>
                <x-core::icon name="ti ti-arrow-right" class="icon" style="stroke-width: 2" />
            </a>
        @endif
    </div>
@endif
