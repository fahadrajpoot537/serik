<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Theme\homzen\Supports\TrebPropertyHelper;

$key = 'W13024458';

echo "=== DEEP HISTORY SEARCH FOR {$key} ===\n\n";

$record = TrebPropertyHelper::resolveFactRecordForDetail($key);
echo "Address: " . ($record['UnparsedAddress'] ?? '') . "\n";
echo "Lat/Lng: " . ($record['Latitude'] ?? $record['latitude'] ?? 'n/a') . "\n\n";

// 1. DB - exact address
echo "--- DB: exact 222 Simmons ---\n";
$exact = DB::table('re_properties')
    ->where('name', 'like', '%222%Simmons%')
    ->orderByDesc('listing_contract_date')
    ->get(['external_id', 'MlsStatus', 'name', 'listing_contract_date', 'price', 'ClosePrice']);
foreach ($exact as $r) {
    echo "  {$r->external_id} | {$r->MlsStatus} | {$r->listing_contract_date} | \${$r->price}\n";
}
echo "Count: {$exact->count()}\n\n";

// 2. DB - same lat/lng (property model)
$p = DB::table('re_properties')->where('external_id', $key)->first();
if ($p && $p->latitude && $p->longitude) {
    echo "--- DB: same coordinates (±0.0005) ---\n";
    $near = DB::table('re_properties')
        ->whereBetween('latitude', [(float)$p->latitude - 0.0005, (float)$p->latitude + 0.0005])
        ->whereBetween('longitude', [(float)$p->longitude - 0.0005, (float)$p->longitude + 0.0005])
        ->orderByDesc('listing_contract_date')
        ->get(['external_id', 'MlsStatus', 'name', 'listing_contract_date', 'price']);
    foreach ($near as $r) {
        echo "  {$r->external_id} | {$r->MlsStatus} | {$r->name}\n";
    }
    echo "Count: {$near->count()}\n\n";
}

// 3. AMP endpoints
$ampQueries = [
    'Property by address' => "contains(UnparsedAddress,'222 Simmons Boulevard')",
    'Property street' => "StreetNumber eq '222' and StreetName eq 'Simmons'",
    'Property sold' => "StreetNumber eq '222' and StreetName eq 'Simmons' and MlsStatus eq 'Sold'",
    'Property expired' => "StreetNumber eq '222' and StreetName eq 'Simmons' and MlsStatus eq 'Expired'",
    'Property terminated' => "StreetNumber eq '222' and StreetName eq 'Simmons' and MlsStatus eq 'Terminated'",
    'Property all statuses' => "StreetNumber eq '222' and contains(StreetName,'Simmons')",
    'HistoryTransactional listing' => "ListingKey eq 'W13024458'",
    'HistoryTransactional address' => "contains(UnparsedAddress,'222 Simmons')",
];

foreach ($ampQueries as $label => $filter) {
    $entity = str_contains($label, 'HistoryTransactional') ? 'HistoryTransactional' : 'Property';
    $url = "https://query.ampre.ca/odata/{$entity}?\$filter=" . rawurlencode($filter)
        . '&$top=30&$orderby=ListingContractDate desc'
        . '&$select=' . rawurlencode('ListingKey,MlsStatus,TransactionType,ListingContractDate,CloseDate,ListPrice,ClosePrice,UnparsedAddress');
    $payload = TrebPropertyHelper::ampGetFresh($url, 15, 1);
    $count = count($payload['value'] ?? []);
    echo "--- AMP {$label}: {$count} ---\n";
    foreach ($payload['value'] ?? [] as $v) {
        echo "  {$v['ListingKey']} | {$v['MlsStatus']} | " . ($v['ListingContractDate'] ?? '') . " | " . ($v['UnparsedAddress'] ?? '') . "\n";
    }
}

// 4. Try Property with StandardStatus Closed/Withdrawn
echo "\n--- AMP StandardStatus filters ---\n";
foreach (['Closed', 'Withdrawn', 'Canceled', 'Expired'] as $status) {
    $filter = "StreetNumber eq '222' and contains(StreetName,'Simmons') and StandardStatus eq '{$status}'";
    $url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter) . '&$top=10';
    $payload = TrebPropertyHelper::ampGetFresh($url, 12, 1);
    $count = count($payload['value'] ?? []);
    if ($count > 0) {
        echo "StandardStatus={$status}: {$count}\n";
        foreach ($payload['value'] ?? [] as $v) {
            echo "  {$v['ListingKey']} | {$v['MlsStatus']}\n";
        }
    }
}

// 5. Search DB for any listing that might be same parcel - Brampton L6V 3Y1 block
echo "\n--- DB: postal L6V 3Y1 + Simmons ---\n";
$postal = DB::table('re_properties')
    ->where('zip_code', 'like', 'L6V 3Y1%')
    ->where('name', 'like', '%Simmons%')
    ->orderByDesc('listing_contract_date')
    ->limit(30)
    ->get(['external_id', 'MlsStatus', 'name']);
foreach ($postal as $r) {
    echo "  {$r->external_id} | {$r->MlsStatus} | {$r->name}\n";
}
