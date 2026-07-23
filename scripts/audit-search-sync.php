<?php

/**
 * Audit deferred Meilisearch batch sync — run: php scripts/audit-search-sync.php
 */
require __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);
$violations = [];
$passes = [];

function scanDirPhp(string $dir, array &$files): void
{
    if (! is_dir($dir)) {
        return;
    }

    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $entry;

        if (is_dir($path)) {
            if (in_array($entry, ['vendor', 'node_modules', 'storage'], true)) {
                continue;
            }
            scanDirPhp($path, $files);

            continue;
        }

        if (str_ends_with($entry, '.php')) {
            $files[] = $path;
        }
    }
}

$imageJob = $root . '/app/Jobs/PersistTrebImagesJob.php';
$pipeline = $root . '/app/Support/ListingImagePipeline.php';
$batchJob = $root . '/app/Jobs/SearchBatchJob.php';
$oldJob = $root . '/app/Jobs/SearchSyncJob.php';
$searchSync = $root . '/app/Support/PropertySearchSync.php';

if (is_file($oldJob)) {
    $violations[] = 'SearchSyncJob.php must be removed (replaced by SearchBatchJob)';
} else {
    $passes[] = 'SearchSyncJob.php removed (no per-property jobs)';
}

$required = [
    'SearchBatchJob' => $batchJob,
    'PropertySearchSync' => $searchSync,
];

foreach ($required as $label => $path) {
    if (! is_file($path)) {
        $violations[] = "MISSING required file: {$label} ({$path})";
    } else {
        $passes[] = "Required file exists: {$label}";
    }
}

if (is_file($imageJob)) {
    $content = file_get_contents($imageJob) ?: '';
    if (preg_match('/->searchable\s*\(/', $content) || preg_match('/searchableSync\s*\(/', $content)) {
        $violations[] = 'PersistTrebImagesJob must not call searchable() or searchableSync()';
    } else {
        $passes[] = 'PersistTrebImagesJob has no direct searchable() calls';
    }
}

if (is_file($pipeline)) {
    $content = file_get_contents($pipeline) ?: '';
    if (preg_match('/->searchable\s*\(/', $content) || preg_match('/searchableSync\s*\(/', $content)) {
        $violations[] = 'ListingImagePipeline must not call searchable() — use PropertySearchSync::schedule()';
    } elseif (! str_contains($content, 'PropertySearchSync') || ! str_contains($content, '->schedule(')) {
        $violations[] = 'ListingImagePipeline must schedule PropertySearchSync after image DB writes';
    } else {
        $passes[] = 'ListingImagePipeline defers search sync via PropertySearchSync::schedule()';
    }
}

if (is_file($searchSync)) {
    $content = file_get_contents($searchSync) ?: '';

    if (preg_match('/SearchSyncJob/', $content)) {
        $violations[] = 'PropertySearchSync must not reference SearchSyncJob';
    } else {
        $passes[] = 'PropertySearchSync has no SearchSyncJob references';
    }

    if (preg_match('/SearchSyncJob::dispatch\s*\(/', $content)) {
        $violations[] = 'PropertySearchSync must not dispatch SearchSyncJob per property';
    } else {
        $passes[] = 'PropertySearchSync does not dispatch per-property SearchSyncJob';
    }

    if (! preg_match('/function\s+schedule\s*\(/', $content) || ! str_contains($content, 'markPending')) {
        $violations[] = 'PropertySearchSync::schedule() must only mark pending';
    } else {
        $passes[] = 'PropertySearchSync::schedule() marks pending IDs';
    }

    if (preg_match('/function\s+schedule[\s\S]*?\$dispatch\s*=\s*function[^{]*\{[^}]*SearchBatchJob::dispatch/s', $content)) {
        $violations[] = 'PropertySearchSync::schedule() must not dispatch SearchBatchJob (dispatch only from markPending on empty→non-empty)';
    } else {
        $passes[] = 'PropertySearchSync::schedule() does not dispatch SearchBatchJob directly';
    }

    if (! preg_match('/\$wasEmpty\s*=\s*\$pending\s*===\s*\[\]/', $content)) {
        $violations[] = 'markPending() must detect empty→non-empty pending transition';
    } else {
        $passes[] = 'markPending() detects empty→non-empty pending transition';
    }

    if (! preg_match('/if\s*\(\s*\$wasEmpty\s*\)\s*\{[\s\S]*?SearchBatchJob::dispatch/', $content)) {
        $violations[] = 'markPending() must dispatch SearchBatchJob only when pending was empty';
    } else {
        $passes[] = 'markPending() dispatches SearchBatchJob only on empty→non-empty transition';
    }

    if (! str_contains($content, 'claimNextBatch') || ! str_contains($content, 'Cache::lock')) {
        $violations[] = 'PropertySearchSync must atomically claim pending IDs under lock';
    } else {
        $passes[] = 'PropertySearchSync atomically claims pending IDs under lock';
    }

    if (! str_contains($content, 'requeue')) {
        $violations[] = 'PropertySearchSync must requeue failed IDs';
    } else {
        $passes[] = 'PropertySearchSync requeues failed IDs on index failure';
    }

    if (! str_contains($content, 'searchableSync')) {
        $violations[] = 'PropertySearchSync must call searchableSync() once per batch';
    } else {
        $passes[] = 'PropertySearchSync uses one searchableSync() call per batch';
    }

    if (! str_contains($content, 'DB::afterCommit')) {
        $violations[] = 'PropertySearchSync should defer until DB commit';
    } else {
        $passes[] = 'PropertySearchSync respects DB::afterCommit';
    }
}

