@php
    Theme::set('pageTitle', __('Search result for: ":query"', ['query' => BaseHelper::stringify(request()->input('q'))]))
@endphp

<h1 class="d-none">{{ __('Search result for: ":query"', ['query' => BaseHelper::stringify(request()->input('q'))]) }}</h1>

@include(Theme::getThemeNamespace('views.loop'))
