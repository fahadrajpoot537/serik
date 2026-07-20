<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Theme\homzen\Supports\TrebPropertyHelper;

$year = (int) ($argv[1] ?? 2025);
$filterType = $argv[2] ?? 'modification';
$skip = (int) ($argv[3] ?? 0);

$start = sprintf('%04d-01-01', $year);
$nextYearStart = sprintf('%04d-01-01', $year + 1);
$residential = "PropertySubType ne 'Industrial' and PropertySubType ne 'Commercial Retail'";
$filterMap = [
    'modification' => "ModificationTimestamp ge {$start}T00:00:00Z and ModificationTimestamp lt {$nextYearStart}T00:00:00Z and {$residential}",
];

$select = 'ListingKey,UnparsedAddress,PropertySubType,PublicRemarks,PrivateRemarks,'
    . 'BedroomsTotal,BedroomsAboveGrade,BedroomsBelowGrade,BathroomsTotalInteger,KitchensTotal,LivingAreaRange,'
    . 'StandardStatus,ExpirationDate,ListPrice,PostalCode,OriginalEntryTimestamp,ModificationTimestamp,'
    . 'PriceChangeTimestamp,TransactionType,MlsStatus,ListOfficeName,'
    . 'ListingContractDate,CloseDate,PurchaseContractDate,Basement,ParkingSpaces,CoveredSpaces,ClosePrice,ArchitecturalStyle';

$urlRaw = 'https://query.ampre.ca/odata/Property?'
    . '$filter=' . rawurlencode($filterMap[$filterType])
    . '&$select=' . $select
    . '&$top=100'
    . '&$skip=' . $skip;

$urlEnc = 'https://query.ampre.ca/odata/Property?'
    . '$filter=' . rawurlencode($filterMap[$filterType])
    . '&$select=' . rawurlencode($select)
    . '&$top=100'
    . '&$skip=' . $skip;

$token = TrebPropertyHelper::ampTokens()[0];
$headers = [
    'Authorization' => 'Bearer ' . $token,
    'Accept' => 'application/json',
    'OData-Version' => '4.0',
    'OData-MaxVersion' => '4.0',
];

foreach (['raw select' => $urlRaw, 'encoded select' => $urlEnc] as $label => $url) {
    $r = Http::timeout(60)->withHeaders($headers)->get($url);
    echo "=== {$label} ===\n";
    echo "Status: {$r->status()}\n";
    echo substr($r->body(), 0, 500) . "\n";
    $json = $r->json();
    echo 'Count: ' . count($json['value'] ?? []) . "\n\n";
}

echo "ampGet raw: ";
$p = TrebPropertyHelper::ampGet($urlRaw, 60);
echo is_array($p) ? count($p['value'] ?? []) : 'null';
echo "\n";
