@php
    $model = $model ?? $property ?? null;
    $isIframeView = request()->boolean('iframe');
    $lat = $model ? (float) $model->latitude : 0;
    $lng = $model ? (float) $model->longitude : 0;
    $hasCoords = $model && abs($lat) > 0.00001 && abs($lng) > 0.00001 && abs($lat) <= 90 && abs($lng) <= 180;
    $showMap = theme_option('real_estate_show_map_on_single_detail_page', 'yes') === 'yes';
@endphp

<div @class(['single-property-map', $class ?? null]) id="location">
    <div class="h7 title fw-7">{{ __('Location') }}</div>

    @if ($showMap && $model)
        @if ($hasCoords && ! $isIframeView)
            {{-- Full page: interactive Leaflet map (assets loaded in property.blade.php) --}}
            <div
                data-bb-toggle="detail-map"
                id="property-detail-map"
                class="property-detail-map"
                style="min-height: 400px; width: 100%; border-radius: 12px; overflow: hidden;"
                data-tile-layer="{{ RealEstateHelper::getMapTileLayer() }}"
                data-center="{{ json_encode([$lat, $lng]) }}"
                data-map-icon="{{ $model->map_icon }}"
                data-max-zoom="{{ theme_option('map_max_zoom', '22') }}"
            ></div>
            <noscript>
                <iframe
                    class="property-detail-map-embed"
                    title="{{ __('Property location') }}"
                    width="100%"
                    style="min-height: 400px; border: 0; border-radius: 12px;"
                    src="https://maps.google.com/maps?q={{ rawurlencode($lat . ',' . $lng) }}&z=15&output=embed"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen
                ></iframe>
            </noscript>
        @elseif ($hasCoords)
            {{-- Iframe/modal: Leaflet is intentionally not loaded — use Google embed so location always shows --}}
            <iframe
                class="property-detail-map-embed"
                title="{{ __('Property location') }}"
                width="100%"
                style="min-height: 400px; border: 0; border-radius: 12px;"
                src="https://maps.google.com/maps?q={{ rawurlencode($lat . ',' . $lng) }}&z=15&output=embed"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
            ></iframe>
        @elseif ($model->location)
            <iframe
                class="property-detail-map-embed"
                title="{{ __('Property location') }}"
                width="100%"
                style="min-height: 400px; border: 0; border-radius: 12px;"
                src="https://maps.google.com/maps?q={{ urlencode($model->location) }}&t=&z=13&ie=UTF8&iwloc=&output=embed"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
            ></iframe>
        @endif
    @endif

    @if ($locationOnMap = ($model->location ?: $model->short_address))
        @php
            $mapUrl = 'https://www.google.com/maps/search/' . urlencode($locationOnMap);

            if ($hasCoords) {
                $mapUrl = 'https://maps.google.com/?q=' . $lat . ',' . $lng;
            }
        @endphp
        <ul class="info-map">
            <li>
                <div class="fw-7">{{ __('Address') }}</div>
                <a class="mt-4 text-variant-1" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">
                    {{ $locationOnMap }}
                </a>
            </li>
        </ul>
    @endif
</div>
