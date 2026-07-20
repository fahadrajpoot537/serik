<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Theme\homzen\Supports\TrebPropertyHelper;

$listingKey = $argv[1] ?? 'W13169286';
$select = TrebPropertyHelper::propertyDetailSelectFields();
$tokens = TrebPropertyHelper::ampTokens();
$headers = [
    'Authorization' => 'Bearer ' . $tokens[0],
    'Accept' => 'application/json',
    'OData-Version' => '4.0',
    'OData-MaxVersion' => '4.0',
];

foreach (['encoded', 'raw'] as $mode) {
    $selectPart = $mode === 'encoded' ? rawurlencode($select) : $select;
    $url = 'https://query.ampre.ca/odata/Property?'
        . '$filter=' . rawurlencode("ListingKey eq '{$listingKey}'")
        . '&$top=1'
        . '&$select=' . $selectPart;

    echo "=== \$select {$mode} (URL len " . strlen($url) . ") ===\n";
    $response = Http::timeout(30)->withHeaders($headers)->get($url);
    echo "HTTP: {$response->status()}\n";
    echo substr($response->body(), 0, 400) . "\n\n";
}

$url = 'https://query.ampre.ca/odata/Property?'
    . '$filter=' . rawurlencode("ListingKey eq '{$listingKey}'")
    . '&$top=1'
    . '&$select=' . $select;

echo "ampGetFresh: ";
$result = TrebPropertyHelper::ampGetFresh($url);
echo is_array($result) ? 'array, count=' . count($result['value'] ?? []) : 'null';
echo "\n\n";

echo "fetchAmpPropertyForResync: ";
$record = TrebPropertyHelper::fetchAmpPropertyForResync($listingKey);
echo is_array($record) ? ($record['ListingKey'] ?? 'no key') : 'null';
echo "\n";
