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

// Fix Resend + send PIN template test (live server helper).
if (isset($_GET['fix_resend']) && (string) $_GET['fix_resend'] === '1') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== fix Resend email (DB + test send) ===\n\n";

    $envPath = $base . '/.env';
    $resendKey = readEnvValue($envPath, 'RESEND_API_KEY');
    if (! $resendKey) {
        echo "FAIL: Add RESEND_API_KEY=re_... to {$envPath} first.\n";
        exit;
    }

    upsertEnvValue($envPath, 'MAIL_MAILER', 'resend');
    upsertEnvValue($envPath, 'RESEND_API_KEY', $resendKey);
    if (! readEnvValue($envPath, 'MAIL_FROM_ADDRESS') || str_contains((string) readEnvValue($envPath, 'MAIL_FROM_ADDRESS'), '@serik.ca')) {
        upsertEnvValue($envPath, 'MAIL_FROM_ADDRESS', 'onboarding@resend.dev');
    }
    upsertEnvValue($envPath, 'MAIL_FROM_NAME', readEnvValue($envPath, 'MAIL_FROM_NAME') ?: 'Serik Realty');

    deleteFile($base . '/bootstrap/cache/config.php');

    $testTo = trim((string) ($_GET['to'] ?? ''));
    $phpBin = defined('PHP_BINARY') ? PHP_BINARY : 'php';
    $runner = $base . '/scripts/fix-resend-email.php';
    $cmd = escapeshellarg($phpBin) . ' ' . escapeshellarg($runner);
    if ($testTo !== '') {
        $cmd .= ' ' . escapeshellarg($testTo);
    }
    $output = shell_exec($cmd . ' 2>&1');
    echo ($output ?: "(no output from runner)\n");
    echo "\nOpen https://resend.com/emails for delivery status.\n";
    echo "Sandbox onboarding@resend.dev only delivers to your Resend signup email.\n";
    echo "For real users: verify serik.ca at https://resend.com/domains then set MAIL_FROM_ADDRESS=info@serik.ca\n";
    exit;
}

// Diagnose IIS storage link + sample image_val paths (404 helper).
if (isset($_GET['diag_images']) && (string) $_GET['diag_images'] === '1') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== storage / images diagnostic ===\n\n";

    $publicStorage = $base . '/public/storage';
    $appPublic = $base . '/storage/app/public';

    echo 'APP_URL(.env)=' . (readEnvValue($base . '/.env', 'APP_URL') ?: '(missing)') . "\n";
    echo 'public/storage exists=' . (file_exists($publicStorage) ? 'yes' : 'NO') . "\n";
    echo 'public/storage is_link=' . (is_link($publicStorage) ? 'yes' : 'no') . "\n";
    echo 'public/storage is_dir=' . (is_dir($publicStorage) ? 'yes' : 'no') . "\n";
    echo 'storage/app/public exists=' . (is_dir($appPublic) ? 'yes' : 'NO') . "\n";

    if (is_dir($publicStorage)) {
        $probe = $publicStorage . '/avatars/1.jpg';
        echo 'probe file public/storage/avatars/1.jpg=' . (is_file($probe) ? 'FOUND' : 'missing') . "\n";
    }
    if (is_dir($appPublic)) {
        $probe2 = $appPublic . '/avatars/1.jpg';
        echo 'probe file storage/app/public/avatars/1.jpg=' . (is_file($probe2) ? 'FOUND' : 'missing') . "\n";
    }

    echo "\n--- sample image_val from DB ---\n";
    try {
        require $base . '/vendor/autoload.php';
        $app = require $base . '/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        echo 'config app.url=' . config('app.url') . "\n";
        echo 'filesystems.public.url=' . config('filesystems.disks.public.url') . "\n\n";

        $rows = Illuminate\Support\Facades\DB::table('re_properties')
            ->select(['id', 'external_id', 'image_val'])
            ->whereNotNull('image_val')
            ->where('image_val', '!=', '')
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();

        foreach ($rows as $row) {
            $raw = (string) $row->image_val;
            $url = \App\Support\SerikMediaUrl::toPublic($raw);
            $diskRel = ltrim(str_replace('\\', '/', $raw), '/');
            if (str_starts_with($diskRel, 'storage/')) {
                $diskRel = substr($diskRel, strlen('storage/'));
            }
            $onDisk = (! str_starts_with($raw, 'http') && $diskRel !== '')
                ? (is_file($appPublic . '/' . $diskRel) ? 'ON_DISK' : 'MISSING_FILE')
                : 'remote_or_empty';
            echo "id={$row->id} key={$row->external_id}\n";
            echo "  raw={$raw}\n";
            echo "  url={$url}\n";
            echo "  disk={$onDisk}\n\n";
        }

        echo "If disk=MISSING_FILE → file not under storage/app/public (re-upload or copy).\n";
        echo "If url has wrong host → fix APP_URL=https://serik.ca and php artisan config:clear.\n";
        echo "\nIMPORTANT: If public/storage is_link=no BUT files FOUND there, CMS media lives in that folder.\n";
        echo "Do NOT rmdir public\\storage or you will delete uploads. Optional later migrate:\n";
        echo "  robocopy public\\storage storage\\app\\public /E\n";
        echo "  rmdir /S /Q public\\storage\n";
        echo "  mklink /J public\\storage storage\\app\\public\n";
        echo "MLS listing photos use https://trreb-image.ampre.ca/... (not local storage).\n";
        echo "If those 404 in browser Network tab, paste the exact failing URL.\n";
    } catch (Throwable $e) {
        echo 'ERROR: ' . $e->getMessage() . "\n";
    }
    exit;
}

