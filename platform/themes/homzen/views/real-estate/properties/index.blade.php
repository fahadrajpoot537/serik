@php
    $itemLayout ??= request()->input('layout', 'grid');
    $itemLayout = in_array($itemLayout, ['grid', 'list']) ? $itemLayout : 'grid';
    $layout ??= get_property_listing_page_layout();

    if (! isset($itemsPerRow)) {
        $itemsPerRow = $itemLayout === 'grid' ? 3 : 2;
        if (! in_array($layout, ['top-map', 'without-map'])) {
            $itemsPerRow = $itemLayout === 'grid' ? 2 : 1;
        }
    }
@endphp

@if ($properties->isNotEmpty())
    @include(Theme::getThemeNamespace("views.real-estate.properties.$itemLayout"), compact('itemsPerRow'))
@else
    <div class="alert alert-warning" role="alert">
        {{ __('No properties found.') }}
    </div>
@endif

@if ($properties instanceof \Illuminate\Pagination\LengthAwarePaginator && $properties->hasPages())
    <p class="text-center text-muted small mb-3 serik-pagination-meta">
        {{ __('Page :current of :last (:total results)', [
            'current' => $properties->currentPage(),
            'last' => $properties->lastPage(),
            'total' => number_format($properties->total()),
        ]) }}
    </p>
    <div class="justify-content-center wd-navigation mt-2">
        {{ $properties->withQueryString()->links(Theme::getThemeNamespace('partials.pagination')) }}
    </div>
@elseif ($properties instanceof \Illuminate\Contracts\Pagination\Paginator && $properties->hasMorePages())
    <div class="justify-content-center wd-navigation mt-5">
        {{ $properties->withQueryString()->links(Theme::getThemeNamespace('partials.pagination')) }}
    </div>
@endif
