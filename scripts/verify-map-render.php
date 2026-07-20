<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/map', 'GET');
$response = $kernel->handle($request);
$html = $response->getContent();

echo (str_contains($html, 'center: ,') ? 'BROKEN center' : 'OK center') . PHP_EOL;
echo (str_contains($html, 'center: [detectedLocation.lng') ? 'OK detectedLocation array' : 'missing array') . PHP_EOL;

if (preg_match('/visitor-location[^"\']*/', $html, $m)) {
    echo 'visitor script snippet: ' . substr($m[0], 0, 100) . PHP_EOL;
}

preg_match_all('/<script[^>]+src="([^"]+)"/', $html, $scripts);
foreach ($scripts[1] as $s) {
    if (preg_match('#^https?://[^/]+/?$#', $s) || $s === '/') {
        echo 'BAD SCRIPT SRC: ' . $s . PHP_EOL;
    }
}

foreach (array_slice($scripts[1], 0, 15) as $s) {
    if (str_contains($s, 'visitor') || str_contains($s, 'maplibre') || str_contains($s, 'homzen')) {
        echo 'script: ' . $s . PHP_EOL;
    }
}

if (preg_match('/<script[^>]*>([\s\S]*?)<\/script>/', $html, $inline, PREG_OFFSET_CAPTURE)) {
    // find the big map script - search for flyMapToDetectedLocation
    if (preg_match('/<script[^>]*>([\s\S]*?flyMapToDetectedLocation[\s\S]*?)<\/script>/', $html, $mapScript)) {
        $js = $mapScript[1];
        $tmp = tempnam(sys_get_temp_dir(), 'mapjs');
        file_put_contents($tmp, $js);
        passthru('node --check ' . escapeshellarg($tmp) . ' 2>&1', $code);
        unlink($tmp);
        echo 'node --check exit: ' . $code . PHP_EOL;
    }
}
