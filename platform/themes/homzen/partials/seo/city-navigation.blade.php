@php
    $layout = $layout ?? 'properties';
    $sections = $sections ?? [];
    $currentCity = $current_city ?? null;
    $currentCommunity = $current_community ?? null;
    $colClass = $layout === 'home' ? 'col-md-4' : 'col-md-3';
    $mobileColClass = $layout === 'home' ? 'col-12' : 'col-6';
    $wrapClass = $layout === 'properties' ? 'container-fluid' : 'container';
@endphp

@if ($sections !== [])
<section class="seo-city-navigation seo-city-navigation--{{ $layout }}" aria-label="{{ __('Ontario real estate navigation') }}">
    <div class="{{ $wrapClass }} px-3 px-lg-4">
        @if ($layout === 'properties' && ($currentCommunity || $currentCity))
            <p class="seo-nav-context mb-3">
                @if ($currentCommunity)
                    {{ __('Browsing') }}: <strong>{{ $currentCommunity }}</strong>@if ($currentCity), {{ $currentCity->name }}@endif
                @elseif ($currentCity)
                    {{ __('Browsing') }}: <strong>{{ $currentCity->name }}</strong>
                @endif
            </p>
        @endif
        <div class="row seo-nav-row">
            @foreach ($sections as $section)
                @if (($section['links'] ?? []) !== [])
                    <div class="{{ $mobileColClass }} {{ $colClass }} seo-nav-col">
                        <div class="seo-nav-block">
                            <h2 class="seo-nav-title">
                                {{ $section['title'] }}
                                @if (! empty($section['subtitle']))
                                    <span class="seo-nav-subtitle-inline">{{ $section['subtitle'] }}</span>
                                @endif
                            </h2>

                            <nav class="seo-nav-list" aria-label="{{ $section['title'] }}">
                                @foreach ($section['links'] as $link)
                                    <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                                @endforeach
                            </nav>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</section>

<style>
.seo-city-navigation {
    padding: 2.5rem 0;
    border-top: 1px solid #e8ecf1;
    margin-top: 2rem;
    background: #fafbfc;
}
.seo-nav-context {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}
.seo-nav-row {
    row-gap: 1.75rem;
}
.seo-nav-col {
    display: flex;
    flex-direction: column;
}
.seo-nav-block {
    height: 100%;
}
.seo-nav-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 0.875rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #0255a1;
}
.seo-nav-subtitle-inline {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #64748b;
    margin-top: 0.15rem;
}
.seo-nav-list {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}
.seo-nav-list a {
    color: #0255a1;
    text-decoration: none;
    font-size: 0.875rem;
    line-height: 1.5;
    display: block;
    padding: 0.1rem 0;
}
.seo-nav-list a:hover {
    text-decoration: underline;
    color: #013d73;
}
@media (max-width: 767.98px) {
    .seo-city-navigation { padding: 1.5rem 0; }
}
</style>
@endif
