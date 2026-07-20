<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$r = Theme\homzen\Supports\TrebPropertyHelper::fetchAmpPropertyForResync('W13024458');
echo $r ? 'HIT tax=' . ($r['TaxAnnualAmount'] ?? 'none') . ' lot=' . ($r['LotWidth'] ?? 'none') : 'MISSING';
