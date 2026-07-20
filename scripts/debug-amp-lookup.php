<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Theme\homzen\Supports\TrebPropertyHelper;

$listingKey = $argv[1] ?? 'W13024458';
$filter = "ListingKey eq '{$listingKey}'";
$url = 'https://query.ampre.ca/odata/Property?'
    . '$filter=' . rawurlencode($filter)
    . '&$select=ListingKey,ListPrice,CoveredSpaces,ParkingSpaces,BedroomsAboveGrade,BedroomsBelowGrade,BedroomsTotal,ArchitecturalStyle,StoriesTotal,Levels,KitchensTotal,StandardStatus,MlsStatus';

echo "=== AMP OData Diagnostic ===\n";
echo "ListingKey: {$listingKey}\n";
echo "Filter: {$filter}\n";
echo "URL: {$url}\n\n";

echo "Config cached: " . (file_exists(base_path('bootstrap/cache/config.php')) ? 'yes' : 'no') . "\n";
echo "config(treb.auth) vendor: ";
$auth = config('treb.auth');
if ($auth) {
    $p = json_decode(base64_decode(strtr(explode('.', $auth)[1], '-_', '+/')), true);
    echo ($p['sub'] ?? '?') . " jti=" . ($p['jti'] ?? '?') . "\n";
} else {
    echo "EMPTY\n";
}

$tokens = TrebPropertyHelper::ampTokens();
echo "ampTokens count: " . count($tokens) . "\n\n";

foreach ($tokens as $i => $token) {
    $p = json_decode(base64_decode(strtr(explode('.', $token)[1], '-_', '+/')), true);
    echo "--- Token " . ($i + 1) . " ({$p['sub']}, jti={$p['jti']}) ---\n";

    try {
        $response = Http::timeout(15)
            ->connectTimeout(5)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'OData-Version' => '4.0',
                'OData-MaxVersion' => '4.0',
            ])
            ->get($url);

        echo "HTTP Status: " . $response->status() . "\n";
        $body = $response->body();
        echo "Response body (first 1500 chars):\n" . substr($body, 0, 1500) . "\n";

        $json = $response->json();
        $count = is_array($json['value'] ?? null) ? count($json['value']) : 0;
        echo "value[] count: {$count}\n";
        if ($count > 0) {
            echo "HIT: " . json_encode($json['value'][0], JSON_PRETTY_PRINT) . "\n";
        }
    } catch (Throwable $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// Also test without $select and with StandardStatus filter
$tests = [
    'no select' => 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter) . '&$top=1',
    'active only' => 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode("ListingKey eq '{$listingKey}' and StandardStatus eq 'Active'") . '&$top=1',
];

$token = $tokens[0] ?? '';
foreach ($tests as $label => $testUrl) {
    echo "--- Alt test: {$label} ---\n";
    echo "URL: {$testUrl}\n";
    $r = Http::timeout(15)->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
        'OData-Version' => '4.0',
        'OData-MaxVersion' => '4.0',
    ])->get($testUrl);
    echo "Status: " . $r->status() . "\n";
    echo "Body: " . substr($r->body(), 0, 500) . "\n\n";
}

// Test ampGet wrapper
echo "--- TrebPropertyHelper::ampGet() ---\n";
$result = TrebPropertyHelper::ampGet($url, 15);
echo "ampGet returned: " . (is_array($result) ? 'array with ' . count($result['value'] ?? []) . ' records' : 'null') . "\n";

echo "--- fetchAmpBackfillRecord() ---\n";
$record = TrebPropertyHelper::fetchAmpBackfillRecord($listingKey);
echo "fetchAmpBackfillRecord: " . (is_array($record) ? json_encode($record) : 'null') . "\n";
