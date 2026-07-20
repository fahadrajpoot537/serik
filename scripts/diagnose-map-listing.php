<?php

/**
 * Diagnose why a listing is missing from Map Search.
 *
 * Usage (run on the LIVE server from project root):
 *   php scripts/diagnose-map-listing.php W13546464
 *
 * It checks every condition the map query applies and tells you the exact
 * reason a listing is excluded (missing coordinates, moderation, date, etc.).
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Botble\RealEstate\Models\Property;

$key = strtoupper(trim($argv[1] ?? ''));

if ($key === '') {
    echo "Usage: php scripts/diagnose-map-listing.php <MLS_ID>\n";
    exit(1);
}

$p = Property::where('external_id', $key)
    ->orWhere('external_id', strtolower($key))
    ->first();

if (! $p) {
    echo "[{$key}] NOT found in re_properties at all.\n";
    exit(0);
}

$onSouth = 41.6; $onNorth = 56.9; $onWest = -95.2; $onEast = -74.0;
$lat = (float) $p->latitude;
$lng = (float) $p->longitude;

echo "==== {$key} (id={$p->id}) ====\n";
echo "  moderation_status      = {$p->moderation_status}\n";
echo "  MlsStatus              = {$p->MlsStatus}\n";
echo "  PropertySubType        = {$p->PropertySubType}\n";
echo "  latitude               = {$p->latitude}\n";
echo "  longitude              = {$p->longitude}\n";
echo "  listing_contract_date  = {$p->listing_contract_date}\n";
echo "  listing_modified_at    = {$p->listing_modified_at}\n";
echo "  created_at             = {$p->created_at}\n";
echo "  updated_at             = {$p->updated_at}\n";
echo str_repeat('-', 55) . "\n";

$reasons = [];

$moderation = strtolower(trim((string) $p->moderation_status));
if ($moderation !== 'approved') {
    $reasons[] = "moderation_status is '{$p->moderation_status}', map requires 'approved'";
}

if ($p->latitude === null || $p->longitude === null) {
    $reasons[] = 'latitude/longitude is NULL — needs geocoding';
} elseif ($lat == 0.0 || $lng == 0.0) {
    $reasons[] = 'latitude/longitude is 0 — NOT geocoded yet (run: php artisan serik:geocode --rounds=5)';
} elseif ($lat < $onSouth || $lat > $onNorth || $lng < $onWest || $lng > $onEast) {
    $reasons[] = "coordinates ({$lat},{$lng}) are outside Ontario map bounds";
}

$excludedSubtypes = ['Industrial', 'Commercial Retail'];
if (in_array(trim((string) $p->PropertySubType), $excludedSubtypes, true)) {
    $reasons[] = "PropertySubType '{$p->PropertySubType}' is excluded from residential map scope";
}

// Date checks (Last X Days uses listing_contract_date, fallback created_at)
$listedOn = $p->listing_contract_date ?: $p->created_at;
if ($listedOn) {
    foreach (['last_1_day' => 1, 'last_3_day' => 3, 'last_7_day' => 7, 'last_30_day' => 30] as $label => $days) {
        $cutoff = now()->subDays($days)->startOfDay();
        $in = \Illuminate\Support\Carbon::parse($listedOn) >= $cutoff;
        echo sprintf("  %-12s cutoff=%s  listed=%s  => %s\n",
            $label, $cutoff->toDateString(), \Illuminate\Support\Carbon::parse($listedOn)->toDateString(),
            $in ? 'INCLUDED' : 'excluded');
    }
}

echo str_repeat('-', 55) . "\n";

if ($reasons === []) {
    echo "RESULT: Listing passes all map filters. If still missing, clear map cache:\n";
    echo "  php artisan cache:clear\n";
} else {
    echo "RESULT: Listing is EXCLUDED from map because:\n";
    foreach ($reasons as $r) {
        echo "  - {$r}\n";
    }
}
