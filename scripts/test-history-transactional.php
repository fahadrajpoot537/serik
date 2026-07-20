<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;

$urls = [
    'https://query.ampre.ca/odata/HistoryTransactional?$filter=' . rawurlencode("ListingKey eq 'W13024458'") . '&$top=20',
    'https://query.ampre.ca/odata/HistoryTransactional?$filter=' . rawurlencode("ResourceRecordKey eq 'W13024458'") . '&$top=20',
    'https://query.ampre.ca/odata/HistoryTransactional?$filter=' . rawurlencode("StreetNumber eq '222' and contains(StreetName,'Simmons')") . '&$top=20',
];

foreach ($urls as $url) {
    $payload = TrebPropertyHelper::ampGetFresh($url, 12, 1);
    $count = isset($payload['value']) ? count($payload['value']) : 0;
    echo substr($url, 0, 100) . PHP_EOL;
    echo "  count={$count}" . PHP_EOL;
    if (! empty($payload['value'][0])) {
        echo '  keys: ' . implode(', ', array_slice(array_keys($payload['value'][0]), 0, 15)) . PHP_EOL;
        echo '  sample: ' . json_encode($payload['value'][0], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
    if (! empty($payload['error'])) {
        echo '  error: ' . json_encode($payload['error']) . PHP_EOL;
    }
}
