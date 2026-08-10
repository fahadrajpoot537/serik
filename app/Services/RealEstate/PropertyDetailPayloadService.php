<?php

namespace App\Services\RealEstate;

use Botble\RealEstate\Models\Property;
use Theme\homzen\Supports\TrebPropertyHelper;

class PropertyDetailPayloadService
{
    public function build(Property $property, bool $isIframe): array
    {
        $listingKey = (string) ($property->external_id ?? '');
        $isAuthenticated = auth('account')->check() || auth()->check();
        $isLocked = $property->isSoldHistory() && ! $isAuthenticated;

        $cacheKey = 'serik_property_detail_payload_v2_' . $property->getKey() . '_' . ($isAuthenticated ? 'auth' : 'guest') . '_' . ($isIframe ? 'iframe' : 'full');

        return \App\Support\SerikCache::remember(
            $cacheKey,
            (int) config('serik.cache.property_detail_ttl', 1800),
            function () use ($property, $listingKey, $isIframe, $isAuthenticated, $isLocked): array {
            $localData = TrebPropertyHelper::dbRowToLocalArray($property);
            $factRecord = [];
            $listingHistory = [];
            $priceChanges = [];
            $keyFacts = [];
            $propertyDetails = [];
            $rooms = [];
            $displayName = (string) ($property->name ?? '');
            $displayLocation = '';
            $displayType = (string) ($property->PropertySubType ?? '');

            try {
                $factRecord = $listingKey !== ''
                    ? TrebPropertyHelper::resolveFactRecordForDetail($listingKey, $localData)
                    : [];

                $displayName = TrebPropertyHelper::formatDisplayAddress($factRecord) ?: $displayName;
                $displayLocation = TrebPropertyHelper::formatLocationLine($factRecord);
                $displayType = (string) ($factRecord['PropertySubType'] ?? $property->PropertySubType ?? '');

                if (! $isIframe && $listingKey !== '') {
                    $listingHistory = TrebPropertyHelper::fetchListingHistoryForDetail($listingKey, $localData, $factRecord);
                    $priceChanges = $isLocked ? [] : TrebPropertyHelper::fetchPriceChanges($listingKey);
                } elseif ($listingKey !== '' && $factRecord !== []) {
                    $listingHistory = [[
                        'date_start' => TrebPropertyHelper::formatDateValue($factRecord['ListingContractDate'] ?? null) ?? '-',
                        'date_end' => '',
                        'price' => $factRecord['ListPrice'] ?? ($property->price ?? null),
                        'event' => $factRecord['MlsStatus'] ?? ($property->MlsStatus ?? 'Listed'),
                        'listing_id' => $listingKey,
                    ]];
                }

                $keyFacts = TrebPropertyHelper::buildKeyFacts($factRecord, $localData);
                $propertyDetails = TrebPropertyHelper::buildPropertyDetails($factRecord, $localData);
                $rooms = [];
            } catch (\Throwable $e) {
                report($e);
            }

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

            $keyFactRows = [];
            foreach ($keyFactFields as $key => $label) {
                $value = $keyFacts[$key] ?? null;
                if (TrebPropertyHelper::hasDisplayValue($value)) {
                    $keyFactRows[] = ['label' => $label, 'value' => $value];
                }
            }

            $detailGroupRows = [];
            foreach ($detailGroups as $groupTitle => $fields) {
                $rows = [];
                foreach ($fields as $fieldKey => $fieldLabel) {
                    $value = $propertyDetails[$fieldKey] ?? null;
                    if (TrebPropertyHelper::hasDetailFieldValue($value)) {
                        $rows[] = ['label' => $fieldLabel, 'value' => $value];
                    }
                }

                if ($groupTitle === __('Inside') && ! empty($propertyDetails['bathrooms_details'])) {
                    foreach ((array) $propertyDetails['bathrooms_details'] as $detailLine) {
                        $rows[] = ['label' => __('Bathrooms Detail'), 'value' => $detailLine];
                    }
                }

                if ($rows !== []) {
                    $detailGroupRows[] = ['title' => $groupTitle, 'rows' => $rows];
                }
            }

            return [
                'listingKey' => $listingKey,
                'isAuthenticated' => $isAuthenticated,
                'isLocked' => $isLocked,
                'displayName' => $displayName,
                'displayLocation' => $displayLocation,
                'displayType' => $displayType,
                'listingHistory' => $listingHistory,
                'priceChanges' => $priceChanges,
                'keyFactRows' => $keyFactRows,
                'detailGroupRows' => $detailGroupRows,
                'rooms' => $rooms,
                'listedLine' => $propertyDetails['listed_line'] ?? null,
            ];
        });
    }
}

