<section
    class="flat-section flat-project"
    @style(["background-color: $shortcode->background_color" => $shortcode->background_color])
>
    <div class="container">
        {!! Theme::partial('shortcode-heading', ['shortcode' => $shortcode, 'centered' => false]) !!}

        @php
            $firstProject = $projects->first();
        @endphp

        <div class="wrap-project row row-cols-lg-2 g-4">
            @if ($firstProject)
                <div class="box-left wow fadeInLeftSmall" data-wow-delay=".2s" data-wow-duration="2000ms">
                    @include(Theme::getThemeNamespace('views.real-estate.projects.item-grid'), ['project' => $firstProject, 'class' => 'lg'])
                </div>
            @endif

            @if ($projects->count() > 1)
                <div class="box-right wow fadeInRightSmall" data-wow-delay=".2s" data-wow-duration="2000ms">
                    @foreach($projects->skip(1) as $project)
                        @include(Theme::getThemeNamespace('views.real-estate.projects.item-list'))
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
