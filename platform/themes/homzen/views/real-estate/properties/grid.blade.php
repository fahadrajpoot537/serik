@php
    $itemsPerRow ??= 3;
@endphp

@if ($properties->isNotEmpty())
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 g-lg-4 serik-prop-grid">
        @foreach($properties as $property)
            <div class="col d-flex">
                @include(Theme::getThemeNamespace('views.real-estate.properties.item-grid'))
            </div>
        @endforeach
    </div>
@endif
