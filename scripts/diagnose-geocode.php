<?php

/**
 * Diagnose why serik:geocode finds 0 properties.
 * Run on the server:  php scripts/diagnose-geocode.php [MLS_ID]
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Botble\RealEstate\Models\Property;
use Illuminate\Support\Facades\DB;

$key = strtoupper(trim($argv[1] ?? ''));

$needsCoords = fn () => Property::query()->where(function ($q) {
    $q->whereNull('latitude')->orWhere('latitude', 0)
        ->orWhereNull('longitude')->orWhere('longitude', 0);
});

echo "Total rows with lat/lng missing-or-zero: " . $needsCoords()->count() . "\n";

echo "  ... AND location present (geocodable):  "
    . $needsCoords()->whereNotNull('location')->where('location', '!=', '')->count() . "\n";

echo "  ... AND location EMPTY/NULL (skipped):   "
    . $needsCoords()->where(function ($q) {
        $q->whereNull('location')->orWhere('location', '');
    })->count() . "\n";

echo str_repeat('-', 55) . "\n";

// What the CURRENT geocode query actually matches (latitude only)
$currentMatch = Property::query()
    ->where(function ($q) {
        $q->whereNull('latitude')->orWhere('latitude', 0);
    })
    ->whereNotNull('location')->where('location', '!=', '')
    ->count();
echo "Current geocode query matches (lat=0 + location): {$currentMatch}\n";

if ($key !== '') {
    echo str_repeat('-', 55) . "\n";
    $p = Property::where('external_id', $key)->first();
    if (! $p) {
        echo "[{$key}] not found.\n";
    } else {
        echo "[{$key}] id={$p->id}\n";
        echo "  latitude  = " . var_export($p->latitude, true) . "\n";
        echo "  longitude = " . var_export($p->longitude, true) . "\n";
        echo "  location  = " . var_export($p->location, true) . "\n";
        echo "  name      = " . var_export($p->name, true) . "\n";
        $matched = Property::query()
            ->where('id', $p->id)
            ->where(function ($q) {
                $q->whereNull('latitude')->orWhere('latitude', 0);
            })
            ->whereNotNull('location')->where('location', '!=', '')
            ->exists();
        echo "  matched by current geocode query? " . ($matched ? 'YES' : 'NO') . "\n";
    }
}
