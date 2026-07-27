@php
    use App\Support\ImageAlt;
    use App\Support\ImageDimensions;
    use App\Support\SerikMediaUrl;
    use App\Support\TrebResponsiveImage;

    $listingKey = strtoupper(trim((string) ($property->external_id ?? '')));
    $size = $size ?? 'medium-rectangle';
    $lazy = $lazy ?? true;
    $imageAlt = ImageAlt::forProperty($property);
    $imgAttrs = ImageDimensions::htmlAttributes($size, $lazy);

    // Same-origin TREB proxy → real-time WebP (like homepage / map).
    if ($listingKey !== '') {
        $coverImage = SerikMediaUrl::mapListingCover($listingKey, $property->image_val ?? null);
    } else {
        $coverImage = $property->cover_image ?? RvMedia::getDefaultImage();
    }

    $isProxyWebp = TrebResponsiveImage::isProxyUrl($coverImage);
    $isExternal = is_string($coverImage) && str_starts_with($coverImage, 'http') && ! $isProxyWebp;
    $responsiveAttrs = $isProxyWebp ? TrebResponsiveImage::cardAttributes($coverImage, $lazy) : [];

    // Default card src uses a mid-width WebP derivative so browsers never get raw JPEG.
    if ($isProxyWebp) {
        $coverImage = TrebResponsiveImage::urlWithWidth($coverImage, 640);
    }

    $placeholder = SerikMediaUrl::placeholder();
@endphp

@if ($isProxyWebp || $isExternal)
    <img
        src="{{ $coverImage }}"
        alt="{{ $imageAlt }}"
        class="img-fluid w-100 h-100 object-fit-cover property-image"
        @if ($listingKey !== '') data-key="{{ $listingKey }}" @endif
        @foreach ($imgAttrs as $attrKey => $attrValue)
            {{ $attrKey }}="{{ $attrValue }}"
        @endforeach
        @foreach ($responsiveAttrs as $attrKey => $attrValue)
            {{ $attrKey }}="{{ $attrValue }}"
        @endforeach
        onerror="this.onerror=null;this.src='{{ $placeholder }}'"
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
