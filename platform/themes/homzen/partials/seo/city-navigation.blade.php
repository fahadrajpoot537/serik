@php
    $layout = $layout ?? 'properties';
    $sections = $sections ?? [];
    $currentCity = $current_city ?? null;
    $currentCommunity = $current_community ?? null;
    $sectionCount = count(array_filter($sections, static fn ($s) => ($s['links'] ?? []) !== []));
    if ($layout === 'home') {
        $colClass = $sectionCount >= 4 ? 'col-lg-3 col-md-6' : 'col-md-4';
    } else {
        $colClass = 'col-md-3';
    }
    // Mobile: always 1 section per row (accordion)
    $mobileColClass = 'col-12';
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
                    @php
                        $sectionId = 'seo-nav-' . \Illuminate\Support\Str::slug((string) ($section['title'] ?? 'section')) . '-' . $loop->index;
                    @endphp
                    <div class="{{ $mobileColClass }} {{ $colClass }} seo-nav-col">
                        <div class="seo-nav-block" data-seo-nav-block>
                            <h2 class="seo-nav-title">
                                <button
                                    type="button"
                                    class="seo-nav-toggle"
                                    data-seo-nav-toggle
                                    aria-expanded="false"
                                    aria-controls="{{ $sectionId }}"
                                >
                                    <span class="seo-nav-toggle__label">
                                        {{ $section['title'] }}
                                        @if (! empty($section['subtitle']))
                                            <span class="seo-nav-subtitle-inline">{{ $section['subtitle'] }}</span>
                                        @endif
                                    </span>
                                    <span class="seo-nav-toggle__icon" aria-hidden="true">+</span>
                                </button>
                                <span class="seo-nav-title-static">
                                    {{ $section['title'] }}
                                    @if (! empty($section['subtitle']))
                                        <span class="seo-nav-subtitle-inline">{{ $section['subtitle'] }}</span>
                                    @endif
                                </span>
                            </h2>

                            <nav
                                id="{{ $sectionId }}"
                                class="seo-nav-list"
                                aria-label="{{ $section['title'] }}"
                            >
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
    padding: 2rem 0;
    border-top: 1px solid #e8ecf1;
    margin-top: 1.5rem;
    background: #fafbfc;
}
.seo-nav-context {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}
.seo-nav-row {
    row-gap: 1.5rem;
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
    margin: 0 0 0.75rem;
    padding-bottom: 0.4rem;
    border-bottom: 2px solid #0255a1;
}
.seo-nav-subtitle-inline {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #64748b;
    margin-top: 0.15rem;
}
.seo-nav-toggle {
    display: none;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    border: 0;
    background: transparent;
    padding: 0;
    text-align: left;
    color: inherit;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}
.seo-nav-title-static {
    display: block;
}
.seo-nav-toggle__icon {
    flex-shrink: 0;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 999px;
    border: 1px solid #cbd5e1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    line-height: 1;
    color: #0255a1;
    font-weight: 600;
    transition: transform 0.15s ease, background 0.15s ease;
}
.seo-nav-block.is-open .seo-nav-toggle__icon {
    transform: rotate(45deg);
    background: #e8f2fc;
}
.seo-nav-list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    max-height: none;
    overflow: visible;
}
.seo-nav-list a {
    color: #0255a1;
    text-decoration: none;
    font-size: 0.875rem;
    line-height: 1.45;
    display: block;
    padding: 0.05rem 0;
}
.seo-nav-list a:hover {
    text-decoration: underline;
    color: #013d73;
}
@media (max-width: 767.98px) {
    .seo-city-navigation { padding: 1.25rem 0; }
    .seo-nav-row {
        row-gap: 0.65rem !important;
        margin: 0 !important;
    }
    .seo-nav-col {
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
    }
    .seo-nav-title {
        margin: 0 !important;
        padding: 0 !important;
        border-bottom: 0 !important;
        font-size: 1rem !important;
    }
    .seo-nav-block {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        padding: 0.75rem 0.9rem;
        height: auto !important;
    }
    .seo-nav-toggle {
        display: flex !important;
    }
    .seo-nav-title-static {
        display: none !important;
    }
    .seo-nav-list {
        display: none !important;
        margin-top: 0.65rem;
        padding-top: 0.55rem;
        border-top: 1px solid #e8ecf1;
        max-height: none !important;
        overflow: visible !important;
    }
    .seo-nav-block.is-open .seo-nav-list {
        display: flex !important;
        flex-direction: column;
        gap: 0.25rem;
        max-height: none !important;
        overflow: visible !important;
    }
}
@media (min-width: 768px) {
    .seo-nav-toggle {
        display: none !important;
    }
    .seo-nav-title-static {
        display: block !important;
    }
}
</style>
<script>
(function () {
    if (window.__serikSeoNavAccordionBound) return;
    window.__serikSeoNavAccordionBound = true;
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-seo-nav-toggle]');
        if (!btn) return;
        var block = btn.closest('[data-seo-nav-block]');
        if (!block) return;
        e.preventDefault();
        var open = block.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
})();
</script>
@endif
