@php
    use Theme\homzen\Supports\TrebPropertyHelper;

    $model = $model ?? $property ?? null;
    $listingKey = $model->external_id ?? '';
    $isLocked = $model->isSoldHistory() && !(auth('account')->check() || auth()->check());

    $localData = [
        'name' => $model->name,
        'price' => $model->price,
        'square' => $model->square,
        'MlsStatus' => $model->MlsStatus,
        'TransactionType' => $model->TransactionType,
        'PropertySubType' => $model->PropertySubType,
        'broker' => $model->broker,
        'external_id' => $model->external_id,
        'created_at' => $model->created_at,
        'updated_at' => $model->updated_at,
        'listing_contract_date' => $model->listing_contract_date,
        'listing_modified_at' => $model->listing_modified_at,
        'ParkingSpaces' => $model->ParkingSpaces,
        'CoveredSpaces' => $model->CoveredSpaces,
        'number_bedroom' => $model->number_bedroom,
        'number_bathroom' => $model->number_bathroom,
        'BedroomsBelowGrade' => $model->BedroomsBelowGrade,
        'number_floor' => $model->number_floor,
        'Basement' => $model->Basement,
        'content' => $model->content,
    ];

    $isAuthenticated = auth('account')->check() || auth()->check();

    $factRecord = $listingKey
        ? TrebPropertyHelper::resolveFactRecordForDetail($listingKey, $localData)
        : [];

    $displayName = TrebPropertyHelper::formatDisplayAddress($factRecord);
    $displayLocation = TrebPropertyHelper::formatLocationLine($factRecord);
    $displayType = $factRecord['PropertySubType'] ?? $model->PropertySubType ?? '';
    // Always fetch history so sold/leased guests see count + locked rows (not Listing History (0)).
    $listingHistory = $listingKey
        ? TrebPropertyHelper::fetchListingHistoryForDetail($listingKey, $localData, $factRecord)
        : [];
    $priceChanges = (! $isLocked && $listingKey) ? TrebPropertyHelper::fetchPriceChanges($listingKey) : [];
    $keyFacts = TrebPropertyHelper::buildKeyFacts($factRecord, $localData);
    $propertyDetails = TrebPropertyHelper::buildPropertyDetails($factRecord, $localData);
    $rooms = (! $isLocked && $listingKey) ? TrebPropertyHelper::fetchPropertyRoomsForDetail($listingKey) : [];
    $addedLabel = $factRecord['ListingContractDate'] ?? $factRecord['OriginalEntryTimestamp'] ?? $model->created_at ?? null;

    $bedroomsLabel = TrebPropertyHelper::formatBedroomLabel($factRecord, $localData);
    $bathrooms = $factRecord['BathroomsTotalInteger'] ?? $localData['number_bathroom'] ?? null;
    $garage = $factRecord['CoveredSpaces'] ?? $localData['CoveredSpaces'] ?? null;
    $hsShow = [TrebPropertyHelper::class, 'hasDisplayValue'];
    $hsDetailShow = [TrebPropertyHelper::class, 'hasDetailFieldValue'];
    $keyFactFields = [
        'tax' => __('Tax'),
        'property_type' => __('Property Type'),
        'building_age' => __('Building Age'),
        'size' => __('Size'),
        'lot_size' => __('Lot Size'),
        'parking' => __('Parking'),
        'basement' => __('Basement'),
        'listing_number' => __('Listing #'),
        'data_source' => __('Data Source'),
        'brokerage' => __('Listing Brokerage'),
        'days_on_market' => __('Days on Market'),
        'property_days_on_market' => __('Property Days on Market'),
        'status_change' => __('Status Change'),
        'listed_on' => __('Listed on'),
        'updated_on' => __('Updated on'),
    ];
    $detailGroups = [
        __('Property') => [
            'property_type' => __('Property Type'),
            'style' => __('Style'),
            'fronting_on' => __('Fronting on'),
            'community' => __('Community'),
            'municipality' => __('Municipality'),
        ],
        __('Inside') => [
            'bedrooms' => __('Bedrooms'),
            'bathrooms' => __('Bathrooms'),
            'basement_type' => __('Basement Type'),
            'kitchens' => __('Kitchens'),
            'rooms' => __('Rooms'),
            'family_room' => __('Family Room'),
            'fireplace' => __('Fireplace'),
        ],
        __('Utilities') => [
            'water' => __('Water'),
            'cooling' => __('Cooling'),
            'heating_type' => __('Heating Type'),
            'heating_fuel' => __('Heating Fuel'),
        ],
        __('Building') => [
            'size' => __('Size'),
            'structures' => __('Structures'),
            'construction' => __('Construction'),
        ],
        __('Parking') => [
            'driveway' => __('Driveway'),
            'garage_type' => __('Garage Type'),
            'garage' => __('Garage'),
            'parking_places' => __('Parking Places'),
            'parking_total' => __('Total Parking Space'),
        ],
        __('Highlights') => [
            'property_features' => __('Property Features'),
            'pets_allowed' => __('Pets Allowed'),
        ],
        __('Land') => [
            'sewer' => __('Sewer'),
            'frontage' => __('Frontage'),
            'depth' => __('Depth'),
            'lot_size' => __('Lot Size'),
            'lot_size_code' => __('Lot Size Code'),
            'cross_street' => __('Cross Street'),
        ],
    ];
