<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;

$key = 'E13169304';

echo "=== E13169304 AMP Debug ===\n\n";

// Raw no select
$url1 = "https://query.ampre.ca/odata/Property?\$filter=" . rawurlencode("ListingKey eq '{$key}'") . '&$top=1';
$p1 = TrebPropertyHelper::ampGetFresh($url1, 15, 2);
echo "Raw (no select): count=" . count($p1['value'] ?? []) . "\n";
if (!empty($p1['value'][0])) {
    $r = $p1['value'][0];
    echo "  MlsStatus={$r['MlsStatus']} StandardStatus=" . ($r['StandardStatus'] ?? '') . "\n";
    echo "  CityRegion=" . ($r['CityRegion'] ?? '') . " City=" . ($r['City'] ?? '') . "\n";
    echo "  PetsAllowed=" . json_encode($r['PetsAllowed'] ?? null) . "\n";
    echo "  GarageType=" . ($r['GarageType'] ?? '') . " CoveredSpaces=" . ($r['CoveredSpaces'] ?? '') . "\n";
    echo "  ConstructionMaterials=" . json_encode($r['ConstructionMaterials'] ?? null) . "\n";
    echo "  Cooling=" . json_encode($r['Cooling'] ?? null) . "\n";
    echo "  CrossStreet=" . ($r['CrossStreet'] ?? '') . "\n";
}

// With select
$select = TrebPropertyHelper::propertyDetailSelectFields();
$url2 = "https://query.ampre.ca/odata/Property?\$filter=" . rawurlencode("ListingKey eq '{$key}'") . '&$top=1&$select=' . rawurlencode($select);
$p2 = TrebPropertyHelper::ampGetFresh($url2, 15, 2);
echo "\nWith select: count=" . count($p2['value'] ?? []) . " error=" . json_encode($p2['error'] ?? null) . "\n";

// Test each field batch to find bad field
$allFields = explode(',', $select);
$batches = array_chunk($allFields, 10);
foreach ($batches as $i => $batch) {
    $batchSelect = implode(',', $batch);
    $url = "https://query.ampre.ca/odata/Property?\$filter=" . rawurlencode("ListingKey eq '{$key}'") . '&$top=1&$select=' . rawurlencode($batchSelect);
    $p = TrebPropertyHelper::ampGetFresh($url, 10, 1);
    $ok = !empty($p['value']);
    echo "Batch {$i}: " . ($ok ? 'OK' : 'FAIL') . " fields=" . $batchSelect . "\n";
    if (!$ok) {
        // binary search within batch
        foreach ($batch as $field) {
            $u = "https://query.ampre.ca/odata/Property?\$filter=" . rawurlencode("ListingKey eq '{$key}'") . '&$top=1&$select=' . rawurlencode($field);
            $fp = TrebPropertyHelper::ampGetFresh($u, 10, 1);
            echo "  field {$field}: " . (!empty($fp['value']) ? 'OK' : 'FAIL') . "\n";
        }
    }
}

echo "\nfetchAmpPropertyForResync: " . (TrebPropertyHelper::fetchAmpPropertyForResync($key) ? 'OK' : 'NULL') . "\n";
echo "ensureAmpRecord: " . (TrebPropertyHelper::ensureAmpRecord($key) ? 'OK' : 'NULL') . "\n";
