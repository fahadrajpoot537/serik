<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;
use Illuminate\Support\Facades\Cache;

$key = 'E13169304';

$searches = [
    "ListingKey eq '{$key}'",
    "ListingKey eq 'e13169304'",
    "contains(UnparsedAddress,'255 McLevin')",
    "StreetNumber eq '255' and contains(StreetName,'McLevin')",
    "UnitNumber eq '1' and StreetNumber eq '255'",
];

foreach ($searches as $filter) {
    $url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($filter) . '&$top=5';
    $p = TrebPropertyHelper::ampGetFresh($url, 12, 1);
    $count = count($p['value'] ?? []);
    echo "Filter: {$filter} => {$count}\n";
    foreach ($p['value'] ?? [] as $v) {
        echo "  {$v['ListingKey']} | {$v['MlsStatus']} | " . ($v['UnparsedAddress'] ?? '') . "\n";
    }
}

$cached = Cache::get('treb_property_record_v5_' . $key);
$raw = Cache::get('treb_property_record_raw_v1_' . $key);
echo "\nCache v5: " . ($cached ? 'YES (' . count($cached) . ' keys)' : 'NO') . "\n";
echo "Cache raw: " . ($raw ? 'YES (' . count($raw) . ' keys)' : 'NO') . "\n";

if ($cached) {
    echo "Cached CityRegion=" . ($cached['CityRegion'] ?? '') . " HeatType=" . ($cached['HeatType'] ?? '') . "\n";
}

// Rooms
$url = 'https://query.ampre.ca/odata/PropertyRooms?$filter=' . rawurlencode("ListingKey eq '{$key}'") . '&$top=5';
$rooms = TrebPropertyHelper::ampGetFresh($url, 12, 1);
echo "\nRooms count: " . count($rooms['value'] ?? []) . "\n";
