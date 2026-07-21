@php
    Theme::set('pageTitle', __('Search result for: ":query"', ['query' => BaseHelper::stringify(request()->input('q'))]));
    Theme::set('pageH1', __('Search result for: ":query"', ['query' => BaseHelper::stringify(request()->input('q'))]));
@endphp

@include(Theme::getThemeNamespace('views.loop'))
