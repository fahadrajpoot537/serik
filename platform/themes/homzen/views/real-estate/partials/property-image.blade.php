@php
    $coverImage = $property->cover_image ?? RvMedia::getDefaultImage();
    $isExternal = is_string($coverImage) && str_starts_with($coverImage, 'http');
    $size = $size ?? 'medium-rectangle';
    $lazy = $lazy ?? true;
    $altParts = array_filter([
        $property->name,
        $property->external_id ?? $property->unique_id ?? null,
        $property->type?->name ?? (is_string($property->type ?? null) ? $property->type : null),
    ]);
    $imageAlt = $altParts ? implode(' - ', array_unique($altParts)) : __('Property listing');
@endphp

@if ($isExternal)
    <img
        src="{{ $coverImage }}"
        alt="{{ $imageAlt }}"
        class="img-fluid w-100 h-100 object-fit-cover"
        @if ($lazy) loading="lazy" @endif
        onerror="this.src='{{ RvMedia::getDefaultImage() }}'"
    />
@else
    {{ RvMedia::image($coverImage, $imageAlt, $size, attributes: ['class' => 'img-fluid w-100 h-100 object-fit-cover'], lazy: $lazy) }}
@endif
