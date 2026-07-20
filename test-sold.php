<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$token = Theme\homzen\Supports\TrebPropertyHelper::ampTokens()[0] ?? '';
$dt = now()->subDays(30)->format('Y-m-d') . 'T00:00:00Z';
foreach (["contains(City,'Bram')", "contains(UnparsedAddress,'Brampton')", "startswith(PostalCode,'L6')"] as $geo) {
    $f = "ModificationTimestamp ge {$dt} and MlsStatus eq 'Sold' and {$geo}";
    $url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode($f) . '&$top=2&$count=true';
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Accept: application/json','OData-Version: 4.0']]);
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);
    echo ($data['@odata.count']??0) . " | $f\n";
}
