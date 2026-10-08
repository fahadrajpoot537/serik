@php
    // Dark footer always uses the white wordmark (ignore theme logo / logo_light —
    // those are the blue color mark used in the header).
    $logoSrc = is_file(public_path('storage/white-logo.png'))
        ? asset('storage/white-logo.png')
        : (theme_option('logo_light')
            ? RvMedia::getImageUrl(theme_option('logo_light'))
            : asset('storage/white-logo.png'));
@endphp

<div class="footer-logo serik-footer-logo">
    <a href="{{ BaseHelper::getHomepageUrl() }}">
        <img
            src="{{ $logoSrc }}"
            width="160"
            height="44"
            decoding="async"
            loading="eager"
            data-bb-lazy="false"
            class="serik-footer-logo__img"
            style="max-height: 44px !important"
            alt="{{ theme_option('site_title', 'Serik Realty') }}"
        >
    </a>
</div>
