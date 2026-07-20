<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Theme\homzen\Supports\TrebPropertyHelper;

$token = TrebPropertyHelper::ampTokens()[0];
$headers = ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json', 'OData-Version' => '4.0'];
$res = "PropertySubType ne 'Industrial' and PropertySubType ne 'Commercial Retail'";
$filter = "ModificationTimestamp ge 2025-01-01T00:00:00Z and ModificationTimestamp lt 2026-01-01T00:00:00Z and {$res}";

$selectMin = 'ListingKey,UnparsedAddress,PropertySubType,ListPrice,MlsStatus,ModificationTimestamp,OriginalEntryTimestamp';
$selectFull = 'ListingKey,UnparsedAddress,PropertySubType,PublicRemarks,PrivateRemarks,'
    . 'BedroomsTotal,BedroomsAboveGrade,BedroomsBelowGrade,BathroomsTotalInteger,KitchensTotal,LivingAreaRange,'
    . 'StandardStatus,ExpirationDate,ListPrice,PostalCode,OriginalEntryTimestamp,ModificationTimestamp,'
    . 'PriceChangeTimestamp,TransactionType,MlsStatus,ListOfficeName,ListingContractDate,CloseDate,'
    . 'PurchaseContractDate,Basement,ParkingSpaces,CoveredSpaces,ClosePrice,StoriesTotal,Levels,ArchitecturalStyle';

foreach (['min' => $selectMin, 'full' => $selectFull] as $label => $select) {
    $url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter)
        . '&$select=' . $select . '&$top=5';
    $r = Http::timeout(45)->withHeaders($headers)->get($url);
    echo "{$label}: status={$r->status()} count=" . count($r->json('value') ?? []) . ' err=' . ($r->json('error.message') ?? '') . PHP_EOL;
    $amp = TrebPropertyHelper::ampGet($url, 45);
    echo "  ampGet: " . (is_array($amp) ? count($amp['value'] ?? []) : 'null') . PHP_EOL;
}
