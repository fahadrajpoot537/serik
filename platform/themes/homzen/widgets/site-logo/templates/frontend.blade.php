@php
    $logoSrc = is_file(public_path('newlogo.png'))
        ? asset('newlogo.png') . '?v=2'
        : (theme_option('logo_light')
            ? RvMedia::getImageUrl(theme_option('logo_light'))
            : asset('newlogo.png') . '?v=2');
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
            style="max-height: 44px !important; width: auto;"
            alt="{{ theme_option('site_title', 'Serik Realty') }}"
        >
    </a>
</div>
