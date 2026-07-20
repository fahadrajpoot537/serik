<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = app(\Botble\RealEstate\Http\Controllers\API\PropertyController::class);

foreach (['modification', 'original_entry', 'sold_mls', 'active'] as $filter) {
    $result = $controller->importHistoricalAmpPage(2024, 0, $filter, 5);
    echo $filter . ': ' . json_encode($result) . PHP_EOL;
}
