
<style>

/* Style the tab */
.tab {
    margin-top:20px;
  overflow: hidden;
}

/* Style the buttons inside the tab */
.tab button {
  background-color: inherit;
  float: left;
  border: none;
  width:50%;
  outline: none;
  cursor: pointer;
  padding: 14px 16px;
  transition: 0.3s;
  font-size: 17px;
}

/* Change background color of buttons on hover */
.tab button:hover {
    color:white;
  background-color: #0255a1;
}

/* Create an active/current tablink class */
.tab button.active {
    background:#d7ecff;
    border-bottom:3px solid #0255a1;
}

/* Keep the stat icon (bed / bath / garage etc.) background always dark (hover color) */
.info-box1 .box-icon,
.info-box1 .box-icon:hover {
    background-color: var(--primary-color) !important;
    color: #fff !important;
}
.info-box1 .box-icon svg,
.info-box1 .box-icon i,
.info-box1 .box-icon [class^="ti-"],
.info-box1 .box-icon [class*=" ti-"] {
    color: #fff !important;
}

/* Style the tab content */
.tabcontent {
  display: none;
  padding: 6px 12px;
  border-top: none;
}



@media (max-width: 992px) {

    /* =========================
       INFO BOX (TOP STATS)
    ========================== */
    .info-box {
        border-radius: 10px;
        padding: 10px;
    }

    .info-box .col.item {
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }

    .info-box .content span {
        font-size: 13px;
    }

    /* =========================
       TABS
    ========================== */
    .tab {
        display: flex;
        width: 100%;
        margin-top: 15px;
    }

    .tab button {
        width: 50%;
        font-size: 14px;
        padding: 10px;
        text-align: center;
    }

    /* =========================
       TABLE SCROLL FIX
    ========================== */
    #history {
        overflow-x: auto;
    }

    #history table {
        min-width: 600px; /* allows horizontal scroll */
        font-size: 12px;
    }

    #history th,
    #history td {
        white-space: nowrap;
        padding: 8px;
    }

    /* =========================
       KEY FEATURES GRID
    ========================== */
    .info-box.row-cols-sm-2.row-cols-lg-2 {
        grid-template-columns: 1fr !important;
    }

    .info-box .col.item {
        width: 100%;
    }

    /* =========================
       DESCRIPTION / NOTES
    ========================== */
    .single-property-desc,
    .single-property-overview {
        padding: 10px;
    }

    .bd-callout {
        padding: 12px;
        font-size: 13px;
    }

    /* =========================
       FIX TAB CONTENT SPACING
    ========================== */
    .tabcontent {
        padding: 10px 0;
    }

    .info-box1 {
        font-size: 13px;
    }

    .info-box1 .box-icon.w-52 {
        width: 42px !important;
        height: 42px !important;
    }

    .info-box1 .content .label,
    .info-box1 .content span {
        font-size: 12px;
        line-height: 1.35;
    }

    #propertyDetailBlocks,
    .hs-detail-section {
        overflow: visible !important;
        width: 100%;
        max-width: 100%;
    }
}


</style>

@php
    use Theme\homzen\Supports\TrebPropertyHelper;

    $model = $model ?? $property ?? null;
    $listingKey = $model->external_id ?? '';
    $item = $listingKey ? TrebPropertyHelper::fetchPropertyRecord($listingKey) : null;

    if (! $item && $model) {
        $item = [
            'UnparsedAddress' => $model->name,
            'PropertySubType' => $model->PropertySubType,
            'ListPrice' => $model->price,
            'LivingAreaRange' => $model->square,
            'ParkingSpaces' => $model->ParkingSpaces,
            'ListOfficeName' => $model->broker,
            'ListingContractDate' => $model->created_at,
            'MlsStatus' => $model->MlsStatus,
            'TransactionType' => $model->TransactionType,
        ];
    }
@endphp

@php
$allowedCities = [
    'Brampton',
    'Mississauga',
    'Vaughan',
    'Milton',
    'Oakville',
    'NiagaraFalls',
    'Toronto',
    'Kitchener',
    'Waterloo',
    'Cambridge',
    'Hamilton',
    'Ottawa',
    'London',
    'Markham',
    'Windsor',
    'RichmondHill',
    'Burlington',
    'Oshawa',
    'Barrie',
    'Guelph',
    'Kingston',
    'Whitby',
    'Ajax',
    'Peterborough',
    'Sarnia',
    'ThunderBay',
    'Sudbury',
    'NorthBay',
    'Orillia',
    'Brantford',
    'StCatharines',
    'Welland',
    'Pickering',
    'Clarington',
    'Newmarket',
    'Aurora',
    'Orangeville',
    'Midland',
    'Collingwood',
    'Timmins',
    'Kenora',
    'ElliotLake',
    'Brockville',
    'Cornwall',
    'Stratford',
    'Woodstock',
    'Leamington',
    'Chatham',
    'Belleville',
    'Pembroke'
];

