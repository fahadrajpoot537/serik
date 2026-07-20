<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;

$key = 'W13024458';
$local = TrebPropertyHelper::localPropertyArray($key);
$record = TrebPropertyHelper::ensureAmpRecord($key, $local);
if (! $record && $local) {
    $record = TrebPropertyHelper::enrichRecordAddress(TrebPropertyHelper::recordFromLocal($local, $key));
}

echo "DB number_bedroom=" . DB::table('re_properties')->where('external_id', $key)->value('number_bedroom') . PHP_EOL;
echo "bedrooms_label=" . TrebPropertyHelper::formatBedroomLabel($record, $local) . PHP_EOL;
echo "style=" . TrebPropertyHelper::formatArchitecturalStyle($record, $local) . PHP_EOL;
echo "garage=" . (TrebPropertyHelper::buildPropertyDetails($record, $local)['garage'] ?? '-') . PHP_EOL;
echo "parking_places=" . (TrebPropertyHelper::buildPropertyDetails($record, $local)['parking_places'] ?? '-') . PHP_EOL;
echo "size=" . (TrebPropertyHelper::buildKeyFacts($record, $local)['size'] ?? '-') . PHP_EOL;

$history = TrebPropertyHelper::fetchListingHistory($key, $local);
echo "history_count=" . count($history) . PHP_EOL;
foreach ($history as $row) {
    echo '  - ' . ($row['event'] ?? '-') . ' | ' . ($row['listing_id'] ?? '-') . ' | ' . ($row['price'] ?? '-') . PHP_EOL;
}
