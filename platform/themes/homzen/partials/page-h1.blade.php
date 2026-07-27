@php
    $text = trim((string) ($text ?? \App\Support\PageH1::resolve()));
    $variant = $variant ?? 'inline';
@endphp

@if ($text !== '')
    @if ($variant === 'map')
        <h1 class="hs-map-page-h1">{{ $text }}</h1>
    @elseif ($variant === 'visually-compact')
        <div class="serik-page-h1-wrap serik-page-h1-wrap--compact">
            <h1 class="serik-page-h1">{{ $text }}</h1>
        </div>
    @elseif ($variant === 'ontario-seo')
        <div class="serik-page-h1-wrap serik-page-h1-wrap--ontario container-fluid px-3 px-lg-4 py-3">
            <h1 class="serik-page-h1">{{ $text }}</h1>
        </div>
    @else
        <div class="serik-page-h1-wrap container py-3">
            <h1 class="serik-page-h1">{{ $text }}</h1>
        </div>
    @endif
@endif

<style>
    .serik-page-h1-wrap {
        background: #fff;
    }

    .serik-page-h1 {
        margin: 0;
        font-size: clamp(1.5rem, 2.4vw, 2.25rem);
        font-weight: 700;
        line-height: 1.25;
        color: #161e2d;
    }

    .serik-page-h1-wrap--compact {
        padding: 10px 14px 0;
        background: transparent;
    }

    .serik-page-h1-wrap--compact .serik-page-h1 {
        font-size: clamp(1.125rem, 2vw, 1.5rem);
    }

    .serik-page-h1-wrap--ontario {
        border-bottom: 1px solid #e8ecf1;
    }

    .serik-page-h1-wrap--ontario .serik-page-h1 {
        font-size: clamp(1.25rem, 2.2vw, 1.75rem);
    }

    .hs-map-page-h1 {
        margin: 0;
        padding: 8px 14px 0;
        font-size: clamp(1.125rem, 2vw, 1.5rem);
        font-weight: 700;
        line-height: 1.25;
        color: #161e2d;
        background: #fff;
    }
</style>
