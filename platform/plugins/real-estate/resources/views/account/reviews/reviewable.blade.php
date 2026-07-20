@if ($item->reviewable)
    <a href="{{ $item->reviewable->url }}" target="_blank">
        {{ $item->reviewable->name }}
    </a>
@else
    &mdash;
@endif
