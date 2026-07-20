<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$select = Theme\homzen\Supports\TrebPropertyHelper::propertyDetailSelectFields();
$fields = explode(',', $select);
$token = config('treb.auth');
$key = 'W13024458';
$filter = rawurlencode("ListingKey eq '{$key}'");

foreach ($fields as $field) {
    $field = trim($field);
    $url = "https://query.ampre.ca/odata/Property?\$filter={$filter}&\$select={$field}&\$top=1";
    $r = Illuminate\Support\Facades\Http::timeout(10)->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
        'OData-Version' => '4.0',
        'OData-MaxVersion' => '4.0',
    ])->get($url);
    if (!$r->successful()) {
        echo "FAIL {$field}: " . ($r->json('error.message') ?? $r->status()) . "\n";
    }
}
echo "done\n";
