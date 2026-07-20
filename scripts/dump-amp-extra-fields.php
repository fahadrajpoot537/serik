<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$u = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode("ListingKey eq 'W13024458'") . '&$top=1';
$p = Theme\homzen\Supports\TrebPropertyHelper::ampGetFresh($u);
$r = $p['value'][0] ?? [];
foreach ($r as $k => $v) {
    if (stripos($k, 'wash') !== false || stripos($k, 'Water') !== false || stripos($k, 'Day') !== false || stripos($k, 'Drive') !== false || stripos($k, 'Structure') !== false || stripos($k, 'Exterior') !== false) {
        echo $k . ': ' . (is_array($v) ? json_encode($v) : $v) . "\n";
    }
}
