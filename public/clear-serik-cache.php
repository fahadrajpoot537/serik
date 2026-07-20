<?php

/**
 * One-time cache clear for shared hosting (no SSH).
 * 1. Upload this file to your site public folder.
 * 2. Visit: https://serik.ca/clear-serik-cache.php?key=YOUR_SECRET
 * 3. DELETE this file immediately after use.
 */

$secret = 'serik2026clear';

if (($_GET['key'] ?? '') !== $secret) {
    http_response_code(403);
    exit('Forbidden. Add ?key=serik2026clear to the URL.');
}

$base = dirname(__DIR__);
$results = [];

function clearDir(string $dir): int
{
    if (! is_dir($dir)) {
        return 0;
    }

    $count = 0;
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        if ($item->isDir()) {
            @rmdir($item->getPathname());
        } else {
            if (@unlink($item->getPathname())) {
                $count++;
            }
        }
    }

    return $count;
}

$viewDir = $base . '/storage/framework/views';
$cacheDir = $base . '/storage/framework/cache/data';

$results['views_deleted'] = clearDir($viewDir);
$results['cache_deleted'] = clearDir($cacheDir);

@file_put_contents($base . '/storage/framework/cache/clear.txt', (string) time());

header('Content-Type: text/plain; charset=utf-8');
echo "Cache cleared successfully.\n\n";
echo 'Views files removed: ' . $results['views_deleted'] . "\n";
echo 'Cache files removed: ' . $results['cache_deleted'] . "\n\n";
echo "IMPORTANT: Delete public/clear-serik-cache.php from your server now.\n";
