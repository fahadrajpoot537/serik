<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = config('treb.auth');
$key = 'W13024458';
$filter = rawurlencode("ListingKey eq '{$key}'");

$fields = [
    'ListingKey', 'ListPrice', 'CoveredSpaces', 'ParkingSpaces',
    'BedroomsAboveGrade', 'BedroomsBelowGrade', 'BedroomsTotal',
    'ArchitecturalStyle', 'StoriesTotal', 'Levels', 'KitchensTotal',
];

foreach ($fields as $field) {
    $url = "https://query.ampre.ca/odata/Property?\$filter={$filter}&\$select={$field}&\$top=1";
    $r = Http::timeout(10)->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Accept' => 'application/json',
        'OData-Version' => '4.0',
        'OData-MaxVersion' => '4.0',
    ])->get($url);
    $ok = $r->successful() ? 'OK' : 'FAIL ' . $r->status();
    $msg = '';
    if (!$r->successful()) {
        $msg = ' - ' . ($r->json('error.message') ?? substr($r->body(), 0, 120));
    }
    echo "{$field}: {$ok}{$msg}\n";
}
