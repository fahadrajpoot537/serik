<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Theme\homzen\Supports\TrebPropertyHelper;

$token = TrebPropertyHelper::ampTokens()[0];
$headers = ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json', 'OData-Version' => '4.0'];
$res = "PropertySubType ne 'Industrial' and PropertySubType ne 'Commercial Retail'";

$tests = [
    'mod2024 le' => "ModificationTimestamp ge 2024-01-01T00:00:00Z and ModificationTimestamp le 2024-12-31T23:59:59Z and {$res}",
    'mod2024 lt' => "ModificationTimestamp ge 2024-01-01T00:00:00Z and ModificationTimestamp lt 2025-01-01T00:00:00Z and {$res}",
    'orig2024 le' => "OriginalEntryTimestamp ge 2024-01-01T00:00:00Z and OriginalEntryTimestamp le 2024-12-31T23:59:59Z and {$res}",
    'mod2025 le' => "ModificationTimestamp ge 2025-01-01T00:00:00Z and ModificationTimestamp le 2025-12-31T23:59:59Z and {$res}",
];

foreach ($tests as $name => $filter) {
    $url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter) . '&$top=3&$select=ListingKey';
    $r = Http::timeout(45)->withHeaders($headers)->get($url);
    echo "$name: " . $r->status() . ' count=' . count($r->json('value') ?? []) . PHP_EOL;
}

$controller = app(\Botble\RealEstate\Http\Controllers\API\PropertyController::class);
echo 'import 2025 mod: ' . json_encode($controller->importHistoricalAmpPage(2025, 0, 'modification', 5)) . PHP_EOL;
