<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Theme\homzen\Supports\TrebPropertyHelper;

$listings = ['W13024458', 'E13169304'];

$detailFields = [
    'PropertySubType', 'ArchitecturalStyle', 'CityRegion', 'City', 'DirectionFaces',
    'BedroomsTotal', 'BedroomsAboveGrade', 'BedroomsBelowGrade', 'BathroomsTotalInteger',
    'BathroomsFull', 'BathroomsHalf', 'Basement', 'KitchensTotal', 'RoomsTotal',
    'DenFamilyroomYN', 'FireplaceYN', 'Cooling', 'HeatType', 'HeatSource', 'WaterSource',
    'ConstructionMaterials', 'BuildingAreaTotal', 'LivingAreaRange', 'GarageType',
    'CoveredSpaces', 'ParkingSpaces', 'ParkingTotal', 'Driveway', 'LotWidth', 'LotDepth',
    'LotSizeArea', 'CrossStreet', 'ExteriorFeatures', 'PetsAllowed', 'StoriesTotal',
    'Levels', 'NumberOfStories', 'MlsStatus', 'TransactionType', 'ListingContractDate',
    'ListPrice', 'UnparsedAddress', 'StreetNumber', 'StreetName',
];

foreach ($listings as $key) {
    echo str_repeat('=', 80) . PHP_EOL;
    echo "LISTING: {$key}" . PHP_EOL;
    echo str_repeat('=', 80) . PHP_EOL;

    $amp = TrebPropertyHelper::fetchAmpPropertyForResync($key);
    echo PHP_EOL . '--- AMP (fetchAmpPropertyForResync) ---' . PHP_EOL;
    if (! $amp) {
        echo "AMP record: NULL\n";
    } else {
        foreach ($detailFields as $field) {
            $val = $amp[$field] ?? null;
            if ($val !== null && $val !== '' && $val !== []) {
                $display = is_array($val) ? json_encode($val) : $val;
                echo "  {$field}: {$display}" . PHP_EOL;
            }
        }
    }

    $db = DB::table('re_properties')->where('external_id', $key)->first();
    echo PHP_EOL . '--- DATABASE (re_properties) ---' . PHP_EOL;
    if (! $db) {
        echo "DB record: NULL\n";
    } else {
        $cols = [
            'name', 'price', 'square', 'number_bedroom', 'number_bathroom', 'number_floor',
            'MlsStatus', 'TransactionType', 'PropertySubType', 'ParkingSpaces', 'CoveredSpaces',
            'BedroomsBelowGrade', 'Basement', 'broker', 'listing_contract_date',
        ];
        foreach ($cols as $col) {
            if (isset($db->$col) && $db->$col !== null && $db->$col !== '') {
                echo "  {$col}: {$db->$col}" . PHP_EOL;
            }
        }
        if (! empty($db->content)) {
            echo '  content: ' . substr(strip_tags($db->content), 0, 80) . '...' . PHP_EOL;
        }
    }

    $local = $db ? TrebPropertyHelper::dbRowToLocalArray((object) array_merge((array) $db, [])) : TrebPropertyHelper::localPropertyArray($key);
    $fact = $amp ?: TrebPropertyHelper::enrichRecordAddress(TrebPropertyHelper::recordFromLocal($local, $key));

    echo PHP_EOL . '--- RENDERED (buildPropertyDetails) ---' . PHP_EOL;
    $details = TrebPropertyHelper::buildPropertyDetails($fact, $local);
    foreach ($details as $k => $v) {
        if ($v !== null && $v !== '' && $v !== '-' && $v !== []) {
            $display = is_array($v) ? json_encode($v) : $v;
            echo "  {$k}: {$display}" . PHP_EOL;
        }
    }

    echo PHP_EOL . '--- RENDERED (buildKeyFacts) ---' . PHP_EOL;
    $facts = TrebPropertyHelper::buildKeyFacts($fact, $local);
    foreach ($facts as $k => $v) {
        if ($v !== null && $v !== '' && $v !== '-') {
            echo "  {$k}: {$v}" . PHP_EOL;
        }
    }

    echo PHP_EOL . '--- LISTING HISTORY ---' . PHP_EOL;
    $hist = TrebPropertyHelper::fetchListingHistoryForDetail($key, $local, $fact);
    echo '  count: ' . count($hist) . PHP_EOL;
    foreach ($hist as $row) {
        echo '  ' . json_encode($row) . PHP_EOL;
    }

    echo PHP_EOL . '--- ROOMS ---' . PHP_EOL;
    $rooms = TrebPropertyHelper::fetchPropertyRoomsForDetail($key);
    echo '  count: ' . count($rooms) . PHP_EOL;

    // Raw AMP without select restriction
    $rawUrl = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode("ListingKey eq '{$key}'") . '&$top=1';
    $raw = TrebPropertyHelper::ampGetFresh($rawUrl, 15, 2);
    $rawRec = $raw['value'][0] ?? null;
    echo PHP_EOL . '--- AMP RAW (no $select) extra fields ---' . PHP_EOL;
    if ($rawRec) {
        $missingFromSelect = [];
        foreach ($detailFields as $field) {
            $inSelect = isset($amp[$field]) && $amp[$field] !== null && $amp[$field] !== '';
            $inRaw = isset($rawRec[$field]) && $rawRec[$field] !== null && $rawRec[$field] !== '';
            if ($inRaw && ! $inSelect) {
                $missingFromSelect[$field] = is_array($rawRec[$field]) ? json_encode($rawRec[$field]) : $rawRec[$field];
            }
        }
        if ($missingFromSelect) {
            foreach ($missingFromSelect as $f => $v) {
                echo "  MISSING FROM SELECT: {$f}: {$v}" . PHP_EOL;
            }
        } else {
            echo "  All checked fields present in select response or empty in raw" . PHP_EOL;
        }

        // Show all non-empty raw fields count
        $nonEmpty = array_filter($rawRec, fn ($v) => $v !== null && $v !== '' && $v !== []);
        echo '  Raw non-empty field count: ' . count($nonEmpty) . PHP_EOL;
    } else {
        echo "  Raw AMP: NULL\n";
    }

    echo PHP_EOL;
}
