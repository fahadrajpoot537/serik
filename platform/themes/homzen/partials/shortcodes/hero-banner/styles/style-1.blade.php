@php
    $backgroundImage = $shortcode->background_image ? RvMedia::getImageUrl($shortcode->background_image) : null;
    $titleColor = $shortcode->title_color ?: '#ffffff';
    $descriptionColor = $shortcode->description_color ?: '#ffffff';

    // Collect multiple slider images (up to 4)
    $sliderImages = [];
    for ($i = 1; $i <= 4; $i++) {
        $imageKey = "slider_image_{$i}";
        if ($shortcode->$imageKey) {
            $sliderImages[] = RvMedia::getImageUrl($shortcode->$imageKey);
        }
    }

    // Include the main background image if it exists
    if ($backgroundImage) {
        array_unshift($sliderImages, $backgroundImage);
    }

    // Filter out empty values
    $sliderImages = array_filter($sliderImages);

    // Set rotation interval (default 5 seconds)
    $rotationInterval = (int) ($shortcode->rotation_interval ?: 5);

    // Enable/disable automatic rotation
    $enableRotation = $shortcode->enable_rotation ?: 'yes';
@endphp

<section class="flat-slider home-1 hero-banner-slider"
         @style(["background-image: url('$backgroundImage') !important" => $backgroundImage])
         data-slider-images="{{ json_encode($sliderImages) }}"
         data-rotation-interval="{{ $rotationInterval }}"
         data-enable-rotation="{{ $enableRotation }}"
         style="transition: background-image 1s ease-in-out;">
    <div class="container relative">
        <div class="row">
            <div class="col-lg-12">
                <div class="slider-content">
                    <div class="text-center">
                        <div class="heading">
                            <h1 class="animationtext slide" style="color: {{ $titleColor }} !important;">
                                {!! BaseHelper::clean($shortcode->title) !!}
                                {!! Theme::partial('shortcodes.hero-banner.partials.animation-text', compact('shortcode')) !!}
                            </h1>
                            @if ($shortcode->description)
                                <p class="subtitle body-1 wow fadeIn" style="color: {{ $descriptionColor }} !important;" data-wow-delay=".8s" data-wow-duration="2000ms">
                                    {!! BaseHelper::clean($shortcode->description) !!}
                                </p>
                            @endif
                        </div>
                        {!! Theme::partial('shortcodes.hero-banner.partials.action-button', ['shortcode' => $shortcode, 'class' => 'mb-4']) !!}
                    </div>
                    @if(is_plugin_active('real-estate') && $shortcode->search_box_enabled)
                        @include(Theme::getThemeNamespace('views.real-estate.partials.search-box'), ['style' => 1, 'centeredTabs' => true])
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="overlay"></div>
</section>
