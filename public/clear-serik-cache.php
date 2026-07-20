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

// Boot Laravel over HTTP and print the real properties-shortcode exception.
if (isset($_GET['test_properties']) && (string) $_GET['test_properties'] === '1') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== properties shortcode diagnostic ===\n\n";

    try {
        require $base . '/vendor/autoload.php';
        $app = require $base . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        $kernel->bootstrap();

        // Fire RouteMatched so theme shortcodes register (same as a normal web hit).
        $request = Illuminate\Http\Request::create('https://serik.ca/', 'GET');
        $app->instance('request', $request);
        event(new Illuminate\Routing\Events\RouteMatched(
            new Illuminate\Routing\Route(['GET'], '/', fn () => null),
            $request
        ));

        if (class_exists(\App\Support\EnsuresTranslator::class)) {
            \App\Support\EnsuresTranslator::ensure();
        }

        echo 'translator bound: ' . (app()->bound('translator') ? 'yes' : 'no') . "\n";
        echo 'App\\Actions\\HomepageFeaturedPropertiesAction: ' . (class_exists(\App\Actions\HomepageFeaturedPropertiesAction::class) ? 'yes' : 'no') . "\n";
        echo 'Theme GetPropertiesAction: ' . (class_exists(\Theme\homzen\Actions\GetPropertiesAction::class) ? 'yes' : 'no') . "\n";
        echo 'properties registered: ' . (array_key_exists('properties', \Botble\Shortcode\Facades\Shortcode::getAll()) ? 'yes' : 'no') . "\n\n";

        foreach (['5', '4'] as $style) {
            echo "--- compile style={$style} ---\n";
            try {
                $started = microtime(true);
                $html = \Botble\Shortcode\Facades\Shortcode::compile(
                    \Botble\Shortcode\Facades\Shortcode::generateShortcode('properties', [
                        'style' => $style,
                        'limit' => '6',
                        'title' => 'Featured',
                        'is_featured' => '0,1',
                    ]),
                    true
                )->toHtml();
                echo 'OK bytes=' . strlen((string) $html) . ' ms=' . round((microtime(true) - $started) * 1000, 1) . "\n";
                echo 'preview: ' . substr(trim(preg_replace('/\s+/', ' ', strip_tags((string) $html))), 0, 180) . "\n\n";
            } catch (Throwable $e) {
                echo 'FAIL: ' . $e::class . "\n";
                echo 'message: ' . $e->getMessage() . "\n";
                echo 'file: ' . $e->getFile() . ':' . $e->getLine() . "\n";
                echo "trace:\n" . $e->getTraceAsString() . "\n\n";
            }
        }

        // Tail laravel log if present
        $logFiles = glob($base . '/storage/logs/laravel*.log') ?: [];
        usort($logFiles, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
        if ($logFiles !== []) {
            echo "--- last ajaxRenderUiBlock / properties log lines ---\n";
            $lines = @file($logFiles[0]) ?: [];
            $interesting = array_values(array_filter($lines, static function ($line) {
                return str_contains($line, 'ajaxRenderUiBlock')
                    || str_contains($line, 'shortcode:properties')
                    || str_contains($line, 'homepage-featured');
            }));
            $slice = array_slice($interesting, -30);
            echo $slice === [] ? "(none found in " . basename($logFiles[0]) . ")\n" : implode('', $slice);
        }
    } catch (Throwable $e) {
        echo 'BOOT FAIL: ' . $e::class . ': ' . $e->getMessage() . "\n";
        echo $e->getFile() . ':' . $e->getLine() . "\n";
        echo $e->getTraceAsString() . "\n";
    }

    exit;
}

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

function deleteFile(string $path): bool
{
    return is_file($path) && @unlink($path);
}

function readEnvValue(string $envPath, string $key): ?string
{
    if (! is_file($envPath)) {
        return null;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return null;
    }

    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '' || str_starts_with($trim, '#')) {
            continue;
        }
        if (str_starts_with($trim, $key . '=')) {
            return substr($trim, strlen($key) + 1);
        }
    }

    return null;
}

function upsertEnvValue(string $envPath, string $key, string $value): bool
{
    if (! is_file($envPath) || ! is_writable($envPath)) {
        return false;
    }

    $contents = file_get_contents($envPath);
    if ($contents === false) {
        return false;
    }

    $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
    if (preg_match($pattern, $contents)) {
        $contents = preg_replace($pattern, $key . '=' . $value, $contents, 1);
    } else {
        $contents = rtrim($contents) . "\n\n" . $key . '=' . $value . "\n";
    }

    return file_put_contents($envPath, $contents) !== false;
}