@endphp

<style>
.hs-detail-section { margin-top: 24px; }
.hs-detail-section .section-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--main-header-text-color, #161e2d);
}
.hs-detail-section .section-subtitle {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 16px;
}
.hs-tabs-scroll {
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    margin-bottom: 0;
}
.hs-tabs-scroll::-webkit-scrollbar { display: none; }
.hs-tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 0;
    flex-wrap: nowrap;
    width: max-content;
    min-width: 100%;
}
.hs-tabs button {
    flex: 0 0 auto;
    border: none;
    background: transparent;
    padding: 12px 16px;
    font-weight: 600;
    font-size: 15px;
    color: #64748b;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    white-space: nowrap;
    line-height: 1.2;
}
.hs-tabs button .hs-tab-text {
    display: inline-block;
    white-space: nowrap;
}
.hs-tabs button.active {
    color: rgb(2, 85, 161);
    border-bottom-color: rgb(2, 85, 161);
    background: rgba(2, 85, 161, 0.06);
}
.hs-tab-panel { display: none; padding: 16px 0; }
.hs-tab-panel.active { display: block; }
.hs-key-facts, .hs-details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px 24px;
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
}
.hs-key-facts .fact-label,
.hs-details-grid .fact-label { font-size: 13px; color: #64748b; display: block; }
.hs-key-facts .fact-value,
.hs-details-grid .fact-value { font-size: 15px; font-weight: 600; color: #1e293b; }
.hs-stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    padding: 12px 0 20px;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 8px;
}
.hs-stats-row .stat { font-size: 15px; color: #334155; }
.hs-stats-row .stat strong { font-weight: 700; }
.hs-history-locked-row { cursor: pointer; }
.hs-history-locked-row:hover td { background: rgba(2, 85, 161, 0.06); }
.hs-history-locked-row .hs-sign-in-link { color: rgb(2, 85, 161); text-decoration: underline; }
.hs-details-group-title {
    grid-column: 1 / -1;
    font-size: 14px;
    font-weight: 700;
    color: rgb(2, 85, 161);
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #e2e8f0;
}
.hs-details-group-title:first-child {
    margin-top: 0;
    padding-top: 0;
    border-top: none;
}
@media (max-width: 768px) {
    .hs-detail-section {
        margin-top: 16px;
        padding: 0 4px;
        overflow: visible;
    }
    .hs-detail-section .section-title {
        font-size: 16px;
        margin-bottom: 6px;
    }
    .hs-detail-section .section-subtitle {
        font-size: 13px;
        margin-bottom: 12px;
        line-height: 1.45;
    }
    .hs-stats-row {
        gap: 12px 16px;
        padding: 10px 0 14px;
    }
    .hs-stats-row .stat {
        font-size: 13px;
    }
    .hs-tabs button {
        padding: 10px 12px;
        font-size: 13px;
    }
    .hs-tab-panel {
        padding: 12px 0;
    }
    .hs-key-facts, .hs-details-grid {
        grid-template-columns: 1fr;
        padding: 14px;
        gap: 10px 16px;
    }
    .hs-key-facts .fact-value,
    .hs-details-grid .fact-value {
        font-size: 14px;
    }
    #propertyDetailBlocks .table-responsive {
        margin: 0 -4px;
    }
    #propertyDetailBlocks .table {
        font-size: 12px;
    }
    #propertyDetailBlocks .table th,
    #propertyDetailBlocks .table td {
        padding: 8px 6px;
        vertical-align: top;
    }
}
</style>