$city = $item['City'] ?? '';

if (empty($city) && !empty($model->name)) {

    $fullAddress = strtolower($model->name);

    $matched = collect($allowedCities)
        ->sortByDesc(fn($c) => strlen($c))
        ->first(function ($c) use ($fullAddress) {

            $normalized =
                strtolower(
                    preg_replace(
                        '/(?<!^)([A-Z])/',
                        ' $1',
                        $c
                    )
                );

            return str_contains(
                $fullAddress,
                $normalized
            );
        });

    $city = $matched ?? '';
}

View::share('cityName', $city);

@endphp


<div @class(['single-property-overview', $class ?? null]) id="overview">
    
    
  
    
          <div class="row row-cols-xs-3 row-cols-sm-3 row-cols-lg-3 g-3 g-lg-4 info-box info-box1" style="border-top:2px solid gray;border-bottom:4px solid gray;padding:10px 0px;border-radius:0px; ">
              
            @if (($model->number_bedroom ?? null))
                <div class="col item">
                    <div class="box-icon w-52">
                        <x-core::icon name="ti ti-bed" />
                    </div>
                    <div class="content">
                        <span class="label">{{ __('Bedrooms:') }}</span>
                        <span>{{ $model->number_bedroom }}{{ (int) ($model->BedroomsBelowGrade ?? 0) > 0 ? '+' . (int) $model->BedroomsBelowGrade : '' }}</span>
                    </div>
                </div>
            @endif
            @if (($model->number_bathroom ?? null))
                <div class="col item">
                    <div class="box-icon w-52">
                        <x-core::icon name="ti ti-bath" />
                    </div>
                    <div class="content">
                        <span class="label">{{ __('Bathrooms:') }}</span>
                        <span>{{ number_format($model->number_bathroom) }} </span>
                    </div>
                </div>
            @endif
              {{-- Garage Type --}}
            @if(!empty($item['GarageType']))
            <div class="col item">
                <div class="box-icon w-52">
                   <x-core::icon name="ti ti-car" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Garage / Parking Places:') }}</span>
                    <span>{{ $item['ParkingSpaces'] }}</span>
                </div>
            </div>
            @endif
        </div>
  
    
    



         
  
    
    
    
    
    
    @include(Theme::getThemeNamespace('views.real-estate.single-layouts.partials.property-detail-blocks'), ['model' => $model, 'class' => $class ?? null])

    <div class="h7 title fw-7" id="features" style="padding: 20px 10px;">{{ __('Key Features') }}</div>
         <div class="row row-cols-sm-2 row-cols-lg-2 g-2 g-lg-2 info-box" style="padding: 20px 10px;">
             
            
              <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-home" />
                </div>
                <div class="content">
                    <span class="label">
                        @if($model instanceof \Botble\RealEstate\Models\Project)
                            {{ __('Project ID:') }}
                        @else
                            {{ __('Listing Key:') }}
                        @endif
                    </span>
                    <span>{{ $model->external_id ?: $model->getKey() }}</span>
                </div>
            </div>
            @if ($model->categories->isNotEmpty())
                <div class="col item">
                    <div class="box-icon w-52">
                        <x-core::icon name="ti ti-category" />
                    </div>
                    <div class="content">
                        <span class="label">{{ __('Type:') }}</span>
                        <span>
                            @foreach ($model->categories as $category)
                                <a href="{{ $category->url }}">{!! BaseHelper::clean($category->name) !!}</a>@if (!$loop->last),&nbsp;@endif
                            @endforeach
                        </span>
                    </div>
                </div>
            @endif
            @if (($model->investor->name ?? null))
                <div class="col item">
                    <div class="box-icon w-52">
                        <x-core::icon name="ti ti-category" />
                    </div>
                    <div class="content">
                        <span class="label">{{ __('Investor:') }}</span>
                        <span>{{ $model->investor->name }}</span>
                    </div>
                </div>
            @endif
            @if (($model->number_block ?? null))
                <div class="col item">
                    <div class="box-icon w-52"> 
                        <x-core::icon name="ti ti-packages" />
                    </div>
                    <div class="content">
                        <span class="label">{{ __('Blocks:') }}</span>
                        <span>{{ number_format($model->number_block) }}</span>
                    </div>
                </div>
            @endif
            @if (($model->number_flat ?? null))
                <div class="col item">
                    <div class="box-icon w-52">
                        <x-core::icon name="ti ti-building" />
                    </div>
                    <div class="content">
                        <span class="label">{{ __('Flats:') }}</span>
                        <span>{{ number_format($model->number_flat) }}</span>
                    </div>
                </div>
            @endif
            
            @php
                $displayFloors = $item['StoriesTotal'] ?? null;
                if (empty($displayFloors) && !empty($item['ArchitecturalStyle'])) {
                    $styleLabel = is_array($item['ArchitecturalStyle']) ? implode(' ', $item['ArchitecturalStyle']) : (string) $item['ArchitecturalStyle'];
                    if (preg_match('/(\d+(?:\.\d+)?)\s*-\s*storey/i', $styleLabel, $matches)) {
                        $displayFloors = (int) round((float) $matches[1]);
                    }
                }
                $displayFloors = $displayFloors ?: ($model->number_floor ?? null);
            @endphp
            @if ($displayFloors)
                <div class="col item">
                    <div class="box-icon w-52">
                        <x-core::icon name="ti ti-stairs" />
                    </div>
                    <div class="content">
                        <span class="label">{{ __('Floors:') }}</span>
                        <span>{{ number_format((float) $displayFloors) }}</span>
                    </div>
                </div>
            @endif
            @if (($model->square ?? null))
                <div class="col item">
                    <div class="box-icon w-52">
                        <x-core::icon name="ti ti-ruler-2" />
                    </div>
                    <div class="content">
                        <span class="label">{{ __('Square:') }}</span>
                        <span>{{ $model->square_text }}</span>
                    </div>
                </div>
            @endif
            @if (($model->date_finish ?? null))
                <div class="col item">
                    <div class="box-icon w-52">
                        <x-core::icon name="ti ti-calendar-check" />
                    </div>
                    <div class="content">
                        <span class="label">{{ __('Finish Date:') }}</span>
                        <span>{{ $model->date_finish->format('M d, Y') }}</span>
                    </div>
                </div>
            @endif
            @if (($model->date_sell ?? null))
                <div class="col item">
                    <div class="box-icon w-52">
                        <x-core::icon name="ti ti-calendar-dollar" />
                    </div>
                    <div class="content">
                        <span class="label">{{ __('Open Sell Date:') }}</span>
                        <span>{{ $model->date_sell->format('M d, Y') }}</span>
                    </div>
                </div>
            @endif
            @foreach ($model->customFields as $customField)
                @continue(! $customField->value)
                <div class="col item">
                    <div class="box-icon w-52">
                        <x-core::icon name="ti ti-box" />
                    </div>
                    <div class="content">
                        <span class="label">{!! BaseHelper::clean($customField->name) !!}:</span>
                        <span>{!! BaseHelper::clean($customField->value) !!}</span>
                    </div>
                </div>
            @endforeach
      
             
             
         @if(!empty($model->broker))
                        <div class="col item">
                            <div class="box-icon w-52">
                                <x-core::icon name="ti ti-building" />
                            </div>
                            <div class="content">
                                <span class="label">{{ __('Brokage:') }}</span>
                                <span>{{ $model->broker }}</span>
                            </div>
                        </div>
                  @endif
                   
                        {{-- Listed On --}}
            @php
                $listedRelative = !empty($item['ListingContractDate'])
                    ? \Theme\homzen\Supports\TrebPropertyHelper::relativeListedLabel($item['ListingContractDate'], '')
                    : '';
            @endphp
            @if($listedRelative !== '')
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-calendar-plus" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Listed:') }}</span>
                    <span>{{ __(ucfirst($listedRelative)) }}</span>
                </div>
            </div>
            @endif
            
            
            {{-- Updated On --}}
            @if(!empty($item['ModificationTimestamp']))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-refresh" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Updated On:') }}</span>
                    <span>{{ \Carbon\Carbon::parse($item['ModificationTimestamp'])->format('Y-m-d') }}</span>
                </div>
            </div>
            @endif
                        
                        
                        {{-- Property Type --}}
            @if(!empty($item['PropertySubType']))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-home" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Property Type:') }}</span>
                    <span>{{ $item['PropertySubType'] }}</span>
                </div>
            </div>
            @endif
            
          @if(!empty($item['Basement']))
            @php
                $basements = is_array($item['Basement']) 
                    ? $item['Basement'] 
                    : json_decode($item['Basement'], true);
            @endphp
            
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-home" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Basement:') }}</span>
                    <span>{{ implode(', ', $basements ?? []) }}</span>
                </div>
            </div>
            @endif
            
            
            {{-- Style --}}
            @if(!empty($item['ArchitecturalStyle'][0]))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-building" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Style:') }}</span>
                    <span>{{ $item['ArchitecturalStyle'][0] }}</span>
                </div>
            </div>
            @endif
            
            
            {{-- Community --}}
            @if(!empty($item['CityRegion']))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-map-pin" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Community:') }}</span>
                    <span>{{ $item['CityRegion'] }}</span>
                </div>
            </div>
            @endif
            
            
            {{-- Municipality --}}
            @if(!empty($item['City']))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-building-community" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Municipality:') }}</span>
                    <span>{{ $item['City'] }}</span>
                </div>
            </div>
            @endif
            
            
            
            
            {{-- Kitchens --}}
            @if(!empty($item['KitchensTotal']))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-chef-hat" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Kitchens:') }}</span>
                    <span>{{ $item['KitchensTotal'] }}</span>
                </div>
            </div>
            @endif
            
            
            {{-- Rooms --}}
            @if(!empty($item['RoomsAboveGrade']))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-door" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Rooms:') }}</span>
                    <span>{{ $item['RoomsAboveGrade'] }}</span>
                </div>
            </div>
            @endif
            
            
            {{-- Size --}}
            @if(!empty($item['LivingAreaRange']))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-ruler-measure" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Size:') }}</span>
                    <span>{{ \Theme\homzen\Supports\TrebPropertyHelper::formatSizeLabel($item, ['square' => $model->square]) }}</span>
                </div>
            </div>
            @endif
            
            
            {{-- Construction --}}
            @if(!empty($item['ConstructionMaterials'][0]))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-building" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Construction:') }}</span>
                    <span>{{ $item['ConstructionMaterials'][0] }}</span>
                </div>
            </div>
            @endif
            
            
            {{-- Parking --}}
            @if(!empty($item['ParkingTotal']))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-car" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Parking:') }}</span>
                    <span>{{ $item['ParkingTotal'] }}</span>
                </div>
            </div>
            @endif
            
            
          
            
            
            {{-- Cooling --}}
            @if(!empty($item['Cooling'][0]))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-snowflake" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Cooling:') }}</span>
                    <span>{{ $item['Cooling'][0] }}</span>
                </div>
            </div>
            @endif
            
            
            {{-- Heating Type --}}
            @if(!empty($item['HeatType']))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-flame" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Heating Type:') }}</span>
                    <span>{{ $item['HeatType'] }}</span>
                </div>
            </div>
            @endif
            
            
            {{-- Heating Fuel --}}
            @if(!empty($item['HeatSource']))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-gas-station" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Heating Fuel:') }}</span>
                    <span>{{ $item['HeatSource'] }}</span>
                </div>
            </div>
            @endif
            
            
            {{-- Pets Allowed --}}
            @if(!empty($item['PetsAllowed'][0]))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-paw" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Pets Allowed:') }}</span>
                    <span>{{ $item['PetsAllowed'][0] }}</span>
                </div>
            </div>
            @endif
            
            
            {{-- Cross Street --}}
            @if(!empty($item['CrossStreet']))
            <div class="col item">
                <div class="box-icon w-52">
                    <x-core::icon name="ti ti-road" />
                </div>
                <div class="content">
                    <span class="label">{{ __('Cross Street:') }}</span>
                    <span>{{ $item['CrossStreet'] }}</span>
                </div>
            </div>
            @endif
        </div>
    </div>
    
   
    
    
    
    



@if ($model->content)
    @php
        $plainDesc = trim(strip_tags($model->content));
        $needsTruncate = mb_strlen($plainDesc) > 200;
    @endphp
    <div @class(['single-property-desc', $class ?? null])>
        <div class="h7 title fw-7" id="description">{{ __('Description') }}</div>
        <div class="body-2 text-variant-1 hs-desc-wrap">
            <div class="ck-content single-detail hs-desc-body @if($needsTruncate) hs-desc-truncated @endif">
                @if($needsTruncate)
                    <span class="hs-desc-short">{{ mb_substr($plainDesc, 0, 200) }}…</span>
                    <span class="hs-desc-full" style="display:none">{!! BaseHelper::clean($model->content) !!}</span>
                @else
                    {!! BaseHelper::clean($model->content) !!}
                @endif
            </div>
            @if($needsTruncate)
                <button type="button" class="hs-desc-toggle">Show More</button>
            @endif
        </div>
    </div>
@endif

