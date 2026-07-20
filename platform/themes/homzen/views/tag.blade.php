@php
    Theme::set('pageTitle', $tag->name);
@endphp

<h1 class="d-none">{{ $tag->name }}</h1>

@include(Theme::getThemeNamespace('views.loop'))
