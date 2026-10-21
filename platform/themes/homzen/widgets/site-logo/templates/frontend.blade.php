@php
    $logoSrc = asset('newlogo.png') . '?v=3';
@endphp

<div class="footer-logo serik-footer-logo">
    <a href="{{ BaseHelper::getHomepageUrl() }}">
        <img
            src="{{ $logoSrc }}"
            width="200"
            height="48"
            decoding="async"
            loading="eager"
            data-bb-lazy="false"
            class="serik-footer-logo__img"
            alt="{{ theme_option('site_title', 'Serik Realty') }}"
        >
    </a>
</div>
