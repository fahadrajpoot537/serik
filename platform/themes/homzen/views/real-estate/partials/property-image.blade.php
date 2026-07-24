@php
    use App\Support\ImageAlt;
    use App\Support\ImageDimensions;

    $coverImage = $property->cover_image ?? RvMedia::getDefaultImage();
    $isExternal = is_string($coverImage) && str_starts_with($coverImage, 'http');
    $size = $size ?? 'medium-rectangle';
    $lazy = $lazy ?? true;
    $imageAlt = ImageAlt::forProperty($property);
    $imgAttrs = ImageDimensions::htmlAttributes($size, $lazy);
@endphp

@if ($isExternal)
    <img
        src="{{ $coverImage }}"
        alt="{{ $imageAlt }}"
        class="img-fluid w-100 h-100 object-fit-cover"
        @foreach ($imgAttrs as $attrKey => $attrValue)
            {{ $attrKey }}="{{ $attrValue }}"
        @endforeach
        onerror="this.src='{{ RvMedia::getDefaultImage() }}'"
    />
@else
    @php
        $imageAttributes = array_merge(
            ['class' => 'img-fluid w-100 h-100 object-fit-cover'],
            $imgAttrs
        );
    @endphp
    {{ RvMedia::image($coverImage, $imageAlt, $size, attributes: $imageAttributes, lazy: $lazy) }}
@endif
