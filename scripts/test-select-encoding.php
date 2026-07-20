<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;

$residential = rawurlencode("PropertySubType ne 'Industrial' and PropertySubType ne 'Commercial Retail'");
$filter = rawurlencode("ModificationTimestamp ge 2024-01-01T00:00:00Z and ModificationTimestamp lt 2025-01-01T00:00:00Z and PropertySubType ne 'Industrial' and PropertySubType ne 'Commercial Retail'");
$select = 'ListingKey,UnparsedAddress,MlsStatus,ModificationTimestamp';
$selectEnc = rawurlencode($select);

$urlPlain = "https://query.ampre.ca/odata/Property?\$filter={$filter}&\$select={$select}&\$top=3";
$urlEnc = "https://query.ampre.ca/odata/Property?\$filter={$filter}&\$select={$selectEnc}&\$top=3";

foreach (['plain select' => $urlPlain, 'encoded select' => $urlEnc] as $label => $url) {
    $r = TrebPropertyHelper::ampGet($url, 45);
    $count = is_array($r) ? count($r['value'] ?? []) : -1;
    $err = $r['error']['message'] ?? '';
    echo "{$label}: count={$count} err={$err}\n";
}
