<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;

$filters = [
    "contains(UnparsedAddress,'222') and contains(UnparsedAddress,'Simmons') and contains(UnparsedAddress,'Brampton')",
    "contains(UnparsedAddress,'222 Simmons')",
    "PostalCode eq 'L6V 3Y1'",
];

foreach ($filters as $filter) {
    $url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter)
        . '&$top=50&$orderby=ListingContractDate desc'
        . '&$select=' . rawurlencode('ListingKey,MlsStatus,StandardStatus,ListingContractDate,CloseDate,UnparsedAddress,ModificationTimestamp');
    $p = TrebPropertyHelper::ampGetFresh($url, 15, 2);
    echo "Filter: {$filter}\n  count=" . count($p['value'] ?? []) . "\n";
    foreach ($p['value'] ?? [] as $v) {
        if (stripos($v['UnparsedAddress'] ?? '', '222') !== false && stripos($v['UnparsedAddress'] ?? '', 'Simmons') !== false) {
            echo "  {$v['ListingKey']} | {$v['MlsStatus']} | {$v['StandardStatus']} | {$v['UnparsedAddress']}\n";
        }
    }
}

// OData entity probe
foreach (['ListingHistory', 'PropertyHistory', 'History', 'SoldProperty', 'ClosedProperty'] as $entity) {
    $url = "https://query.ampre.ca/odata/{$entity}?\$top=1";
    $p = TrebPropertyHelper::ampGetFresh($url, 8, 1);
    $count = is_array($p) ? count($p['value'] ?? []) : -1;
    echo "Entity {$entity}: " . ($count >= 0 ? $count : 'error') . "\n";
}
