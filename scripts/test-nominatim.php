<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$address = $argv[1] ?? '1720 Eglinton Avenue E, Toronto, ON, Canada';

$r = Http::withHeaders([
    'User-Agent' => config('services.nominatim.user_agent'),
    'Accept' => 'application/json',
])->get(config('services.nominatim.url'), [
    'q' => $address,
    'format' => 'jsonv2',
    'limit' => 1,
    'countrycodes' => 'ca',
]);

echo 'Address: ' . $address . "\n";
echo 'HTTP ' . $r->status() . "\n";
$d = $r->json();
if (isset($d[0]['lat'])) {
    echo 'lat=' . $d[0]['lat'] . '  lon=' . $d[0]['lon'] . "\n";
    echo 'display_name: ' . ($d[0]['display_name'] ?? '') . "\n";
} else {
    echo "No result. Body: " . substr($r->body(), 0, 300) . "\n";
}