// Cron / queue health snapshot (writes same idea as serik-live-health.bat).
if (isset($_GET['diag_cron']) && (string) $_GET['diag_cron'] === '1') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== cron / queue diagnostic ===\n\n";
    try {
        require $base . '/vendor/autoload.php';
        $app = require $base . '/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        echo 'now=' . now() . ' tz=' . config('app.timezone') . "\n";
        echo 'high=' . Illuminate\Support\Facades\DB::table('jobs')->where('queue', 'high')->count() . "\n";
        echo 'low=' . Illuminate\Support\Facades\DB::table('jobs')->where('queue', 'low')->count() . "\n";
        echo 'failed=' . Illuminate\Support\Facades\DB::table('failed_jobs')->count() . "\n";
        echo 'updated_3h=' . \Botble\RealEstate\Models\Property::where('updated_at', '>=', now()->subHours(3))->count() . "\n";
        echo 'created_3h=' . \Botble\RealEstate\Models\Property::where('created_at', '>=', now()->subHours(3))->where('created_at', '<', '2100-01-01')->count() . "\n";

        foreach (['treb-sync-live.log', 'queue-high.log', 'queue-low.log'] as $log) {
            $p = $base . '/storage/logs/' . $log;
            if (is_file($p)) {
                echo $log . ' mtime=' . date('Y-m-d H:i:s', filemtime($p)) . ' size=' . filesize($p) . "\n";
            } else {
                echo $log . " MISSING\n";
            }
        }

        echo "\nNeeded on Windows:\n";
        echo "1) Task Scheduler every 1 min: php artisan schedule:run\n";
        echo "2) NSSM SerikQueueHigh + SerikQueueLow RUNNING\n";
        echo "3) Force: php artisan serik:sync-live --force --days=1 --pages=6 --max-new=100\n";
    } catch (Throwable $e) {
        echo 'ERROR: ' . $e->getMessage() . "\n";
    }
    exit;
}

// Fix locked / unwritable Laravel daily logs (IIS Permission denied → homepage properties blank).
if (isset($_GET['fix_logs']) && (string) $_GET['fix_logs'] === '1') {
    header('Content-Type: text/plain; charset=utf-8');
    $logDir = $base . '/storage/logs';
    echo "=== fix storage/logs ===\n\n";
    echo 'log dir: ' . $logDir . "\n";
    echo 'exists: ' . (is_dir($logDir) ? 'yes' : 'no') . "\n";
    echo 'writable: ' . (is_writable($logDir) ? 'yes' : 'no') . "\n\n";

    if (! is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    $deleted = 0;
    $kept = 0;
    foreach (glob($logDir . '/laravel*.log') ?: [] as $file) {
        $name = basename($file);
        if (@unlink($file)) {
            echo "deleted: {$name}\n";
            $deleted++;
        } else {
            echo "COULD NOT DELETE (still locked?): {$name}\n";
            $kept++;
        }
    }

    // Touch a fresh writable log so Monolog can append.
    $today = $logDir . '/laravel-' . date('Y-m-d') . '.log';
    $ok = @file_put_contents($today, '[' . date('Y-m-d H:i:s') . '] local.INFO: log permissions probe' . PHP_EOL, FILE_APPEND);
    echo "\nprobe write " . basename($today) . ': ' . ($ok !== false ? 'OK' : 'FAILED') . "\n";
    echo "deleted={$deleted} kept={$kept}\n\n";
    echo "Next: hard refresh https://serik.ca/\n";
    echo "Also deploy the code fix that stops Log::info from crashing the properties shortcode.\n";
    exit;
}

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
