<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\View;

Theme::setThemeName('homzen');
$app['config']->set('app.theme', 'homzen');

$html = View::file(
    base_path('platform/themes/homzen/partials/shortcodes/hero-banner/styles/style-4.blade.php'),
    [
        'shortcode' => (object) [
            'style' => 4,
            'search_box_enabled' => true,
        ],
        'isMapSearchPageView' => true,
    ]
)->render();

echo 'length=' . strlen($html) . PHP_EOL;

$lines = explode("\n", $html);
for ($i = 7394; $i <= 7404 && $i < count($lines); $i++) {
    echo ($i + 1) . ': ' . $lines[$i] . PHP_EOL;
}