$viewDir = $base . '/storage/framework/views';
$cacheDir = $base . '/storage/framework/cache/data';
$bootstrapCache = $base . '/bootstrap/cache';

$results['views_deleted'] = clearDir($viewDir);
$results['cache_deleted'] = clearDir($cacheDir);

// Laravel config / services / packages caches (these keep GEO_BLOCK_ENABLED=true alive)
$results['config_php_deleted'] = deleteFile($bootstrapCache . '/config.php');
$results['services_php_deleted'] = deleteFile($bootstrapCache . '/services.php');
$results['packages_php_deleted'] = deleteFile($bootstrapCache . '/packages.php');
$results['routes_v7_deleted'] = deleteFile($bootstrapCache . '/routes-v7.php');

@file_put_contents($base . '/storage/framework/cache/clear.txt', (string) time());

$envPath = $base . '/.env';
$geoBefore = readEnvValue($envPath, 'GEO_BLOCK_ENABLED');
$countriesBefore = readEnvValue($envPath, 'GEO_BLOCK_ALLOWED_COUNTRIES');

$geoDisabled = false;
if (isset($_GET['disable_geo']) && (string) $_GET['disable_geo'] === '1') {
    $geoDisabled = upsertEnvValue($envPath, 'GEO_BLOCK_ENABLED', 'false');
    // Also allow PK while testing (harmless if geo remains off)
    upsertEnvValue($envPath, 'GEO_BLOCK_ALLOWED_COUNTRIES', 'US,CA,PK');
}

$geoAfter = readEnvValue($envPath, 'GEO_BLOCK_ENABLED');
$countriesAfter = readEnvValue($envPath, 'GEO_BLOCK_ALLOWED_COUNTRIES');

$clientIp = $_SERVER['HTTP_CF_CONNECTING_IP']
    ?? $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['REMOTE_ADDR']
    ?? 'unknown';
if (is_string($clientIp) && str_contains($clientIp, ',')) {
    $clientIp = trim(explode(',', $clientIp)[0]);
}
$cfCountry = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? 'n/a';

header('Content-Type: text/plain; charset=utf-8');
echo "Cache cleared successfully.\n\n";
echo 'Views files removed: ' . $results['views_deleted'] . "\n";
echo 'Cache files removed: ' . $results['cache_deleted'] . "\n";
echo 'bootstrap/cache/config.php deleted: ' . ($results['config_php_deleted'] ? 'yes' : 'no') . "\n";
echo 'bootstrap/cache/services.php deleted: ' . ($results['services_php_deleted'] ? 'yes' : 'no') . "\n";
echo 'bootstrap/cache/packages.php deleted: ' . ($results['packages_php_deleted'] ? 'yes' : 'no') . "\n";
echo 'bootstrap/cache/routes-v7.php deleted: ' . ($results['routes_v7_deleted'] ? 'yes' : 'no') . "\n\n";

echo "Geo block diagnostics\n";
echo 'Your IP: ' . $clientIp . "\n";
echo 'CF-IPCountry: ' . $cfCountry . "\n";
echo 'GEO_BLOCK_ENABLED before: ' . ($geoBefore ?? '(missing → app may default ON)') . "\n";
echo 'GEO_BLOCK_ENABLED after:  ' . ($geoAfter ?? '(missing)') . "\n";
echo 'ALLOWED_COUNTRIES before: ' . ($countriesBefore ?? '(missing)') . "\n";
echo 'ALLOWED_COUNTRIES after:  ' . ($countriesAfter ?? '(missing)') . "\n";
echo 'disable_geo applied: ' . ($geoDisabled ? 'yes' : (isset($_GET['disable_geo']) ? 'FAILED (check .env writable)' : 'no')) . "\n\n";

if (! isset($_GET['disable_geo'])) {
    echo "Homepage 403 from Pakistan? Open this next:\n";
    echo "https://serik.ca/clear-serik-cache.php?key=serik2026clear&disable_geo=1\n\n";
}

echo "Then open https://serik.ca/ again.\n";
echo "IMPORTANT: Delete public/clear-serik-cache.php from your server now.\n";
