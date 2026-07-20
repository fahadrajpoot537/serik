@php
    $coverImage = $property->cover_image ?? RvMedia::getDefaultImage();
    $isExternal = is_string($coverImage) && str_starts_with($coverImage, 'http');
    $size = $size ?? 'medium-rectangle';
    $lazy = $lazy ?? true;
@endphp

@if ($isExternal)
    <img
        src="{{ $coverImage }}"
        alt="{{ $property->name }}"
        class="img-fluid w-100 h-100 object-fit-cover"
        @if ($lazy) loading="lazy" @endif
        onerror="this.src='{{ RvMedia::getDefaultImage() }}'"
    />
@else
    {{ RvMedia::image($coverImage, $property->name, $size, attributes: ['class' => 'img-fluid w-100 h-100 object-fit-cover'], lazy: $lazy) }}
@endif
