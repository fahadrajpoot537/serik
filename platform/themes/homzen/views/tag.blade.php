@php
    Theme::set('pageTitle', $tag->name);
    Theme::set('pageH1', $tag->name);
@endphp

@include(Theme::getThemeNamespace('views.loop'))
