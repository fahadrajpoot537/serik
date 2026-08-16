@php
    // Site-wide light footer — always use the color logo.
    $logoSrc = theme_option('logo')
        ? RvMedia::getImageUrl(theme_option('logo'))
        : asset('storage/white-logo.png');
@endphp

<div class="footer-logo">
    <a href="{{ BaseHelper::getHomepageUrl() }}">
        <img
            src="{{ $logoSrc }}"
            width="160"
            height="44"
            decoding="async"
            loading="eager"
            fetchpriority="high"
            data-bb-lazy="false"
            style="max-height: 44px !important"
            alt="{{ theme_option('site_title', 'Serik Realty') }}"
        >
    </a>
</div>