<div class="hs-detail-section" id="propertyDetailBlocks" @if($listingKey) data-listing-key="{{ $listingKey }}" data-history-auth="{{ $isAuthenticated ? '1' : '0' }}" @endif>
    <div class="section-title">{{ __('Listing History') }}</div>
    <p class="section-subtitle" id="historySubtitle">
        {{ __('Buy/sell history for') }} {{ $displayName }}
        @if($displayType) ({{ $displayType }}) @endif
    </p>

    <div class="hs-tabs-scroll">
        <div class="hs-tabs" role="tablist">
            <button type="button" class="hs-tab-btn active" data-tab="hs-history"><span class="hs-tab-text">{{ __('Listing History') }} ({{ count($listingHistory) }})</span></button>
            <button type="button" class="hs-tab-btn" data-tab="hs-price-change"><span class="hs-tab-text">{{ __('Price Changes') }} ({{ count($priceChanges) }})</span></button>
            <button type="button" class="hs-tab-btn" data-tab="hs-key-facts"><span class="hs-tab-text">{{ __('Key Facts') }}</span></button>
            <button type="button" class="hs-tab-btn" data-tab="hs-details"><span class="hs-tab-text">{{ __('Details') }}</span></button>
            <button type="button" class="hs-tab-btn" data-tab="hs-rooms"><span class="hs-tab-text">{{ __('Rooms') }} ({{ count($rooms) }})</span></button>
        </div>
    </div>

    @if ($isLocked)
        <div class="text-center py-3">
            <div style="padding: 18px; background: rgba(0,0,0,0.02); border: 1px dashed rgba(0,0,0,0.15); border-radius: 8px;">
                <span style="font-weight: 700; color: #e63946; display: block; margin-bottom: 5px;">🔒 {{ __('Complete Account') }}</span>
                <p style="margin: 0; font-size: 14px; color: #4a5568;">{{ __('Real estate boards need a verified account to see listing history & sold data.') }}</p>
                <a href="#modalLogin" data-bs-toggle="modal" class="btn btn-primary btn-sm mt-3" style="background: rgb(2, 85, 161); border: none;">{{ __('Log in to view') }}</a>
            </div>
        </div>
    @endif

    <div class="hs-tab-panel active" id="hs-history">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>{{ __('Date Start') }}</th>
                        <th>{{ __('Date End') }}</th>
                        <th>{{ __('Price') }}</th>
                        <th>{{ __('Event') }}</th>
                        <th>{{ __('Listing ID') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($listingHistory as $row)
                        <tr
                            @if(!empty($row['locked']))
                                class="text-muted hs-history-locked-row"
                                role="button"
                                tabindex="0"
                                data-bs-toggle="modal"
                                data-bs-target="#modalLogin"
                                title="{{ __('Sign in to view this listing history') }}"
                            @endif
                        >
                            <td>{{ $row['date_start'] ?? '-' }}</td>
                            <td>{{ $row['date_end'] ?? '-' }}</td>
                            <td>
                                @if(!empty($row['locked']))
                                    -
                                @elseif(isset($row['price']))
                                    ${{ number_format((float) $row['price']) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if(!empty($row['locked']))
                                    <span class="hs-sign-in-link">{{ __('(Sign in required)') }}</span>
                                    {{ preg_replace('/^\(Sign in required\)\s*/', '', $row['event'] ?? '') }}
                                @else
                                    {{ $row['event'] ?? '-' }}
                                @endif
                            </td>
                            <td>
                                @if(!empty($row['locked']))
                                    *********
                                @else
                                    {{ $row['listing_id'] ?? '-' }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">{{ __('No history found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (! $isAuthenticated)
        <p class="text-muted" style="font-size:14px;margin-top:12px;">
            {{ __('Sign in to view expired, terminated, and sold listing history for this property.') }}
            <a href="#modalLogin" data-bs-toggle="modal" class="hs-sign-in-link">{{ __('Log in') }}</a>
        </p>
    @endif

    <div class="hs-tab-panel" id="hs-price-change">
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Old Price') }}</th>
                        <th>{{ __('New Price') }}</th>
                        <th>{{ __('Event') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($priceChanges as $row)
                        <tr>
                            <td>{{ $row['date'] ?? '-' }}</td>
                            <td>@if(isset($row['old_price'])) ${{ number_format((float) $row['old_price']) }} @else - @endif</td>
                            <td>@if(isset($row['new_price'])) ${{ number_format((float) $row['new_price']) }} @else - @endif</td>
                            <td>{{ $row['event'] ?? __('Price Change') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">{{ __('No price changes recorded') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="hs-tab-panel" id="hs-key-facts">
        <p class="section-subtitle">
            {{ __('Key facts for') }} {{ $displayName }}@if($displayLocation), {{ $displayLocation }}@endif
        </p>
        <div class="hs-key-facts">
            @foreach ($keyFactFields as $key => $label)
                @if ($hsShow($keyFacts[$key] ?? null))
                    <div><span class="fact-label">{{ $label }}</span><span class="fact-value">{{ $keyFacts[$key] }}</span></div>
                @endif
            @endforeach
        </div>
    </div>

    <div class="hs-tab-panel" id="hs-details">
        @if (!empty($propertyDetails['listed_line']))
            <p class="section-subtitle">{{ $propertyDetails['listed_line'] }}</p>
        @endif
        <div class="hs-details-grid">
            @foreach ($detailGroups as $groupTitle => $fields)
                @php
                    $visibleFields = [];
                    foreach ($fields as $fieldKey => $fieldLabel) {
                        if ($hsDetailShow($propertyDetails[$fieldKey] ?? null)) {
                            $visibleFields[$fieldKey] = $fieldLabel;
                        }
                    }
                    if ($groupTitle === __('Inside') && !empty($propertyDetails['bathrooms_details'])) {
                        foreach ($propertyDetails['bathrooms_details'] as $detailLine) {
                            $visibleFields['bath_detail_' . md5($detailLine)] = __('Bathrooms Detail');
                        }
                    }
                @endphp
                @if ($visibleFields !== [])
                    <div class="hs-details-group-title">{{ $groupTitle }}</div>
                    @foreach ($fields as $fieldKey => $fieldLabel)
                        @if ($hsDetailShow($propertyDetails[$fieldKey] ?? null))
                            <div><span class="fact-label">{{ $fieldLabel }}</span><span class="fact-value">{{ $propertyDetails[$fieldKey] }}</span></div>
                        @endif
                    @endforeach
                    @if ($groupTitle === __('Inside') && !empty($propertyDetails['bathrooms_details']))
                        @foreach ($propertyDetails['bathrooms_details'] as $detailLine)
                            <div><span class="fact-label">{{ __('Bathrooms Detail') }}</span><span class="fact-value">{{ $detailLine }}</span></div>
                        @endforeach
                    @endif
                @endif
            @endforeach
        </div>
    </div>

    <div class="hs-tab-panel" id="hs-rooms">
        @if ($rooms === [])
            <p class="text-muted">{{ __('Room details are not available for this listing.') }}</p>
        @else
            <p class="section-subtitle">
                {{ __('Room details for') }} {{ $displayName }}@if($displayLocation), {{ explode(' - ', $displayLocation)[0] }}@endif.
                @if(!empty($propertyDetails['listed_line']))
                    {{ $propertyDetails['listed_line'] }}
                @endif
            </p>
            <div class="hs-room-list">
                @foreach ($rooms as $room)
                    <div class="hs-room-item" style="margin-bottom:16px;">
                        <div style="font-weight:700;color:#1e293b;">{{ $room['name'] }}</div>
                        @if($hsShow($room['level'] ?? null))
                            <div style="font-size:14px;color:#64748b;">{{ __('Level') }}: {{ $room['level'] }}</div>
                        @endif
                        @if($hsShow($room['features'] ?? null))
                            <div style="font-size:14px;color:#334155;">{{ $room['features'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabsScroll = document.querySelector('#propertyDetailBlocks .hs-tabs-scroll');

    document.querySelectorAll('.hs-tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.hs-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.hs-tab-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const tab = document.getElementById(this.dataset.tab);
            if (tab) tab.classList.add('active');

            if (tabsScroll) {
                const btnRect = this.getBoundingClientRect();
                const scrollRect = tabsScroll.getBoundingClientRect();
                if (btnRect.left < scrollRect.left || btnRect.right > scrollRect.right) {
                    this.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                }
            }
        });
    });

    document.querySelectorAll('.hs-history-locked-row').forEach((row) => {
        row.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                const loginModal = document.getElementById('modalLogin');
                if (loginModal && window.bootstrap?.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(loginModal).show();
                }
            }
        });
    });

    // Logged-in: refresh history via API (AMP unit sync) — HTML pages cannot call AMP directly.
    const blocks = document.getElementById('propertyDetailBlocks');
    const listingKey = blocks?.dataset?.listingKey;
    const canRefreshHistory = blocks?.dataset?.historyAuth === '1';
    if (listingKey && canRefreshHistory) {
        fetch('/api/v1/listing-history/' + encodeURIComponent(listingKey), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then((res) => res.ok ? res.json() : null)
        .then((payload) => {
            const rows = Array.isArray(payload?.data) ? payload.data : [];
            if (rows.length === 0) {
                return;
            }

            const tbody = document.querySelector('#hs-history tbody');
            const tabLabel = document.querySelector('[data-tab="hs-history"] .hs-tab-text');
            if (!tbody) {
                return;
            }

            tbody.innerHTML = rows.map((row) => {
                const locked = !!row.locked;
                const price = (!locked && row.price != null) ? ('$' + Number(row.price).toLocaleString()) : '-';
                const eventLabel = locked
                    ? ('<span class="hs-sign-in-link">(Sign in required)</span> ' + String(row.event || '').replace(/^\(Sign in required\)\s*/, ''))
                    : (row.event || '-');
                const listingId = locked ? '*********' : (row.listing_id || '-');
                const trClass = locked ? ' class="text-muted hs-history-locked-row"' : '';
                return '<tr' + trClass + '>'
                    + '<td>' + (row.date_start || '-') + '</td>'
                    + '<td>' + (row.date_end || '-') + '</td>'
                    + '<td>' + price + '</td>'
                    + '<td>' + eventLabel + '</td>'
                    + '<td>' + listingId + '</td>'
                    + '</tr>';
            }).join('');

            if (tabLabel) {
                tabLabel.textContent = 'Listing History (' + rows.length + ')';
            }
        })
        .catch(() => {});
    }
});
</script>
