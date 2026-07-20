<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;

$filters = [
    "contains(UnparsedAddress,'222 Simmons Boulevard')",
    "StreetNumber eq '222' and contains(StreetName,'Simmons')",
    "StandardStatus ne 'Active'",
    "MlsStatus eq 'Sold' and contains(UnparsedAddress,'222 Simmons')",
    "MlsStatus eq 'Expired' and contains(UnparsedAddress,'222 Simmons')",
];

foreach ($filters as $filter) {
    $url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter)
        . '&$top=20&$orderby=ListingContractDate desc'
        . '&$select=' . rawurlencode('ListingKey,MlsStatus,StandardStatus,ListingContractDate,CloseDate,ListPrice,UnparsedAddress');
    $p = TrebPropertyHelper::ampGetFresh($url, 12, 1);
    echo $filter . ' => ' . count($p['value'] ?? []) . "\n";
    foreach ($p['value'] ?? [] as $v) {
        echo "  {$v['ListingKey']} | {$v['MlsStatus']} | {$v['StandardStatus']} | {$v['UnparsedAddress']}\n";
    }
}