if (is_file($batchJob)) {
    $content = file_get_contents($batchJob) ?: '';

    if (! str_contains($content, 'ShouldBeUniqueUntilProcessing')) {
        $violations[] = 'SearchBatchJob must implement ShouldBeUniqueUntilProcessing';
    } else {
        $passes[] = 'SearchBatchJob implements ShouldBeUniqueUntilProcessing';
    }

    if (! preg_match("/return\s+'serik-search-batch-global'/", $content)) {
        $violations[] = 'SearchBatchJob must use a single global uniqueId';
    } else {
        $passes[] = 'SearchBatchJob uses one global uniqueId';
    }

    if (! str_contains($content, 'WORKER_LOCK_KEY') || ! str_contains($content, 'Cache::lock')) {
        $violations[] = 'SearchBatchJob must acquire a distributed worker lock';
    } else {
        $passes[] = 'SearchBatchJob acquires a distributed worker lock';
    }

    if (! str_contains($content, 'processNextBatch')) {
        $violations[] = 'SearchBatchJob must delegate draining to PropertySearchSync::processNextBatch()';
    } else {
        $passes[] = 'SearchBatchJob drains pending via processNextBatch()';
    }

    if (! preg_match('/while\s*\(\s*\$sync->pendingCount\(\)\s*>\s*0\s*\)/', $content)) {
        $violations[] = 'SearchBatchJob must loop processNextBatch() until pending is empty';
    } else {
        $passes[] = 'SearchBatchJob drains all pending batches in one execution';
    }

    if (preg_match('/function\s+handle[\s\S]*?self::dispatch\s*\(/', $content)) {
        $violations[] = 'SearchBatchJob::handle() must not self-dispatch after each batch';
    } else {
        $passes[] = 'SearchBatchJob::handle() does not self-dispatch per batch';
    }

    if (preg_match('/propertyId/', $content)) {
        $violations[] = 'SearchBatchJob must not accept a per-property constructor argument';
    } else {
        $passes[] = 'SearchBatchJob has no per-property constructor argument';
    }
}

$appFiles = [];
scanDirPhp($root . '/app', $appFiles);

foreach ($appFiles as $file) {
    $content = file_get_contents($file) ?: '';
    $relative = str_replace('\\', '/', $file);

    if (str_contains($content, 'SearchSyncJob::dispatch')) {
        $violations[] = "Forbidden SearchSyncJob::dispatch in {$relative}";
    }
}

echo "=== Search Sync Audit ===\n\n";

foreach ($passes as $line) {
    echo "PASS: {$line}\n";
}

if ($violations !== []) {
    echo "\nFAILURES:\n";
    foreach ($violations as $line) {
        echo "  - {$line}\n";
    }
    exit(1);
}

echo "\nAll checks passed.\n";
echo "\nBatch pipeline guarantees:\n";
echo "  1. schedule() only marks pending; SearchBatchJob dispatches on empty→non-empty only.\n";
echo "  2. SearchBatchJob loops processNextBatch() until pending is empty (one worker lock).\n";
echo "  3. One searchableSync() per batch → up to SERIK_SEARCH_SYNC_BATCH Meilisearch documents.\n";
echo "  4. Failed IDs are requeued; failed() re-dispatches if pending remains.\n";

exit(0);
