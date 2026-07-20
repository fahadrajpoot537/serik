<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Botble\RealEstate\Http\Controllers\API\PropertyController;
use Illuminate\Support\Facades\Http;
use Theme\homzen\Supports\TrebPropertyHelper;

$token = TrebPropertyHelper::ampTokens()[0] ?? '';
$headers = [
    'Authorization' => 'Bearer ' . $token,
    'Accept' => 'application/json',
    'OData-Version' => '4.0',
    'OData-MaxVersion' => '4.0',
];

$residential = "PropertySubType ne 'Industrial' and PropertySubType ne 'Commercial Retail'";

$filters = [
    'mod_2025' => "ModificationTimestamp ge 2025-01-01T00:00:00Z and ModificationTimestamp le 2025-12-31T23:59:59Z and {$residential}",
    'orig_2024' => "OriginalEntryTimestamp ge 2024-01-01T00:00:00Z and OriginalEntryTimestamp le 2024-12-31T23:59:59Z and {$residential}",
    'orig_2020' => "OriginalEntryTimestamp ge 2020-01-01T00:00:00Z and OriginalEntryTimestamp le 2020-12-31T23:59:59Z and {$residential}",
    'active' => "StandardStatus eq 'Active' and ModificationTimestamp ge 2025-01-01T00:00:00Z and {$residential}",
    'sold_status' => "MlsStatus eq 'Sold' and ModificationTimestamp ge 2020-01-01T00:00:00Z and {$residential}",
    'sold_cond' => "MlsStatus eq 'Sold Conditional' and ModificationTimestamp ge 2020-01-01T00:00:00Z and {$residential}",
    'cron_mod' => "ModificationTimestamp ge 2025-06-01T00:00:00Z and {$residential}",
];

foreach ($filters as $name => $filter) {
    $url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter)
        . '&$top=3&$select=ListingKey,ModificationTimestamp,OriginalEntryTimestamp,MlsStatus,StandardStatus';
    $r = Http::timeout(45)->withHeaders($headers)->get($url);
    $count = count($r->json('value') ?? []);
    $err = $r->json('error.message') ?? '';
    echo "{$name}: HTTP {$r->status()} count={$count}" . ($err ? " err={$err}" : '') . PHP_EOL;
}

$controller = app(PropertyController::class);
$r = $controller->importHistoricalAmpPage(2025, 0, 'modification', 5);
echo 'importHistorical modification: ' . json_encode($r) . PHP_EOL;

try {
    $sold = $controller->syncRecentSoldListings(\Illuminate\Http\Request::create('/', 'GET', ['days' => 30]));
    echo 'syncRecentSold 30d: ' . $sold->getContent() . PHP_EOL;
} catch (\Throwable $e) {
    echo 'syncRecentSold error: ' . $e->getMessage() . PHP_EOL;
}
