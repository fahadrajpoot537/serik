<?php

/**
 * Check whether the AMPRE OData Property feed returns coordinate fields.
 * Usage: php scripts/test-amp-coords.php [MLS_ID]
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;

$key = trim($argv[1] ?? 'W13546464');

$tokens = TrebPropertyHelper::ampTokens();
if (! $tokens) {
    exit("No AMP tokens configured (TRREB_AUTH / treb.auth).\n");
}
$token = $tokens[0];

function ampGet(string $url, string $token): array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'OData-Version: 4.0',
        ],
    ]);
    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$code, json_decode($res, true), $res];
}

// 1) Full record (no $select) so we can see every field AMP returns.
$filter = rawurlencode("ListingKey eq '{$key}'");
$url = "https://query.ampre.ca/odata/Property?\$filter={$filter}&\$top=1";
[$code, $json, $raw] = ampGet($url, $token);

echo "HTTP {$code}\n";
if (! is_array($json) || empty($json['value'][0])) {
    echo "No record / raw response:\n" . substr((string) $raw, 0, 500) . "\n";
    // Try any single active listing to inspect available fields.
    $url2 = "https://query.ampre.ca/odata/Property?\$top=1";
    [$c2, $j2] = ampGet($url2, $token);
    echo "Fallback single-record HTTP {$c2}\n";
    if (is_array($j2) && ! empty($j2['value'][0])) {
        $json = $j2;
    } else {
        exit;
    }
}

$rec = $json['value'][0];
$fields = array_keys($rec);
sort($fields);

echo "\nTotal fields returned: " . count($fields) . "\n";

$coordKeys = array_values(array_filter($fields, fn ($f) => stripos($f, 'lat') !== false
    || stripos($f, 'long') !== false
    || stripos($f, 'coord') !== false
    || stripos($f, 'geo') !== false));

echo "\nCoordinate-like fields: " . (implode(', ', $coordKeys) ?: '(none)') . "\n";

foreach (['Latitude', 'Longitude'] as $f) {
    echo "  {$f} = " . var_export($rec[$f] ?? '(absent)', true) . "\n";
}

echo "\nAll field names:\n" . implode(', ', $fields) . "\n";
