<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode("ListingKey eq 'W13024458'") . '&$top=1';
$p = Theme\homzen\Supports\TrebPropertyHelper::ampGetFresh($u);
$r = $p['value'][0] ?? [];
foreach (['PropertyType','PropertySubType','StructureType','ArchitecturalStyle','WaterSource','DaysOnMarket','CumulativeDaysOnMarket','SourceSystemName','OriginatingSystemName','Driveway','ExteriorFeatures'] as $f) {
    $v = $r[$f] ?? 'MISSING';
    echo $f . ': ' . (is_array($v) ? json_encode($v) : $v) . "\n";
}

// siblings at address
$rows = DB::table('re_properties')
    ->where('name', 'like', '%222 Simmons%')
    ->get(['external_id','MlsStatus','price','listing_contract_date']);
echo "\nDB siblings:\n";
foreach ($rows as $row) {
    echo json_encode($row) . "\n";
}
