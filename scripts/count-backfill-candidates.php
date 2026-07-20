<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$query = DB::table('re_properties')
    ->where('moderation_status', 'approved')
    ->whereNotNull('external_id')
    ->where('external_id', '!=', '')
    ->where(function ($q) {
        $q->whereNull('CoveredSpaces')
            ->orWhere('number_floor', '<=', 1)
            ->orWhere('updated_at', '<', now()->subDays(14))
            ->orWhereRaw('BedroomsBelowGrade > 0 AND number_bedroom > BedroomsBelowGrade');
    });

echo 'total_candidates=' . $query->count() . PHP_EOL;
echo 'limit_500_sample=' . $query->orderBy('updated_at')->limit(500)->count() . PHP_EOL;
