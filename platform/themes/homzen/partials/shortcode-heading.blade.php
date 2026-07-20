@php
    $animation ??= true;
    $centered ??= true;
    $buttonLabel ??= $shortcode->button_label;
    $buttonUrl ??= $shortcode->button_url;
    $hasButton ??= $buttonLabel && $buttonUrl;
@endphp


<style>
    @media (max-width: 768px) {
    .button-prop {
      display:none;
    }
}
</style>

@if($shortcode->title || $shortcode->subtitle)
    <div style="display: block;margin-bottom:30px;"
        @class(['text-center' => $centered && ! $hasButton, 'wow fadeIn' => $animation, 'style-1' => $hasButton, $class ?? null])
        @if($animation)
            data-wow-delay=".2s" data-wow-duration="2000ms"
        @endif
    >
        @if($hasButton)
            <div class="box-left">
        @endif
      
        @if($shortcode->title)
            <h2 class="section-title mt-4" style="font-weight: 700;text-align:left;color: #000;">{!! BaseHelper::clean($shortcode->title) !!}</h2>
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
           

            <a href="blog" class="btn-view button-prop" style="float:right; margin-top:-70px;">
                <span class="text" style="font-weight: 700;">View All</span>
                <x-core::icon name="ti ti-arrow-right" class="icon" style="stroke-width: 2" />
            </a>
        @endif
    </div>
@endif
