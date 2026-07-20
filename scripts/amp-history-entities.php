<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;

$key = 'W13024458';
$url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode("ListingKey eq '{$key}'") . '&$top=1';
$p = TrebPropertyHelper::ampGetFresh($url, 15, 2);
$r = $p['value'][0] ?? [];

echo "All AMP fields for {$key} (" . count($r) . "):\n";
$historyHints = [];
foreach ($r as $field => $val) {
    if ($val === null || $val === '' || $val === []) continue;
    if (preg_match('/history|prior|previous|sold|close|expire|terminate|parcel|roll|legal/i', $field)) {
        $historyHints[$field] = is_array($val) ? json_encode($val) : $val;
    }
}
print_r($historyHints);

$entities = ['HistoryTransactional', 'PropertyHistory', 'ListingHistory', 'TransactionHistory', 'PropertyRooms'];
foreach ($entities as $entity) {
    $u = "https://query.ampre.ca/odata/{$entity}?\$filter=" . rawurlencode("ListingKey eq '{$key}'") . '&$top=5';
    $res = TrebPropertyHelper::ampGetFresh($u, 10, 1);
    $c = is_array($res) ? count($res['value'] ?? []) : -1;
    echo "{$entity}: {$c}\n";
}
