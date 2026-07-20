<?php
/**
 * Compare EVERY AMP ListingKey currently in the feed against local DB.
 * Stable order: ListingKey asc. Checkpoint to storage/logs/amp-full-key-audit.json
 */
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Theme\homzen\Supports\TrebPropertyHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

@set_time_limit(0);
$token = TrebPropertyHelper::ampTokens()[0];
$base = 'https://query.ampre.ca/odata/Property';
$stateFile = storage_path('logs/amp-full-key-audit.json');
$maxRuntime = (int) ($argv[1] ?? 120); // seconds for this pass; re-run to continue
$deadline = microtime(true) + $maxRuntime;

$state = [
    'skip' => 0,
    'scanned' => 0,
    'missing' => [],
    'present' => 0,
    'amp_pages' => 0,
    'done' => false,
    'started_at' => date('c'),
];
if (is_file($stateFile)) {
    $prev = json_decode(file_get_contents($stateFile), true);
    if (is_array($prev) && empty($prev['done'])) {
        $state = array_merge($state, $prev);
        echo "Resuming skip={$state['skip']} scanned={$state['scanned']} missing=".count($state['missing'])."\n";
    }
}

$pageSize = 500;
while (microtime(true) < $deadline) {
    $url = $base.'?$orderby=ListingKey asc&$top='.$pageSize.'&$skip='.$state['skip'].'&$select=ListingKey,MlsStatus,TransactionType,OriginalEntryTimestamp,ModificationTimestamp';
    $res = Http::timeout(60)->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ])->get($url);
    if ($res->status() !== 200) {
        echo "HTTP {$res->status()} at skip={$state['skip']}: ".substr($res->body(),0,200)."\n";
        sleep(5);
        continue;
    }
    $rows = $res->json()['value'] ?? [];
    $state['amp_pages']++;
    if ($rows === []) {
        $state['done'] = true;
        break;
    }
    $keys = array_values(array_filter(array_column($rows, 'ListingKey')));
    $have = array_flip(DB::table('re_properties')->whereIn('external_id', $keys)->pluck('external_id')->all());
    foreach ($keys as $k) {
        $state['scanned']++;
        if (isset($have[$k])) {
            $state['present']++;
        } else {
            // Keep only first 500 missing keys in state for follow-up import (full count still tracked)
            if (!isset($state['missing_count'])) $state['missing_count'] = 0;
            $state['missing_count']++;
            if (count($state['missing']) < 500) {
                $state['missing'][] = $k;
            }
        }
    }
    $state['skip'] += count($rows);
    echo "skip={$state['skip']} scanned={$state['scanned']} present={$state['present']} missing_count=".($state['missing_count']??0)."\n";
    if (count($rows) < $pageSize) {
        $state['done'] = true;
        break;
    }
    // Persist checkpoint every page
    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));
}

$state['finished_pass_at'] = date('c');
file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT));
echo "\nPASS SUMMARY:\n";
echo "done=".var_export($state['done'],true)." skip={$state['skip']} scanned={$state['scanned']}\n";
echo "present={$state['present']} missing_count=".($state['missing_count']??0)."\n";
echo "sample missing: ".implode(',', array_slice($state['missing'],0,20))."\n";
echo "state -> {$stateFile}\n";
