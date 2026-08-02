@php
    use Theme\homzen\Supports\TrebPropertyHelper;

    $model = $model ?? $property ?? null;
    $listingKey = $model->external_id ?? '';
    $localHeader = [
        'name' => $model->name,
        'price' => $model->price,
        'MlsStatus' => $model->MlsStatus,
        'TransactionType' => $model->TransactionType,
        'PropertySubType' => $model->PropertySubType,
    ];
    $factRecord = [];
    $displayName = $model->name ?? '';
    $displayLocation = '';
    $displayType = $model->PropertySubType ?? '';

    try {
        $factRecord = $listingKey
            ? TrebPropertyHelper::resolveFactRecordForDetail($listingKey, $localHeader)
            : TrebPropertyHelper::enrichRecordAddress(TrebPropertyHelper::recordFromLocal($localHeader, $listingKey));

        $displayName = TrebPropertyHelper::formatDisplayAddress($factRecord) ?: ($model->name ?? '');
        $displayLocation = TrebPropertyHelper::formatLocationLine($factRecord);
        $displayType = $factRecord['PropertySubType'] ?? $model->PropertySubType ?? '';
    } catch (\Throwable $e) {
        try {
            report($e);
        } catch (\Throwable) {
        }
    }
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
                    @if($model->MlsStatus == 'Terminated')
                        <br>
                        <span class="flag-tag primary status-sold d-inline-block mb-2">{{ $model->MlsStatus }}</span>
                    @endif

                @else
                    Listed For :
                    <span>
                        {{ $model->price_html ?? $model->formatted_price }}
                    </span>

                    <br>

                    <span style="color:#e63946;font-size:14px;">
                        Cash back upto 1.5% of purchase price<br>
                        (*Some Terms and Conditions Apply)
                    </span>

                    <br>

                    <span style="color:#777;font-size:17px;">
                        @php
                            $listedLabelSsr = \Theme\homzen\Supports\TrebPropertyHelper::relativeListedLabel(
                                $model->listing_contract_date ?? $model->created_at
                            );
                        @endphp
                        <span id="listingDate">{{ $listedLabelSsr }}</span>
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
    const listedDay = startOfDay(listed);
    const nowDay = startOfDay(now);
    const days = Math.round((nowDay.getTime() - listedDay.getTime()) / 86400000);

    if (days <= 0) return (prefix + ' today').trim();
    if (days === 1) return (prefix + ' 1 day ago').trim();
    if (days <= 6) return (prefix + ' ' + days + ' days ago').trim();
    if (days <= 13) return (prefix + ' this week').trim();

    if (listed.getFullYear() === now.getFullYear() && listed.getMonth() === now.getMonth()) {
        return (prefix + ' this month').trim();
    }

    const monthYear = listed.toLocaleString('en-US', { month: 'long', year: 'numeric' });
    return (prefix + ' in ' + monthYear).trim();
}

document.addEventListener("DOMContentLoaded", function () {
    @unless(request()->boolean('iframe'))
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
                if (soldEl) soldEl.innerText = String(soldDate).split('T')[0];
            }
        })
        .catch(function () {});
    @endunless
});
</script>