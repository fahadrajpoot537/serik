<?php

/**
 *   php scripts/list-accounts.php
 * Shows which DB this app uses and registered account emails.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$default = config('database.default');
$conn = config("database.connections.{$default}");

echo 'connection=' . $default . PHP_EOL;
echo 'host=' . ($conn['host'] ?? '') . PHP_EOL;
echo 'database=' . ($conn['database'] ?? '') . PHP_EOL;
echo 'accounts=' . DB::table('re_accounts')->count() . PHP_EOL;
echo str_repeat('-', 60) . PHP_EOL;

foreach (DB::table('re_accounts')->orderByDesc('id')->limit(50)->get(['id', 'email', 'created_at', 'confirmed_at']) as $row) {
    echo sprintf(
        "#%d  %s  created=%s  confirmed=%s\n",
        $row->id,
        $row->email,
        $row->created_at,
        $row->confirmed_at ?: 'NULL'
    );
}
