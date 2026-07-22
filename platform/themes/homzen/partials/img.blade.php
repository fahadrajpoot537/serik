@php
    use App\Support\ImageAlt;

    $src = (string) ($src ?? '');
    $resolvedAlt = ImageAlt::resolve(
        $alt ?? null,
        $media ?? $src,
        $context ?? null,
        (bool) ($decorative ?? false)
    );
    $extraAttributes = $attributes ?? [];
@endphp
<img
    src="{{ $src }}"
    alt="{{ $resolvedAlt }}"
    @foreach ($extraAttributes as $attrKey => $attrValue)
        @if (is_bool($attrValue))
            @if ($attrValue) {{ $attrKey }} @endif
        @else
            {{ $attrKey }}="{{ $attrValue }}"
        @endif
    @endforeach
/>
