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

    /* Categories heading: stack title + subtitle + View All (no float overlap) */
    #page-home .flat-categories .serik-hp-heading,
    #page-home .flat-categories .wow.fadeIn.style-1 {
      display: flex !important;
      flex-direction: column !important;
      align-items: flex-start !important;
      width: 100% !important;
      max-width: 100% !important;
      margin-bottom: 1rem !important;
      gap: 0.35rem;
    }

    #page-home .flat-categories .box-left {
      display: block !important;
      width: 100% !important;
      max-width: 100% !important;
    }

    #page-home .flat-categories .section-title {
      margin-top: 0 !important;
      margin-bottom: 0.35rem !important;
      font-size: 1.35rem !important;
      line-height: 1.25 !important;
      text-align: left !important;
      white-space: normal !important;
      overflow-wrap: break-word !important;
      word-break: normal !important;
    }

    #page-home .flat-categories .box-left > div,
    #page-home .flat-categories .serik-hp-heading > div {
      width: 100% !important;
      max-width: 100% !important;
      font-size: 0.9rem !important;
      line-height: 1.45 !important;
      color: #5b6573 !important;
      white-space: normal !important;
      overflow-wrap: break-word !important;
      word-break: normal !important;
    }

    #page-home .flat-categories .btn-view.button-prop {
      display: inline-flex !important;
      float: none !important;
      margin: 0.55rem 0 0 !important;
      position: static !important;
      align-self: flex-start;
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
    <div style="display:block;width:100%;margin-bottom:30px;"
        @class(['text-center' => $centered && ! $hasButton, 'wow fadeIn' => $animation, 'style-1' => $hasButton, 'serik-hp-heading' => true, $class ?? null])
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
