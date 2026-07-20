<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$indexes = DB::select("SHOW INDEX FROM re_properties WHERE Column_name IN ('external_id', 'unique_id', 'id')");
echo "Indexes on external_id / unique_id / id:\n";
foreach ($indexes as $idx) {
    echo "  {$idx->Key_name} on {$idx->Column_name} (Non_unique={$idx->Non_unique})\n";
}

$count = DB::table('re_properties')->count();
echo "\nRow count: {$count}\n";

$start = microtime(true);
DB::table('re_properties')->where('external_id', 'W13169286')->first();
echo 'Lookup by external_id: ' . round(microtime(true) - $start, 4) . "s\n";

$start = microtime(true);
\Botble\RealEstate\Models\Property::firstOrNew(['external_id' => 'W13169286']);
echo 'firstOrNew external_id: ' . round(microtime(true) - $start, 4) . "s\n";
