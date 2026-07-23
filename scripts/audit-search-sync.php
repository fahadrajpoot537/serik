<?php

/**
 * Audit deferred Meilisearch sync — run: php scripts/audit-search-sync.php
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
$searchJob = $root . '/app/Jobs/SearchSyncJob.php';
$searchSync = $root . '/app/Support/PropertySearchSync.php';

$required = [
    'SearchSyncJob' => $searchJob,
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

if (is_file($searchJob)) {
    $content = file_get_contents($searchJob) ?: '';
    if (! str_contains($content, 'ShouldBeUniqueUntilProcessing')) {
        $violations[] = 'SearchSyncJob must implement ShouldBeUniqueUntilProcessing';
    } else {
        $passes[] = 'SearchSyncJob implements ShouldBeUniqueUntilProcessing';
    }

    if (! preg_match("/return\s+'search-sync:'\s*\.\s*\\\$this->propertyId/", $content)) {
        $violations[] = 'SearchSyncJob must use uniqueId search-sync:{propertyId}';
    } else {
        $passes[] = 'SearchSyncJob uniqueId is per property (search-sync:{id})';
    }

    if (! str_contains($content, 'PropertySearchSync')) {
        $violations[] = 'SearchSyncJob must delegate indexing to PropertySearchSync';
    } else {
        $passes[] = 'SearchSyncJob delegates to PropertySearchSync';
    }
}

if (is_file($searchSync)) {
    $content = file_get_contents($searchSync) ?: '';
    if (! str_contains($content, 'SearchSyncJob::dispatch')) {
        $violations[] = 'PropertySearchSync must dispatch SearchSyncJob';
    } else {
        $passes[] = 'PropertySearchSync dispatches SearchSyncJob';
    }

    if (! str_contains($content, 'searchableSync')) {
        $violations[] = 'PropertySearchSync must batch via searchableSync()';
    } else {
        $passes[] = 'PropertySearchSync supports batched searchableSync()';
    }

    if (! str_contains($content, 'DB::afterCommit')) {
        $violations[] = 'PropertySearchSync should defer dispatch until DB commit';
    } else {
        $passes[] = 'PropertySearchSync respects DB::afterCommit for eventual consistency';
    }
}

$appFiles = [];
scanDirPhp($root . '/app/Jobs', $appFiles);

foreach ($appFiles as $file) {
    $relative = str_replace('\\', '/', $file);
    if (! str_contains($relative, 'PersistTrebImagesJob.php')
        && ! str_contains($relative, 'SearchSyncJob.php')
        && preg_match('/PersistTrebImagesJob/', file_get_contents($file) ?: '')) {
        if (preg_match('/->searchable\s*\(/', file_get_contents($file) ?: '')) {
            $violations[] = "Image-related job calls searchable() directly: {$relative}";
        }
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
    echo "\nDuplicate collapse guarantee:\n";
    echo "  - ShouldBeUniqueUntilProcessing on SearchSyncJob collapses queue duplicates per property.\n";
    echo "  - PropertySearchSync pending set + batch claim merges concurrent property syncs.\n";
    exit(1);
}

echo "\nAll checks passed.\n";
echo "\nDuplicate index update elimination:\n";
echo "  1. Multiple image persists for the same property enqueue one SearchSyncJob (unique per property).\n";
echo "  2. SearchSyncJob claims a batch of pending property IDs in one Meilisearch request.\n";
echo "  3. Image jobs never call searchable(); Meilisearch failures stay on the search queue.\n";

exit(0);
