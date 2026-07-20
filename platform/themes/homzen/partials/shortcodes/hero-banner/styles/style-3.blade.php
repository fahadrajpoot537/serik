@php
    $backgroundImage = $shortcode->background_image ? RvMedia::getImageUrl($shortcode->background_image) : null;
    $titleColor = $shortcode->title_color ?: '#000000';
    $descriptionColor = $shortcode->description_color ?: '#000000';
@endphp

<section class="flat-slider home-3" @style(["background-image: url('$backgroundImage') !important" => $backgroundImage])>
    <div class="container relative">
        <div class="row position-relative">
            <div class="col-xl-8 col-lg-7">
                <div class="slider-content">
                    <div class="heading">
                        <h2 class="title wow fadeIn animationtext clip" style="color: {{ $titleColor }} !important;" data-wow-delay=".2s" data-wow-duration="2000ms">
                            {!! BaseHelper::clean($shortcode->title) !!}
                            <br>
                            {!! Theme::partial('shortcodes.hero-banner.partials.animation-text', compact('shortcode')) !!}
                        </h2>
                        @if ($shortcode->description)
                            <p class="subtitle body-1 wow fadeIn" style="color: {{ $descriptionColor }} !important;" data-wow-delay=".8s" data-wow-duration="2000ms">
                                {!! BaseHelper::clean($shortcode->description) !!}
                            </p>
                        @endif
                    </div>
                    {!! Theme::partial('shortcodes.hero-banner.partials.action-button', compact('shortcode')) !!}
                </div>
            </div>
            <div class="col-xl-4 col-lg-5">
                @if(is_plugin_active('real-estate') && $shortcode->search_box_enabled)
                    @include(Theme::getThemeNamespace('views.real-estate.partials.search-box'), ['style' => 3])
                @endif
            </div>
        </div>
    </div>
</section>
