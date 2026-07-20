<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;

$candidates = DB::table('re_properties')
    ->where('moderation_status', 'approved')
    ->whereNotNull('external_id')
    ->where('external_id', '!=', '')
    ->where(function ($q) {
        $q->whereNull('CoveredSpaces')
            ->orWhere('number_floor', '<=', 1)
            ->orWhere('updated_at', '<', now()->subDays(14))
            ->orWhereRaw('BedroomsBelowGrade > 0 AND number_bedroom > BedroomsBelowGrade');
    })
    ->orderBy('updated_at')
    ->limit(10)
    ->get([
        'id', 'external_id', 'price', 'CoveredSpaces', 'ParkingSpaces',
        'number_bedroom', 'BedroomsBelowGrade', 'number_floor', 'Basement', 'updated_at',
    ]);

foreach ($candidates as $i => $property) {
    $key = strtoupper((string) $property->external_id);
    $amp = TrebPropertyHelper::fetchAmpBackfillRecord($key);
    $changes = TrebPropertyHelper::buildLegacyBackfillChanges($property, $amp);

    $reasons = [];
    if (! is_array($amp)) {
        $reasons[] = 'AMP empty';
    }
    $below = (int) ($property->BedroomsBelowGrade ?? 0);
    $beds = (int) ($property->number_bedroom ?? 0);
    if ($below > 0 && $beds > $below) {
        $reasons[] = "legacy beds={$beds} below={$below} would set main=" . ($beds - $below);
    } else {
        $reasons[] = "beds={$beds} below={$below} already normalized";
    }
    if (empty($property->CoveredSpaces) && ! empty($property->ParkingSpaces)) {
        $reasons[] = 'would copy ParkingSpaces to CoveredSpaces';
    }
    if (is_string($property->Basement ?? null) && str_starts_with(trim($property->Basement), '[')) {
        $reasons[] = 'Basement JSON needs normalization';
    }

    $action = $changes === [] ? 'SKIP' : 'UPDATE ' . json_encode($changes);
    echo ($i + 1) . ". [{$key}] {$action}\n   " . implode('; ', $reasons) . "\n";
}
