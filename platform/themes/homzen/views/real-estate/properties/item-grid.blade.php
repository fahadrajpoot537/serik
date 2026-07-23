@once
<style>
    .serik-prop-card {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e8eaed;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        transition: box-shadow 0.2s ease, transform 0.2s ease;
        height: 100%;
    }
    .serik-prop-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }
    .serik-prop-card .blurred-content { filter: blur(5px); pointer-events: none; user-select: none; }
    .serik-prop-card__media { position: relative; display: block; aspect-ratio: 4 / 3; overflow: hidden; background: #f3f4f6; }
    .serik-prop-card__media img { width: 100%; height: 100%; object-fit: cover; }
    .serik-prop-card__badge {
        position: absolute; left: 10px; bottom: 10px;
        background: #1a7f4b; color: #fff; font-size: 12px; font-weight: 600;
        padding: 4px 10px; border-radius: 4px; line-height: 1.2;
    }
    .serik-prop-card__badge.sold { background: #c0392b; }
    .serik-prop-card__days {
        position: absolute; right: 10px; bottom: 10px;
        background: rgba(0, 0, 0, 0.72); color: #fff; font-size: 11px; font-weight: 500;
        padding: 4px 8px; border-radius: 4px;
    }
    .serik-prop-card__body { padding: 12px 14px 14px; }
    .serik-prop-card__price-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
    .serik-prop-card__price { font-size: 1.25rem; font-weight: 700; color: #111; margin: 0; line-height: 1.2; }
    .serik-prop-card__heart { border: none; background: transparent; padding: 4px; color: #6b7280; line-height: 1; }
    .serik-prop-card__stats { font-size: 13px; color: #374151; margin-bottom: 6px; line-height: 1.4; }
    .serik-prop-card__stats span + span::before { content: '  '; }
    .serik-prop-card__address { display: block; font-size: 14px; font-weight: 600; color: #111; line-height: 1.35; text-decoration: none; margin-bottom: 4px; }
    .serik-prop-card__address:hover { color: var(--primary-color, #0255a1); }
    .serik-prop-card__mls { font-size: 11px; color: #9ca3af; line-height: 1.3; }
    .serik-prop-card__listed-date { font-size: 12px; color: #6b7280; margin-top: 6px; line-height: 1.3; font-weight: 500; }
    .property-login-overlay { position: absolute; inset: 0; background: rgba(0,0,0,.55); z-index: 9; display: flex; align-items: center; justify-content: center; padding: 16px; }
    .property-login-overlay-caption { color: #fff !important; font-size: 14px; line-height: 1.5; margin-bottom: 16px; }
    .property-login-overlay-caption a { color: #fff !important; font-weight: 600; text-decoration: underline; }
</style>
@endonce

@php
    use Theme\homzen\Supports\TrebPropertyHelper;

    $card = TrebPropertyHelper::listingCardViewModel($property);
    $canViewSold = ! $property->isSoldHistory() || auth('account')->check() || auth()->check();
    $linkUrl = $canViewSold ? $card['url'] : '#';
    $isSold = $property->isSoldHistory();
    $statusLabel = $isSold
        ? ($property->MlsStatus === 'Leased' ? __('Leased') : __('Sold'))
        : ($property->TransactionType === 'For Lease' ? __('For Lease') : __('For Sale'));
    $baths = (int) ($property->number_bathroom ?? 0);
    $sqft = trim((string) ($property->square_text ?? ''));
    $broker = trim((string) ($property->broker ?? ''));
    $mls = trim((string) ($property->external_id ?? $property->unique_id ?? ''));
@endphp

<article @class(['serik-prop-card property-item homeya-box', $class ?? null]) @if ($property->latitude && $property->longitude) data-lat="{{ $property->latitude }}" data-lng="{{ $property->longitude }}" @endif>
    @if ($isSold && ! $canViewSold)
        {!! Theme::partial('sold-property-login-gate') !!}
    @endif

    <div class="@if($isSold && ! $canViewSold) blurred-content @endif">
        <a href="{{ $linkUrl }}" @class(['serik-prop-card__media', 'js-auth-open-login' => ! $canViewSold]) @if(! $canViewSold) role="button" @endif>
            @include(Theme::getThemeNamespace('views.real-estate.partials.property-image'), [
                'property' => $property,
                'size' => 'medium-rectangle',
                'lazy' => $lazyImage ?? true,
            ])
            <span @class(['serik-prop-card__badge', 'sold' => $isSold])>{{ $statusLabel }}</span>
            @if ($card['listed_active'])
                <span class="serik-prop-card__days">{{ __('Listed') }} {{ $card['listed_active'] }}</span>
            @elseif ($card['listed_ago'] && $card['listed_ago'] !== '-')
                <span class="serik-prop-card__days">{{ $card['listed_ago'] }}</span>
            @endif
        </a>

        <div class="serik-prop-card__body">
            <div class="serik-prop-card__price-row">
                @if (! setting('real_estate_hide_price', false))
                    @if ($canViewSold)
                        <h3 class="serik-prop-card__price">{{ $property->price_format }}</h3>
                    @else
                        <h3 class="serik-prop-card__price">******</h3>
                    @endif
                @endif
                @if (RealEstateHelper::isEnabledWishlist())
                    <button type="button" class="serik-prop-card__heart" data-type="property"
                        data-bb-toggle="add-to-wishlist" data-id="{{ $property->getKey() }}"
                        data-add-message="{{ __('Added to wishlist') }}"
                        data-remove-message="{{ __('Removed from wishlist') }}"
                        aria-label="{{ __('Save property') }}">
                        <x-core::icon name="ti ti-heart" />
                    </button>
                @endif
            </div>

            <p class="serik-prop-card__stats">
                @if ($card['beds'])<span>{{ $card['beds'] }} {{ __('bed') }}</span>@endif
                @if ($baths > 0)<span>{{ $baths }} {{ __('bath') }}</span>@endif
                @if ($sqft !== '')<span>{{ $sqft }}</span>@endif
            </p>

            <a href="{{ $linkUrl }}" @class(['serik-prop-card__address line-clamp-2', 'js-auth-open-login' => ! $canViewSold])
                title="{{ $card['address'] }}">{{ $card['address'] }}@if($card['location']), {{ $card['location'] }}@endif</a>

            @if ($mls || $broker)
                <div class="serik-prop-card__mls">
                    @if ($mls)MLS® {{ $mls }}@endif
                    @if ($mls && $broker) · @endif
                    @if ($broker){{ $broker }}@endif
                </div>
            @endif

            @if ($card['listed_active'])
                <div class="serik-prop-card__listed-date">{{ __('Listed') }} {{ $card['listed_active'] }}</div>
            @endif
        </div>
    </div>
</article>
