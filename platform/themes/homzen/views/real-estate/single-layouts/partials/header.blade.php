@php
    use Theme\homzen\Supports\TrebPropertyHelper;

    $model = $model ?? $property ?? null;
    $listingKey = $model->external_id ?? '';
    $ampRecord = $listingKey ? TrebPropertyHelper::ensureAmpRecord($listingKey, [
            'name' => $model->name,
            'price' => $model->price,
            'MlsStatus' => $model->MlsStatus,
            'TransactionType' => $model->TransactionType,
            'PropertySubType' => $model->PropertySubType,
        ]) : null;

    $factRecord = $ampRecord
        ?: TrebPropertyHelper::enrichRecordAddress(TrebPropertyHelper::recordFromLocal([
            'name' => $model->name,
            'price' => $model->price,
            'MlsStatus' => $model->MlsStatus,
            'TransactionType' => $model->TransactionType,
            'PropertySubType' => $model->PropertySubType,
        ], $listingKey));

    $displayName = TrebPropertyHelper::formatDisplayAddress($factRecord);
    $displayLocation = TrebPropertyHelper::formatLocationLine($factRecord);
    $displayType = $factRecord['PropertySubType'] ?? $model->PropertySubType ?? '';
@endphp



<style>
@media (max-width: 768px) {
    .title {
        font-size: 1.15rem !important;
        line-height: 1.35;
    }
    .box-price {
        font-size: 0.95rem !important;
    }
    .box-price.d-flex {
        display: block !important;
        text-align: left !important;
        margin-top: 10px;
    }
    .header-property-detail {
        margin-bottom: 0;
        padding-bottom: 0;
        padding-left: 12px;
        padding-right: 12px;
    }
    .header-property-detail .content-top {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 8px;
    }
    #propertyType {
        font-size: 15px !important;
    }
}
</style>

<div class="header-property-detail">
    <div class="content-top d-flex justify-content-between align-items-center">
        
        <!-- LEFT SIDE -->
        <div class="box-name">
            <h1 class="h4 title">
                {!! BaseHelper::clean($displayName) !!}
                <br>
                <span id="cityRegion">{{ $displayLocation }}</span><br>
                <span id="propertyType" style="font-size:18px;">{{ $displayType }}</span>
            </h1>
        </div>

        <!-- RIGHT SIDE -->
        <div class="box-price d-flex align-items-center">
            <h4 style="font-size:20px; text-align: right;" id="priceBox">
                
                @if($model->isSoldHistory())
                    <span class="flag-tag primary status-sold d-inline-block mb-2">{{ $model->MlsStatus === 'Leased' ? __('Leased') : __('Sold') }}</span><br>
                    Listed : 
                    <span style="text-decoration: line-through; color: gray;">
                        {{ $model->price_html ?? $model->formatted_price }}
                    </span>
                    <br>

                    @if(!empty($model->ClosePrice))
                        Sold : 
                        <span style="margin-left:8px; color:var(--primary-color, #db1d23);">
                            ${{ number_format((float) $model->ClosePrice) }}
                        </span>
                    @endif
                    <br>

                    Sold On : <span id="soldDate"></span>

                @elseif($model->MlsStatus == 'Expired' || $model->MlsStatus == 'Terminated')

                    <span style="text-decoration: line-through; color: gray;">
                        {{ $model->price_html ?? $model->formatted_price }}
                    </span>

                @else
                    @php
                        $cash_back = ($model->price/100)*1.5;
                    @endphp

                    Listed For :
                    <span>
                        {{ $model->price_html ?? $model->formatted_price }}
                    </span>

                    <br>

                    <span style="color:#e63946;font-size:14px;">
                        Your Cash Back is ${{ number_format($cash_back) }} (*Terms and Conditions Apply)
                    </span>

                    <br>

                    <span style="color:#777;font-size:17px;">
                        <span id="listingDate"></span>
                    </span>
                @endif

            </h4>
        </div>

    </div>

    @include(Theme::getThemeNamespace('views.real-estate.partials.meta'), ['model' => $model])
</div>

<!-- LOADING -->
<div id="loader" style="display:none;">Loading...</div>



<script>
// Human-friendly relative listed label (mirrors TrebPropertyHelper::relativeListedLabel).
function relativeListedLabel(dateStr, prefix) {
    prefix = prefix || 'Listed';
    if (!dateStr) return '';

    const listed = new Date(String(dateStr).replace(' ', 'T'));
    if (isNaN(listed.getTime())) return '';

    const year = listed.getFullYear();
    if (year < 2000 || year > (new Date().getFullYear() + 1)) return '';

    const now = new Date();
    const startOfDay = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate());
    const sameDay = startOfDay(listed).getTime() === startOfDay(now).getTime();

    if (sameDay) return prefix + ' today';

    const weekAgo = startOfDay(now);
    weekAgo.setDate(weekAgo.getDate() - 7);
    if (listed.getTime() >= weekAgo.getTime()) return prefix + ' this week';

    if (listed.getFullYear() === now.getFullYear() && listed.getMonth() === now.getMonth()) {
        return prefix + ' this month';
    }

    // Older than this month: show month + year, e.g. "Listed June 2026".
    const monthYear = listed.toLocaleString('en-US', { month: 'long', year: 'numeric' });
    return (prefix + ' ' + monthYear).trim();
}

document.addEventListener("DOMContentLoaded", function () {
    let listingKey = "{{ $model->external_id }}";
    const apiBase = "{{ url('/api/v1') }}";

    fetch(`${apiBase}/getPropertyDetails/${listingKey}`)
        .then(response => response.json())
        .then(res => {
            if (!res.data) return;
            let item = res.data;
            document.getElementById('cityRegion').innerText = [item.City, item.CityRegion].filter(Boolean).join(' - ');
            document.getElementById('propertyType').innerText = item.PropertySubType ?? '';
            const listingEl = document.getElementById('listingDate');
            if (listingEl && item.ListingContractDate) {
                listingEl.innerText = relativeListedLabel(item.ListingContractDate, 'Listed');
            }
            const soldDate = item.PurchaseContractDate;
            if (soldDate) {
                const soldEl = document.getElementById('soldDate');
                if (soldEl) soldEl.innerText = soldDate.split('T')[0];
            }
        })
        .catch(err => console.error("API Error:", err));
});
</script>