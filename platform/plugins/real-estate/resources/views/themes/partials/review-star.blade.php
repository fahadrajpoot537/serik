@switch($style ?? 1)
    @case(2)
            <span class="rating-star">
                <span class="rating-star-item" style="width: {{ $avgStar * 20 }}%"></span>
            </span>
            <span>{{ trans('plugins/real-estate::property.avg_out_of_5', ['avg' => number_format($avgStar, 1)]) }}</span>
        @break
    @default
        <span class="rating-star">
            <span class="rating-star-item" style="width: {{ $avgStar * 20 }}%"></span>
        </span>
        @if (isset($count))
            <span class="inline-block text-sm">({{ number_format($count) }})</span>
        @endif
        @break
@endswitch
