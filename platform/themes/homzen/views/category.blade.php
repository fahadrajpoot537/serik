@php
    Theme::set('pageTitle', $category->name);
    Theme::set('pageH1', $category->name);
@endphp

@include(Theme::getThemeNamespace('views.loop'))
