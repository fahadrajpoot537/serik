<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;

$url = 'https://query.ampre.ca/odata/Property?$filter='
    . rawurlencode("StreetNumber eq '255' and contains(StreetName,'McLevin')")
    . '&$top=10&$select=' . rawurlencode(TrebPropertyHelper::propertyDetailSelectFields());

$p = TrebPropertyHelper::ampGetFresh($url, 15, 2);
foreach ($p['value'] ?? [] as $v) {
    echo ($v['ListingKey'] ?? '?') . ' | unit=' . ($v['UnitNumber'] ?? '') . ' | ' . ($v['UnparsedAddress'] ?? '') . "\n";
    echo '  CityRegion=' . ($v['CityRegion'] ?? '') . ' HeatType=' . ($v['HeatType'] ?? '') . ' PetsAllowed=' . json_encode($v['PetsAllowed'] ?? null) . "\n";
    echo '  Construction=' . json_encode($v['ConstructionMaterials'] ?? null) . ' GarageType=' . ($v['GarageType'] ?? '') . "\n";
}
