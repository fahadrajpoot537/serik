<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;

$key = 'W13024458';
$local = TrebPropertyHelper::localPropertyArray($key);
$record = TrebPropertyHelper::fetchAmpPropertyForResync($key) ?: TrebPropertyHelper::recordFromLocal($local, $key);

echo "=== Key Facts ===\n";
print_r(TrebPropertyHelper::buildKeyFacts($record, $local));

echo "\n=== Property Details ===\n";
print_r(TrebPropertyHelper::buildPropertyDetails($record, $local));

echo "\n=== History ===\n";
foreach (TrebPropertyHelper::fetchListingHistory($key, $local) as $row) {
    echo json_encode($row) . "\n";
}

echo "\n=== Rooms count ===\n";
echo count(TrebPropertyHelper::fetchPropertyRooms($key)) . "\n";
