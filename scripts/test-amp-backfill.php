<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Botble\RealEstate\Http\Controllers\API\PropertyController;
use Theme\homzen\Supports\TrebPropertyHelper;

$controller = app(PropertyController::class);
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('ampCurl');
$method->setAccessible(true);

$keys = ['X12429428', 'W13024458'];

foreach ($keys as $k) {
    $url = 'https://query.ampre.ca/odata/Property?$filter='
        . rawurlencode("ListingKey eq '{$k}'")
        . '&$select=ListingKey,ListPrice,CoveredSpaces';
    $p1 = TrebPropertyHelper::ampGet($url);
    $p2 = $method->invoke($controller, $url, 15);
    echo "{$k} ampGet=" . count($p1['value'] ?? []) . ' ampCurl=' . count($p2['value'] ?? []) . PHP_EOL;
    if (! empty($p2['error'])) {
        echo '  error=' . json_encode($p2['error']) . PHP_EOL;
    }
}

echo 'tokens=' . count(TrebPropertyHelper::ampTokens()) . PHP_EOL;
