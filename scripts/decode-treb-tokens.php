<?php
foreach (['TRREB_AUTH', 'TRREB_AUTH1'] as $name) {
    $line = null;
    foreach (file(__DIR__ . '/../.env') as $l) {
        if (str_starts_with(trim($l), $name . '=')) {
            $line = trim(substr($l, strlen($name) + 1));
            break;
        }
    }
    $payload = json_decode(base64_decode(strtr(explode('.', $line)[1], '-_', '+/')), true);
    echo $name . " JWT:\n";
    echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";
}

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$tokens = Theme\homzen\Supports\TrebPropertyHelper::ampTokens();
echo 'ampTokens() count=' . count($tokens) . PHP_EOL;
echo 'config(treb.auth) === env(TRREB_AUTH): ' . (config('treb.auth') === env('TRREB_AUTH') ? 'yes' : 'no') . PHP_EOL;
echo 'config(treb.auth) prefix: ' . substr((string) config('treb.auth'), 0, 24) . PHP_EOL;
echo 'env(TRREB_AUTH) prefix: ' . substr((string) env('TRREB_AUTH'), 0, 24) . PHP_EOL;
echo 'token[0] prefix: ' . substr($tokens[0] ?? '', 0, 24) . PHP_EOL;
echo 'token[1] prefix: ' . substr($tokens[1] ?? '', 0, 24) . PHP_EOL;
