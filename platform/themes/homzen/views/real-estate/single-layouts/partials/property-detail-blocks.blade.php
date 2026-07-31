@php
    $detail = $propertyDetailBlocks ?? [];
    $listingKey = (string) ($detail['listingKey'] ?? '');
    $isAuthenticated = (bool) ($detail['isAuthenticated'] ?? false);
    $isLocked = (bool) ($detail['isLocked'] ?? false);
    $displayName = (string) ($detail['displayName'] ?? '');
    $displayLocation = (string) ($detail['displayLocation'] ?? '');
    $displayType = (string) ($detail['displayType'] ?? '');
    $listingHistory = (array) ($detail['listingHistory'] ?? []);
    $priceChanges = (array) ($detail['priceChanges'] ?? []);
    $keyFactRows = (array) ($detail['keyFactRows'] ?? []);
    $detailGroupRows = (array) ($detail['detailGroupRows'] ?? []);
    $rooms = (array) ($detail['rooms'] ?? []);
    $listedLine = $detail['listedLine'] ?? null;
@endphp

<style>
.hs-detail-section { margin-top: 16px; }
.hs-detail-section .section-title {
    font-size: 17px;
    font-weight: 700;
    margin-bottom: 4px;
    color: var(--main-header-text-color, #161e2d);
}
.hs-detail-section .section-subtitle {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 10px;
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
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 0;
    flex-wrap: nowrap;
    width: max-content;
    min-width: 100%;
}
.hs-tabs button {
    flex: 0 0 auto;
    border: none;
    background: transparent;
    padding: 8px 12px;
    font-weight: 600;
    font-size: 14px;
    color: #64748b;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
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
    background: rgba(2, 85, 161, 0.05);
}
.hs-tab-panel { display: none; padding: 10px 0 4px; }
.hs-tab-panel.active { display: block; }
.hs-key-facts, .hs-details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 6px 20px;
    background: #f8fafc;
    border-radius: 8px;
    padding: 12px 14px;
}
.hs-key-facts .fact-label,
.hs-details-grid .fact-label {
    font-size: 12px;
    color: #64748b;
    display: block;
    line-height: 1.25;
    margin-bottom: 1px;
}
.hs-key-facts .fact-value,
.hs-details-grid .fact-value {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.3;
}
.hs-stats-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12px 16px;
    padding: 8px 0 12px;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 6px;
}
.hs-stats-row .stat { font-size: 14px; color: #334155; }
.hs-stats-row .stat strong { font-weight: 700; }
.hs-history-locked-row { cursor: pointer; }
.hs-history-locked-row:hover td { background: rgba(2, 85, 161, 0.06); }
.hs-history-locked-row .hs-sign-in-link { color: rgb(2, 85, 161); text-decoration: underline; }
.hs-room-list .hs-room-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #e2e8f0;
}
.hs-room-list .hs-room-row:last-child { border-bottom: none; }
.hs-room-left { flex: 1; min-width: 0; }
.hs-room-name { font-weight: 700; color: #1e293b; font-size: 14px; }
.hs-room-size { font-size: 13px; color: #64748b; margin-top: 1px; }
.hs-room-level {
    font-size: 13px;
    color: #64748b;
    text-align: right;
    white-space: nowrap;
    flex-shrink: 0;
    padding-top: 1px;
}
.hs-details-group-title {
    grid-column: 1 / -1;
    font-size: 13px;
    font-weight: 700;
    color: rgb(2, 85, 161);
    margin-top: 4px;
    padding-top: 6px;
    border-top: 1px solid #e2e8f0;
}
.hs-details-group-title:first-child {
    margin-top: 0;
    padding-top: 0;
    border-top: none;
}
#propertyDetailBlocks .table { margin-bottom: 0; }
#propertyDetailBlocks .table th,
#propertyDetailBlocks .table td {
    padding: 6px 8px;
    vertical-align: top;
}
@media (max-width: 768px) {
    .hs-detail-section {
        margin-top: 12px;
        padding: 0 2px;
        overflow: visible;
    }
    .hs-detail-section .section-title {
        font-size: 15px;
        margin-bottom: 4px;
    }
    .hs-detail-section .section-subtitle {
        font-size: 12px;
        margin-bottom: 8px;
        line-height: 1.4;
    }
    .hs-stats-row {
        gap: 8px 12px;
        padding: 6px 0 10px;
    }
    .hs-stats-row .stat { font-size: 13px; }
    .hs-tabs button {
        padding: 8px 10px;
        font-size: 13px;
    }
    .hs-tab-panel { padding: 8px 0 2px; }
    .hs-key-facts, .hs-details-grid {
        grid-template-columns: 1fr;
        padding: 10px 12px;
        gap: 6px 12px;
    }
    .hs-key-facts .fact-value,
    .hs-details-grid .fact-value { font-size: 13px; }
    #propertyDetailBlocks .table-responsive { margin: 0 -2px; }
    #propertyDetailBlocks .table { font-size: 12px; }
    #propertyDetailBlocks .table th,
    #propertyDetailBlocks .table td { padding: 6px 5px; }
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
            @foreach ($keyFactRows as $row)
                <div><span class="fact-label">{{ $row['label'] }}</span><span class="fact-value">{{ $row['value'] }}</span></div>
            @endforeach
        </div>
    </div>

    <div class="hs-tab-panel" id="hs-details">
        @if (!empty($listedLine))
            <p class="section-subtitle">{{ $listedLine }}</p>
        @endif
        <div class="hs-details-grid">
            @foreach ($detailGroupRows as $group)
                <div class="hs-details-group-title">{{ $group['title'] }}</div>
                @foreach ($group['rows'] as $row)
                    <div><span class="fact-label">{{ $row['label'] }}</span><span class="fact-value">{{ $row['value'] }}</span></div>
                @endforeach
            @endforeach
        </div>
    </div>

    <div class="hs-tab-panel" id="hs-rooms">
        @if ($rooms === [])
            <p class="text-muted">{{ __('Room details are not available for this listing.') }}</p>
        @else
            <p class="section-subtitle">
                {{ __('Room details for') }} {{ $displayName }}@if($displayLocation), {{ explode(' - ', $displayLocation)[0] }}@endif.
                @if(!empty($listedLine))
                    {{ $listedLine }}
                @endif
            </p>
            <div class="hs-room-list">
                @foreach ($rooms as $room)
                    <div class="hs-room-row">
                        <div class="hs-room-left">
                            <div class="hs-room-name">{{ $room['name'] }}</div>
                            @if(! empty($room['size']) && $room['size'] !== '-')
                                <div class="hs-room-size">{{ $room['size'] }}</div>
                            @endif
                        </div>
                        @if(! empty($room['level']) && $room['level'] !== '-')
                            <div class="hs-room-level">{{ $room['level'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div style="color:#e63946;font-size:14px;margin:16px 0 0;font-weight:600;">
        Coop Commission: 2.5%
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

    // Lazy-load listing history after first paint (never block SSR on Meili/FULLTEXT).
    const blocks = document.getElementById('propertyDetailBlocks');
    const listingKey = blocks?.dataset?.listingKey;
    const renderHistoryRows = (rows) => {
        const tbody = document.querySelector('#hs-history tbody');
        const tabLabel = document.querySelector('[data-tab="hs-history"] .hs-tab-text');
        if (!tbody || !Array.isArray(rows) || rows.length === 0) {
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
    };

    if (listingKey) {
        fetch('/api/v1/listing-history/' + encodeURIComponent(listingKey), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then((res) => res.ok ? res.json() : null)
        .then((payload) => {
            const rows = Array.isArray(payload?.data) ? payload.data : [];
            renderHistoryRows(rows);
        })
        .catch(() => {});
    }

    let roomsLoaded = {{ count($rooms) > 0 ? 'true' : 'false' }};
    const roomsTabBtn = document.querySelector('[data-tab="hs-rooms"]');
    const loadRooms = () => {
        if (!listingKey || roomsLoaded) {
            return;
        }
        roomsLoaded = true;
        const panel = document.getElementById('hs-rooms');
        if (panel) {
            panel.innerHTML = '<p class="text-muted">{{ __('Loading room details…') }}</p>';
        }
        fetch('/api/v1/property-rooms/' + encodeURIComponent(listingKey), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then((res) => res.ok ? res.json() : null)
        .then((payload) => {
            const rooms = Array.isArray(payload?.data) ? payload.data : [];
            const tabLabel = document.querySelector('[data-tab="hs-rooms"] .hs-tab-text');
            if (tabLabel) {
                tabLabel.textContent = 'Rooms (' + rooms.length + ')';
            }
            if (!panel) {
                return;
            }
            if (rooms.length === 0) {
                panel.innerHTML = '<p class="text-muted">{{ __('Room details are not available for this listing.') }}</p>';
                return;
            }
            panel.innerHTML = '<div class="hs-room-list">' + rooms.map((room) => {
                const size = room.size && room.size !== '-' ? '<div class="hs-room-size">' + room.size + '</div>' : '';
                const level = room.level && room.level !== '-' ? '<div class="hs-room-level">' + room.level + '</div>' : '';
                return '<div class="hs-room-row">'
                    + '<div class="hs-room-left"><div class="hs-room-name">' + (room.name || 'Room') + '</div>' + size + '</div>'
                    + level
                    + '</div>';
            }).join('') + '</div>';
        })
        .catch(() => {
            roomsLoaded = false;
        });
    };

    if (roomsTabBtn) {
        roomsTabBtn.addEventListener('click', loadRooms, { once: false });
    }
});
</script>
