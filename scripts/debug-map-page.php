<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$isMapSearchPageView = true;
$shortcode = (object) ['style' => 4, 'search_box_enabled' => true];

ob_start();
include storage_path('framework/views/1238c0edd740b0dc827d51418b6ae844.php');
$html = ob_get_clean();

echo (str_contains($html, 'center: ,') ? 'FOUND broken in view only' : 'view ok') . PHP_EOL;
echo (str_contains($html, '[location.lng, location.lat]') ? 'has array in view' : 'no array in view') . PHP_EOL;
