@if ($paginator->hasPages())
    <ul class="flat-pagination serik-flat-pagination" role="navigation" aria-label="Pagination">
        @if (! $paginator->onFirstPage())
            <li>
                <a href="{{ $paginator->previousPageUrl() }}" class="page-numbers" rel="prev" aria-label="{{ trans('pagination.previous') }}">
                    <x-core::icon name="ti ti-chevron-left" />
                </a>
            </li>
        @endif

        @if (! empty($elements))
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li>
                        <span class="page-numbers page-numbers--dots" aria-hidden="true">&hellip;</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ((int) $page === (int) $paginator->currentPage())
                                <span class="page-numbers current" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ is_string($url) ? $url : $paginator->url((int) $page) }}" class="page-numbers">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
        @endif

        @if (method_exists($paginator, 'lastPage') && ! $paginator->onLastPage())
            <li>
                <a href="{{ $paginator->url($paginator->lastPage()) }}" class="page-numbers page-numbers--last" aria-label="Last page">Last</a>
            </li>
            @if ($paginator->hasMorePages())
            <li>
                <a href="{{ $paginator->nextPageUrl() }}" class="page-numbers" rel="next" aria-label="{{ trans('pagination.next') }}">
                    <x-core::icon name="ti ti-chevron-right" />
                </a>
            </li>
            @endif
        @elseif ($paginator->hasMorePages())
            <li>
                <a href="{{ $paginator->nextPageUrl() }}" class="page-numbers" rel="next" aria-label="{{ trans('pagination.next') }}">
                    <x-core::icon name="ti ti-chevron-right" />
                </a>
            </li>
        @endif
    </ul>
@endif
