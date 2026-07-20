<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;
use Illuminate\Support\Facades\DB;

$key = $argv[1] ?? 'W13024458';

$record = TrebPropertyHelper::resolveFactRecordForDetail($key);
$ref = new ReflectionClass(TrebPropertyHelper::class);

$mBuilding = $ref->getMethod('fetchBuildingPropertyRecords');
$mBuilding->setAccessible(true);
$building = $mBuilding->invoke(null, $record);

echo "Building AMP records: " . count($building) . "\n";
foreach ($building as $v) {
    echo "  {$v['ListingKey']} | {$v['MlsStatus']} | " . ($v['ListingContractDate'] ?? '') . "\n";
}

// DB all at postal
$postal = str_replace(' ', '', $record['PostalCode'] ?? '');
if ($postal) {
    $rows = DB::table('re_properties')
        ->whereRaw("REPLACE(UPPER(COALESCE(zip_code, '')), ' ', '') = ?", [strtoupper($postal)])
        ->orderByDesc('listing_contract_date')
        ->get(['external_id', 'MlsStatus', 'name', 'listing_contract_date', 'close_date', 'expire_date', 'price', 'ClosePrice']);
    echo "\nDB by postal {$postal}: " . $rows->count() . "\n";
    foreach ($rows as $r) {
        echo "  {$r->external_id} | {$r->MlsStatus} | {$r->listing_contract_date} | close={$r->close_date} | {$r->price}\n";
    }
}
