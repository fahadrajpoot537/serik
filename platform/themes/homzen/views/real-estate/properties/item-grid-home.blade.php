{{-- Homepage portal card: fixed media ratio + equal body height --}}
@php
    use Theme\homzen\Supports\TrebPropertyHelper;

    $card = TrebPropertyHelper::listingCardViewModel($property);
    $canViewSold = ! $property->isSoldHistory() || auth('account')->check() || auth()->check();
    $linkUrl = $canViewSold ? $card['url'] : '#';
    $isSold = $property->isSoldHistory();
    $mlsStatus = trim((string) ($property->MlsStatus ?? ''));
    $transactionType = trim((string) ($property->TransactionType ?? ''));

    // Display-only status badge from existing MlsStatus / TransactionType (no hardcoded values).
    $badgeVariant = match (true) {
        $mlsStatus === 'Terminated' => 'terminated',
        $mlsStatus === 'Expired' => 'expired',
        $mlsStatus === 'Suspended' => 'suspended',
        in_array($mlsStatus, ['Leased', 'Leased Conditional'], true) => 'leased',
        in_array($mlsStatus, ['Sold', 'Sold Conditional', 'Sold Conditional Escape'], true) => 'sold',
        in_array($transactionType, ['For Lease', 'For Sub-Lease'], true) => 'for-lease',
        default => 'for-sale',
    };
    $statusLabel = match ($badgeVariant) {
        'terminated' => __('Terminated'),
        'expired' => __('Expired'),
        'suspended' => __('Suspended'),
        'leased' => __('Leased'),
        'sold' => __('Sold'),
        'for-lease' => __('For Lease'),
        default => __('For Sale'),
    };

    $baths = (int) ($property->number_bathroom ?? 0);
    $sqft = trim((string) ($property->square_text ?? ''));
    $broker = trim((string) ($property->broker ?? ''));
    $mls = trim((string) ($property->external_id ?? $property->unique_id ?? ''));
@endphp

<article @class(['serik-prop-card', 'serik-portal-card', 'property-item', 'homeya-box', $class ?? null]) @if ($property->latitude && $property->longitude) data-lat="{{ $property->latitude }}" data-lng="{{ $property->longitude }}" @endif>
    @if ($isSold && ! $canViewSold)
        {!! Theme::partial('sold-property-login-gate') !!}
    @endif

    <div class="@if($isSold && ! $canViewSold) blurred-content @endif serik-portal-card__inner">
        <a href="{{ $linkUrl }}" @class(['serik-prop-card__media', 'serik-portal-card__media', 'js-property-modal-link' => $canViewSold, 'js-auth-open-login' => ! $canViewSold]) @if(! $canViewSold) role="button" @endif>
            @include(Theme::getThemeNamespace('views.real-estate.partials.property-image'), [
                'property' => $property,
                'size' => 'medium-rectangle',
                'lazy' => $lazyImage ?? true,
            ])
            <span @class([
                'serik-prop-card__badge',
                'serik-portal-card__badge',
                'serik-portal-card__badge--' . $badgeVariant,
                'sold' => $badgeVariant === 'sold' || $badgeVariant === 'leased',
            ])>{{ $statusLabel }}</span>
            @if ($card['listed_active'])
                <span class="serik-prop-card__days serik-portal-card__days">{{ __('Listed') }} {{ $card['listed_active'] }}</span>
            @elseif ($card['listed_ago'] && $card['listed_ago'] !== '-')
                <span class="serik-prop-card__days serik-portal-card__days">{{ $card['listed_ago'] }}</span>
            @endif
        </a>

        <div class="serik-prop-card__body serik-portal-card__body">
            @if (! setting('real_estate_hide_price', false))
                <h3 class="serik-prop-card__price serik-portal-card__price">
                    {{ $canViewSold ? $property->price_format : '******' }}
                </h3>
            @endif

            <a href="{{ $linkUrl }}" @class(['serik-prop-card__address', 'serik-portal-card__address', 'js-property-modal-link' => $canViewSold, 'js-auth-open-login' => ! $canViewSold])
                title="{{ $card['address'] }}">
                <x-core::icon name="ti ti-map-pin" class="serik-portal-card__pin" />
                <span class="serik-portal-card__address-text">
                    {{ $card['address'] }}
                    @if ($card['location'])
                        <span class="serik-portal-card__address-sub">{{ $card['location'] }}</span>
                    @endif
                </span>
            </a>

            <div class="serik-prop-card__stats serik-portal-card__stats">
                @if ($card['beds'])
                    <span class="serik-portal-card__stat">
                        <span class="serik-portal-card__stat-icon"><x-core::icon name="ti ti-bed" /></span>
                        <span>
                            <span class="serik-portal-card__stat-value">{{ $card['beds'] }}</span>
                            <span class="serik-portal-card__stat-label">{{ __('bed') }}</span>
                        </span>
                    </span>
                @endif
                @if ($baths > 0)
                    <span class="serik-portal-card__stat">
                        <span class="serik-portal-card__stat-icon"><x-core::icon name="ti ti-bath" /></span>
                        <span>
                            <span class="serik-portal-card__stat-value">{{ $baths }}</span>
                            <span class="serik-portal-card__stat-label">{{ __('bath') }}</span>
                        </span>
                    </span>
                @endif
                @if ($sqft !== '')
                    <span class="serik-portal-card__stat">
                        <span class="serik-portal-card__stat-icon"><x-core::icon name="ti ti-ruler-measure" /></span>
                        <span>
                            <span class="serik-portal-card__stat-value">{{ $sqft }}</span>
                            <span class="serik-portal-card__stat-label">{{ __('area') }}</span>
                        </span>
                    </span>
                @endif
            </div>

            @if ($mls || $broker)
                <div class="serik-prop-card__mls serik-portal-card__meta line-clamp-1">
                    @if ($mls)MLS® {{ $mls }}@endif
                    @if ($mls && $broker) · @endif
                    @if ($broker){{ $broker }}@endif
                </div>
            @endif

            <div class="serik-portal-card__cta-row">
                <a href="{{ $linkUrl }}" @class(['serik-portal-card__view', 'js-property-modal-link' => $canViewSold, 'js-auth-open-login' => ! $canViewSold])
                    @if(! $canViewSold) role="button" @endif>{{ __('View Details') }}</a>

                @if (RealEstateHelper::isEnabledWishlist())
                    <button type="button" class="serik-prop-card__heart serik-portal-card__heart" data-type="property"
                        data-bb-toggle="add-to-wishlist" data-id="{{ $property->getKey() }}"
                        data-add-message="{{ __('Added to wishlist') }}"
                        data-remove-message="{{ __('Removed from wishlist') }}"
                        aria-label="{{ __('Save property') }}">
                        <x-core::icon name="ti ti-heart" />
                    </button>
                @endif
            </div>
        </div>
    </div>
</article>
