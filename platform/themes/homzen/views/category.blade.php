@php
    Theme::set('pageTitle', $category->name);
@endphp

<h1 class="d-none">{{ $category->name }}</h1>

@include(Theme::getThemeNamespace('views.loop'))
