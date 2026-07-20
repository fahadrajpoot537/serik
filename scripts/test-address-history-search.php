<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;
use Illuminate\Support\Facades\DB;

$filters = [
    "StreetNumber eq '222' and contains(StreetName,'Simmons')",
    "contains(UnparsedAddress,'222 Simmons')",
    "contains(UnparsedAddress,'222 Simmons Boulevard')",
];

foreach ($filters as $filter) {
    $url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter)
        . '&$orderby=ListingContractDate desc&$top=50'
        . '&$select=' . rawurlencode('ListingKey,ListPrice,MlsStatus,TransactionType,ListingContractDate,UnparsedAddress,CloseDate,ExpirationDate');
    $payload = TrebPropertyHelper::ampGetFresh($url, 12, 1);
    $values = $payload['value'] ?? [];
    echo "Filter: {$filter}" . PHP_EOL;
    echo '  count=' . count($values) . PHP_EOL;
    foreach ($values as $v) {
        echo '  ' . ($v['ListingKey'] ?? '?') . ' | ' . ($v['MlsStatus'] ?? '?') . ' | ' . ($v['ListingContractDate'] ?? '?') . ' | ' . ($v['UnparsedAddress'] ?? '') . PHP_EOL;
    }
}

echo PHP_EOL . 'Broader DB search:' . PHP_EOL;
$rows = DB::table('re_properties')
    ->where('name', 'like', '%Simmons%')
    ->where('name', 'like', '%222%')
    ->orderByDesc('created_at')
    ->get(['external_id', 'name', 'MlsStatus', 'created_at']);
foreach ($rows as $r) {
    echo $r->external_id . ' | ' . $r->MlsStatus . ' | ' . $r->name . PHP_EOL;
}
