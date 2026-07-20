<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Botble\RealEstate\Http\Controllers\API\PropertyController;
use Illuminate\Support\Facades\Http;
use Theme\homzen\Supports\TrebPropertyHelper;

$controller = app(PropertyController::class);
$ref = new ReflectionClass($controller);
$ampCurl = $ref->getMethod('ampCurl');
$ampCurl->setAccessible(true);

$year = 2025;
$start = '2025-01-01';
$end = '2025-12-31';
$residential = "PropertySubType ne 'Industrial' and PropertySubType ne 'Commercial Retail'";
$filter = "ModificationTimestamp ge {$start}T00:00:00Z and ModificationTimestamp le {$end}T23:59:59Z and {$residential}";

$selectFull = 'ListingKey,UnparsedAddress,PropertySubType,PublicRemarks,PrivateRemarks,'
    . 'BedroomsTotal,BedroomsAboveGrade,BedroomsBelowGrade,BathroomsTotalInteger,KitchensTotal,LivingAreaRange,'
    . 'StandardStatus,ExpirationDate,ListPrice,PostalCode,OriginalEntryTimestamp,ModificationTimestamp,'
    . 'PriceChangeTimestamp,TransactionType,MlsStatus,ListOfficeName,ListingContractDate,CloseDate,'
    . 'PurchaseContractDate,Basement,ParkingSpaces,CoveredSpaces,ClosePrice,StoriesTotal,Levels,ArchitecturalStyle';

$selectMin = 'ListingKey,UnparsedAddress,PropertySubType,ListPrice,MlsStatus,ModificationTimestamp,OriginalEntryTimestamp';

foreach (['min' => $selectMin, 'full' => $selectFull] as $label => $select) {
    $url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter)
        . '&$select=' . rawurlencode($select)
        . '&$top=5&$skip=0';

    $http = Http::timeout(45)->withHeaders([
        'Authorization' => 'Bearer ' . TrebPropertyHelper::ampTokens()[0],
        'Accept' => 'application/json',
        'OData-Version' => '4.0',
    ])->get($url);

    $curl = $ampCurl->invoke($controller, [$url, 45]);

    echo "{$label} select:\n";
    echo '  Http: ' . $http->status() . ' count=' . count($http->json('value') ?? []) . ' err=' . ($http->json('error.message') ?? 'none') . PHP_EOL;
    echo '  ampCurl: ' . (is_array($curl) && isset($curl['value']) ? count($curl['value']) : 'NULL') . PHP_EOL;
    if (is_array($curl) && isset($curl['error'])) {
        echo '  ampCurl error: ' . ($curl['error']['message'] ?? json_encode($curl['error'])) . PHP_EOL;
    }
    echo PHP_EOL;
}

// Test without encoding select (current bug)
$urlBad = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter)
    . '&$select=' . $selectFull . '&$top=5&$skip=0';
$curlBad = $ampCurl->invoke($controller, [$urlBad, 45]);
echo "full select NOT encoded:\n";
echo '  ampCurl: ' . (is_array($curlBad) && isset($curlBad['value']) ? count($curlBad['value']) : 'NULL') . PHP_EOL;
