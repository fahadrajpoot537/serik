@once
<style>
    .property-item {
        position: relative;
        overflow: hidden;
    }

    .blurred-content {
        filter: blur(5px);
        pointer-events: none;
        user-select: none;
    }

    .property-login-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.55);
        z-index: 99;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        padding: 20px;
    }

    .property-login-overlay-content {
        max-width: 420px;
    }

    .property-login-overlay-caption {
        color: #fff;
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 16px;
    }

    .property-login-overlay-caption a {
        color: #fff;
        font-weight: 600;
        text-decoration: underline;
    }

    .flat-recommended-v2 .prop-box {
        margin-bottom: 8px;
    }

    .homeya-box.hs-property-card {
        border-radius: 8px;
        margin-bottom: 0;
        box-shadow: 0 2px 10px rgba(2, 85, 161, 0.06);
        border: 1px solid #e8edf3;
    }

    .homeya-box.hs-property-card .archive-top {
        display: flex;
        flex-direction: column;
    }

    .homeya-box.hs-property-card .images-group .images-style {
        aspect-ratio: 16 / 10;
        overflow: hidden;
    }

    .homeya-box.hs-property-card .images-group .images-style img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .homeya-box.hs-property-card .content {
        min-height: auto;
        padding: 8px 10px 10px;
    }

    .hs-card-price-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 6px;
        margin-bottom: 2px;
        flex-wrap: wrap;
    }

    .hs-card-price {
        font-size: 14px;
        font-weight: 700;
        color: #111;
        margin: 0;
        line-height: 1.25;
    }

    .hs-card-price-label {
        font-weight: 600;
    }

    .hs-card-time {
        font-size: 11px;
        color: #6b7280;
        white-space: nowrap;
    }

    .hs-card-address {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #111;
        line-height: 1.3;
        margin-bottom: 2px;
        text-decoration: none;
    }

    .hs-card-address:hover {
        color: var(--primary-color, #013677);
    }

    .hs-card-type {
        font-size: 12px;
        color: #4b5563;
        margin-bottom: 6px;
    }

    .homeya-box.hs-property-card .meta-list.hs-card-meta {
        gap: 10px;
        margin: 0;
        padding: 0;
    }

    .homeya-box.hs-property-card .meta-list.hs-card-meta .item {
        font-size: 12px;
        color: #374151;
        gap: 3px;
    }

    .homeya-box.hs-property-card .meta-list.hs-card-meta .item i,
    .homeya-box.hs-property-card .meta-list.hs-card-meta .item svg {
        font-size: 13px;
        width: 13px;
        height: 13px;
        color: #6b7280;
    }

    @media (max-width: 768px) {
        .flat-recommended-v2 .prop-box {
            margin-bottom: 8px;
        }

        .homeya-box.hs-property-card .content {
            padding: 8px 10px 10px;
        }

        .hs-card-price {
            font-size: 14px;
        }

        .hs-card-address {
            font-size: 13px;
        }
    }
</style>
@endonce

@php
    use Illuminate\Support\Str;
    use Theme\homzen\Supports\TrebPropertyHelper;

    $class ??= null;
    $itemsPerRow ??= 3;

    $subtypeMap = [
        'Detached' => 'detached-houses',
        'Detached Condo' => 'detached-houses',
        'Semi-Detached' => 'semi-detached-houses',
        'Link' => 'link-houses',
        'Att/Row/Townhouse' => 'townhouses',
        'Condo Townhouse' => 'townhouses',
        'Condo Apartment' => 'condos',
        'Co-op Apartment' => 'condos',
        'Co-Ownership Apartment' => 'condos',
        'Leasehold Condo' => 'condos',
        'Common Element Condo' => 'condos',
        'Duplex' => 'duplex',
        'Fourplex' => 'fourplex',
        'Multiplex' => 'multiplex',
        'Other' => 'houses',
    ];

    $subtype = $subtypeMap[$property->PropertySubType] ?? 'houses';

    $allowedCities = [
        'Brampton', 'Mississauga', 'Vaughan', 'Milton', 'Oakville', 'NiagaraFalls', 'Toronto',
        'Kitchener', 'Waterloo', 'Cambridge', 'Hamilton', 'Ottawa', 'London', 'Markham',
        'Windsor', 'RichmondHill', 'Burlington', 'Oshawa', 'Barrie', 'Guelph', 'Kingston',
        'Whitby', 'Ajax', 'Peterborough', 'Sarnia', 'ThunderBay', 'Sudbury', 'NorthBay',
        'Orillia', 'Brantford', 'StCatharines', 'Welland', 'Pickering', 'Clarington',
        'Newmarket', 'Aurora', 'Orangeville', 'Midland', 'Collingwood', 'Timmins', 'Kenora',
        'ElliotLake', 'Brockville', 'Cornwall', 'Stratford', 'Woodstock', 'Leamington',
        'Chatham', 'Belleville', 'Pembroke',
    ];

    $slug = strtolower((string) ($property->slug ?? ''));
    $matchedCity = collect($allowedCities)
        ->sortByDesc(fn ($city) => strlen($city))
        ->first(function ($city) use ($slug) {
            $citySlug = strtolower(Str::slug($city));

            return str_contains($slug, '-' . $citySlug . '-');
        });

    $city = $matchedCity ? Str::slug($matchedCity) : 'ontario';
    $propertyUrl = url("on/{$subtype}-for-sale-in-{$city}/map/{$property->slug}");

    $cardRecord = TrebPropertyHelper::enrichRecordAddress(TrebPropertyHelper::recordFromLocal([
        'name' => $property->name,
        'MlsStatus' => $property->MlsStatus,
        'TransactionType' => $property->TransactionType,
        'PropertySubType' => $property->PropertySubType,
        'created_at' => $property->created_at,
        'ParkingSpaces' => $property->ParkingSpaces,
    ], (string) ($property->external_id ?? '')));

    $cardStreet = TrebPropertyHelper::formatDisplayAddress($cardRecord);
    $cardLocation = TrebPropertyHelper::formatLocationLine($cardRecord);

    if ($cardLocation === '' && $property->short_address) {
        $cardLocation = $property->short_address;
    }

    if ($cardLocation === '' && $property->name) {
        $parts = array_map('trim', explode(',', $property->name));
        if (count($parts) >= 2) {
            $cardLocation = trim(preg_replace('/\s+(ON|Ontario)(\s+[A-Z]\d[A-Z]\s?\d[A-Z]\d)?$/i', '', $parts[1]));
        }
    }

    $cardAddressLine = $cardStreet ?: $property->display_name;
    if ($cardLocation !== '') {
        $cardAddressLine .= ', ' . $cardLocation;
    }

    $priceLabel = $property->isSoldHistory()
        ? ($property->MlsStatus === 'Leased' ? __('Leased') : __('Sold'))
        : __('Listed');

    $listedAgo = TrebPropertyHelper::formatRelativeTime(
        $property->created_at ? (string) $property->created_at : null
    );

    $cardType = $property->PropertySubType ?: ($cardRecord['PropertySubType'] ?? '');
    $bedMain = (int) ($property->number_bedroom ?? 0);
    $bedBelow = (int) ($property->BedroomsBelowGrade ?? 0);
    $bedText = $bedMain > 0 ? $bedMain . ($bedBelow > 0 ? '+' . $bedBelow : '') : '';
    $bathCount = (int) ($property->number_bathroom ?? 0);
    $garageCount = (int) ($property->ParkingSpaces ?? 0);

    $canViewSold = ! $property->isSoldHistory() || auth('account')->check();
    $linkUrl = $canViewSold ? $propertyUrl : '#';
@endphp

<div @class(['property-item homeya-box hs-property-card position-relative', $class]) @if ($property->latitude && $property->longitude) data-lat="{{ $property->latitude }}" data-lng="{{ $property->longitude }}" @endif>
    @if ($property->isSoldHistory() && ! auth('account')->check())
        {!! Theme::partial('sold-property-login-gate') !!}
    @endif

    <div class="@if($property->isSoldHistory() && !auth('account')->check()) blurred-content @endif">
        <div class="archive-top">
            <a href="{{ $linkUrl }}"
                @class(['images-group', 'js-auth-open-login' => ! $canViewSold])
                @if(! $canViewSold) role="button" @endif>
                <div class="images-style">
                    @include(Theme::getThemeNamespace('views.real-estate.partials.property-image'), [
                        'property' => $property,
                        'size' => 'medium-rectangle',
                    ])
                </div>
                <div class="top">
                    <div class="d-flex gap-8">
                        @if($property->is_featured)
                            <span class="flag-tag success">{{ __('Featured') }}</span>
                        @endif
                        {!! BaseHelper::clean($property->status_html) !!}
                    </div>
                    @if (RealEstateHelper::isEnabledWishlist())
                        <div class="d-flex gap-4">
                            <button type="button" class="box-icon w-32" data-type="property"
                                data-bb-toggle="add-to-wishlist" data-id="{{ $property->getKey() }}"
                                data-add-message="{{ __('Added ":name" to wishlist successfully!', ['name' => $property->name]) }}"
                                data-remove-message="{{ __('Removed ":name" from wishlist successfully!', ['name' => $property->name]) }}">
                                <x-core::icon name="ti ti-heart" />
                            </button>
                        </div>
                    @endif
                </div>
            </a>

            <div class="content">
                <div class="hs-card-price-row">
                    @if (! setting('real_estate_hide_price', false))
                        @if ($canViewSold)
                            <h6 class="hs-card-price">
                                <span class="hs-card-price-label">{{ $priceLabel }}:</span>
                                {{ $property->price_format }}
                            </h6>
                        @else
                            <h6 class="hs-card-price blur-text">******</h6>
                        @endif
                    @endif
                    @if ($listedAgo && $listedAgo !== '-')
                        <span class="hs-card-time">{{ $listedAgo }}</span>
                    @endif
                </div>

                <a href="{{ $linkUrl }}" @class(['hs-card-address line-clamp-2', 'js-auth-open-login' => ! $canViewSold])
                    title="{{ $cardAddressLine }}">{{ $cardAddressLine }}</a>

                @if ($cardType)
                    <div class="hs-card-type">{{ $cardType }}</div>
                @endif

                <ul class="meta-list hs-card-meta">
                    @if ($bedText)
                        <li class="item">
                            <i class="icon icon-bed"></i>
                            <span>{{ $bedText }}</span>
                        </li>
                    @endif
                    @if ($bathCount > 0)
                        <li class="item">
                            <i class="icon icon-bathtub"></i>
                            <span>{{ $bathCount }}</span>
                        </li>
                    @endif
                    @if ($garageCount > 0)
                        <li class="item">
                            <i class="icon icon-garage"></i>
                            <span>{{ $garageCount }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
