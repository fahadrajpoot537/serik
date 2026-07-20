<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;

$key = strtoupper(trim($argv[1] ?? 'E13169304'));
$local = TrebPropertyHelper::localPropertyArray($key);
$fact = TrebPropertyHelper::resolveFactRecordForDetail($key, $local);
$details = TrebPropertyHelper::buildPropertyDetails($fact, $local);
$hist = TrebPropertyHelper::fetchListingHistoryForDetail($key, $local, $fact);

echo "LISTING: {$key}\n";
echo "Fact keys: " . count($fact) . "\n";
echo "City={$fact['City']} CityRegion={$fact['CityRegion']} HeatType=" . ($fact['HeatType'] ?? '') . "\n";
echo "PetsAllowed=" . json_encode($fact['PetsAllowed'] ?? null) . "\n";
echo "\nDetails:\n";
foreach ($details as $k => $v) {
    if ($v !== null && $v !== '' && $v !== '-' && $v !== []) {
        echo "  {$k}: " . (is_array($v) ? json_encode($v) : $v) . "\n";
    }
}
echo "\nHistory: " . count($hist) . "\n";
foreach ($hist as $row) {
    echo '  ' . json_encode($row) . "\n";
}
echo "\nRooms: " . count(TrebPropertyHelper::fetchPropertyRoomsForDetail($key)) . "\n";
