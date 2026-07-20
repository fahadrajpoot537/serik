<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'config cached: ' . (file_exists(base_path('bootstrap/cache/config.php')) ? 'yes' : 'no') . PHP_EOL;
echo 'env(TRREB_AUTH) empty: ' . (empty(env('TRREB_AUTH')) ? 'yes' : 'no') . PHP_EOL;
echo 'config(treb.auth) empty: ' . (empty(config('treb.auth')) ? 'yes' : 'no') . PHP_EOL;
