<p><strong><a
            href="{{ route('package.edit', $package->id) }}"
            target="_blank"
        >{{ $package->name }}</a></strong></p>

@if ((float) $package->price)
    <p><strong>{{ format_price($package->price / $package->number_of_listings, $package->currency) }}</strong> /
        {{ trans('plugins/real-estate::general.per_post') }}</p>
    <p><strong>{{ format_price($package->price, $package->currency) }}</strong> {{ trans('plugins/real-estate::general.total') }} ({{ trans('plugins/real-estate::general.save') }}
        {{ $package->percent_save }}%)</p>
@else
    <p><strong>{{ trans('plugins/real-estate::general.free') }}</strong> {{ $package->number_of_listings }} {{ trans('plugins/real-estate::general.posts') }}</p>
@endif
