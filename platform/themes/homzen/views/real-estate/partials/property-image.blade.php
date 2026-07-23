@php
    use App\Support\ImageAlt;

    $coverImage = $property->cover_image ?? RvMedia::getDefaultImage();
    $isExternal = is_string($coverImage) && str_starts_with($coverImage, 'http');
    $size = $size ?? 'medium-rectangle';
    $lazy = $lazy ?? true;
    $imageAlt = ImageAlt::forProperty($property);
@endphp

@if ($isExternal)
    <img
        src="{{ $coverImage }}"
        alt="{{ $imageAlt }}"
        class="img-fluid w-100 h-100 object-fit-cover"
        @if ($lazy) loading="lazy" @else loading="eager" fetchpriority="high" @endif
        onerror="this.src='{{ RvMedia::getDefaultImage() }}'"
    />
@else
    @php
        $imageAttributes = ['class' => 'img-fluid w-100 h-100 object-fit-cover'];
        if (! $lazy) {
            $imageAttributes['loading'] = 'eager';
            $imageAttributes['fetchpriority'] = 'high';
        }
    @endphp
    {{ RvMedia::image($coverImage, $imageAlt, $size, attributes: $imageAttributes, lazy: $lazy) }}
@endif
