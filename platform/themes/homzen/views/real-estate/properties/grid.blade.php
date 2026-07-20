@php
    $itemsPerRow ??= 4;
@endphp

@if ($properties->isNotEmpty())
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3 g-xl-4 serik-prop-grid">
        @foreach($properties as $property)
            <div class="col d-flex">
                @include(Theme::getThemeNamespace('views.real-estate.properties.item-grid'))
            </div>
        @endforeach
    </div>
@endif
