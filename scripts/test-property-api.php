<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/v1/getPropertyDetails/W13024458', 'GET');
$response = $kernel->handle($request);
$body = json_decode($response->getContent(), true);

echo $response->getStatusCode() . PHP_EOL;
echo substr($response->getContent(), 0, 2000) . PHP_EOL;

$kernel->terminate($request, $response);
