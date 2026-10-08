@php
    // Dark footer — white wordmark so "Serik Realty" reads on #161e2d.
    $logoLight = theme_option('logo_light');
    if ($logoLight) {
        $logoSrc = RvMedia::getImageUrl($logoLight);
    } elseif (is_file(public_path('storage/white-logo.png'))) {
        $logoSrc = asset('storage/white-logo.png');
    } else {
        $logoSrc = theme_option('logo')
            ? RvMedia::getImageUrl(theme_option('logo'))
            : Theme::asset()->url('images/logo.png');
    }
@endphp

<div class="footer-logo">
    <a href="{{ BaseHelper::getHomepageUrl() }}">
        <img
            src="{{ $logoSrc }}"
            width="160"
            height="44"
            decoding="async"
            loading="eager"
            data-bb-lazy="false"
            style="max-height: 44px !important"
            alt="{{ theme_option('site_title', 'Serik Realty') }}"
        >
    </a>
</div>
