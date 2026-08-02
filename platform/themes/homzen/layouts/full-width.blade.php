@extends(Theme::getThemeNamespace('layouts.base'))

@section('content')
@php
    $isHomeShell = \App\Support\SerikHomepage::isHomepageRequest();
@endphp

<div @class(['serik-hp-shell' => $isHomeShell])>
    {!! apply_filters('ads_render', null, 'header_before') !!}
    {!! apply_filters('theme_front_header_content', null) !!}

    {!! Theme::partial('top-header') !!}

    <div class="serik-site-header" id="serikSiteHeader">
        {!! Theme::partial('header') !!}
    </div>

    {!! apply_filters('ads_render', null, 'header_after') !!}

    @if(Theme::get('breadcrumbEnabled', 'yes') === 'yes')
        {!! Theme::breadcrumb()->render(Theme::getThemeNamespace('partials.breadcrumb')) !!}
    @elseif (! Theme::get('pageH1ProvidedByContent') && \App\Support\PageH1::resolve())
        {!! Theme::partial('page-h1', ['variant' => Theme::get('pageH1Variant', 'inline')]) !!}
    @endif

    <main @class(['serik-hp-main' => $isHomeShell]) id="serikHomepageMain">
        {!! Theme::content() !!}
    </main>

    {!! apply_filters('ads_render', null, 'footer_before') !!}
    {!! apply_filters('theme_front_footer_content', null) !!}

    <div @class(['serik-hp-footer-wrap' => $isHomeShell])>
        {!! Theme::partial('footer') !!}
    </div>

    {!! apply_filters('ads_render', null, 'footer_after') !!}
</div>

@if ($isHomeShell)
{{-- After footer scripts (WOW/jQuery): force homepage sections visible. WOW.init() re-hides .wow after earlier reveals. --}}
<script>
(function () {
    function revealHomepageSections() {
        document.querySelectorAll('#page-home .wow').forEach(function (el) {
            el.style.setProperty('visibility', 'visible', 'important');
            el.style.setProperty('opacity', '1', 'important');
            el.style.setProperty('transform', 'none', 'important');
            el.style.setProperty('animation', 'none', 'important');
            el.classList.add('animated');
        });
        document.querySelectorAll('#page-home .serik-hp-cats, #page-home .serik-hp-cats__wrap, #page-home .flat-categories').forEach(function (el) {
            el.style.setProperty('visibility', 'visible', 'important');
            el.style.setProperty('opacity', '1', 'important');
            el.style.setProperty('display', 'block', 'important');
            el.style.setProperty('height', 'auto', 'important');
        });
    }
    function run() {
        revealHomepageSections();
        [100, 400, 1000, 2000].forEach(function (ms) {
            setTimeout(revealHomepageSections, ms);
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
    window.addEventListener('load', revealHomepageSections);
})();
</script>
@endif
@endsection
