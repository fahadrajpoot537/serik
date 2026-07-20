<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;
use Illuminate\Support\Facades\DB;

$roll = '211009004109701';
$key = 'W13024458';

echo "=== ROLL NUMBER HISTORY SEARCH: {$roll} ===\n\n";

$filters = [
    "RollNumber eq '{$roll}'",
    "contains(RollNumber,'{$roll}')",
];

foreach ($filters as $filter) {
    $url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter)
        . '&$top=50&$orderby=ListingContractDate desc'
        . '&$select=' . rawurlencode('ListingKey,MlsStatus,TransactionType,ListingContractDate,CloseDate,ExpirationDate,TerminatedDate,ListPrice,ClosePrice,UnparsedAddress,RollNumber');
    $p = TrebPropertyHelper::ampGetFresh($url, 20, 2);
    echo "Filter: {$filter}\n";
    echo "Count: " . count($p['value'] ?? []) . "\n";
    if (!empty($p['error'])) echo "Error: " . json_encode($p['error']) . "\n";
    foreach ($p['value'] ?? [] as $v) {
        echo "  {$v['ListingKey']} | {$v['MlsStatus']} | start=" . ($v['ListingContractDate'] ?? '') . " | close=" . ($v['CloseDate'] ?? '') . " | \${$v['ListPrice']}\n";
    }
    echo "\n";
}

// Get roll from current listing raw
$url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode("ListingKey eq '{$key}'") . '&$top=1&$select=RollNumber,TaxLegalDescription';
$p = TrebPropertyHelper::ampGetFresh($url, 15, 2);
echo "Current listing RollNumber: " . ($p['value'][0]['RollNumber'] ?? 'null') . "\n";
