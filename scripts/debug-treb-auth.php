<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Theme\homzen\Supports\TrebPropertyHelper;

function maskToken(?string $token): string
{
    if ($token === null || $token === '') {
        return '(empty)';
    }
    $token = trim($token, " \t\n\r\0\x0B\"'");
    return substr($token, 0, 20) . '...[' . strlen($token) . ' chars]';
}

function jwtJti(?string $token): string
{
    if (! $token) {
        return 'n/a';
    }
    $parts = explode('.', trim($token));
    if (count($parts) < 2) {
        return 'invalid-jwt';
    }
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

    return ($payload['jti'] ?? '?') . ' sub=' . ($payload['sub'] ?? '?');
}

echo "=== TRREB Auth Diagnostic ===\n\n";

// 1. .env direct read
$envAuth = null;
$envAuth1 = null;
foreach (file(base_path('.env')) as $line) {
    $line = trim($line);
    if (str_starts_with($line, 'TRREB_AUTH=') && ! str_starts_with($line, 'TRREB_AUTH1=')) {
        $envAuth = trim(substr($line, strlen('TRREB_AUTH=')), " \t\"'");
    }
    if (str_starts_with($line, 'TRREB_AUTH1=')) {
        $envAuth1 = trim(substr($line, strlen('TRREB_AUTH1=')), " \t\"'");
    }
}

echo "1. .env TRREB_AUTH:  " . maskToken($envAuth) . " jti=" . jwtJti($envAuth) . "\n";
echo "   .env TRREB_AUTH1: " . maskToken($envAuth1) . " jti=" . jwtJti($envAuth1) . "\n";
echo "   .env TRREB_AUTH has leading/trailing quotes: " . (preg_match('/^["\']|["\']$/', trim(substr(file_get_contents(base_path('.env')), 0))) ? 'check manually' : 'no') . "\n";
echo "   TRREB_AUTH len=" . strlen($envAuth ?? '') . " starts/ends space=" . (($envAuth ?? '') !== trim($envAuth ?? '') ? 'yes' : 'no') . "\n\n";

// 2. config cache
$configCached = file_exists(base_path('bootstrap/cache/config.php'));
echo "2. Config cached: " . ($configCached ? 'YES' : 'no') . "\n";
echo "   config(treb.auth):  " . maskToken(config('treb.auth')) . " jti=" . jwtJti(config('treb.auth')) . "\n";
echo "   config(treb.auth1): " . maskToken(config('treb.auth1')) . " jti=" . jwtJti(config('treb.auth1')) . "\n";
echo "   config auth matches .env TRREB_AUTH:  " . (config('treb.auth') === $envAuth ? 'yes' : 'NO - STALE/SWAPPED') . "\n";
echo "   config auth1 matches .env TRREB_AUTH1: " . (config('treb.auth1') === $envAuth1 ? 'yes' : 'NO - STALE/SWAPPED') . "\n\n";

// 3. env() at runtime
echo "3. env(TRREB_AUTH) at runtime:  " . maskToken(env('TRREB_AUTH')) . " (empty when config cached)\n";
echo "   env(TRREB_AUTH1) at runtime: " . maskToken(env('TRREB_AUTH1')) . "\n\n";

// 4. ampTokens order
$tokens = TrebPropertyHelper::ampTokens();
echo "4. ampTokens() count=" . count($tokens) . "\n";
foreach ($tokens as $i => $t) {
    $source = 'unknown';
    if ($t === $envAuth) {
        $source = 'TRREB_AUTH (.env)';
    } elseif ($t === $envAuth1) {
        $source = 'TRREB_AUTH1 (.env)';
    } elseif ($t === config('treb.auth')) {
        $source = 'config(treb.auth)';
    } elseif ($t === config('treb.auth1')) {
        $source = 'config(treb.auth1)';
    }
    echo "   token[" . $i . "]: " . maskToken($t) . " source={$source} jti=" . jwtJti($t) . "\n";
}
echo "\n";

// 5. HTTP test with each token source
$url = 'https://query.ampre.ca/odata/Property?$filter=' . rawurlencode("ListingKey eq 'W13024458'") . '&$top=1';

$sources = [
    'ampTokens[0] (first used by Laravel)' => $tokens[0] ?? null,
    '.env TRREB_AUTH' => $envAuth,
    '.env TRREB_AUTH1' => $envAuth1,
    'config(treb.auth)' => config('treb.auth'),
    'env(TRREB_AUTH) direct' => env('TRREB_AUTH'),
    'empty string (simulates broken path)' => '',
];

echo "5. HTTP tests against: {$url}\n";
foreach ($sources as $label => $token) {
    $authHeader = 'Bearer ' . ($token ?? '');
    echo "\n--- {$label} ---\n";
    echo "   Authorization: " . maskToken($token) . " (Bearer prefix=" . (str_starts_with($authHeader, 'Bearer eyJ') ? 'yes' : 'NO') . ")\n";

    if ($token === null || $token === '') {
        echo "   SKIPPED (empty token -> would cause 401)\n";
        continue;
    }

    $r = Http::timeout(15)->withHeaders([
        'Authorization' => $authHeader,
        'Accept' => 'application/json',
        'OData-Version' => '4.0',
        'OData-MaxVersion' => '4.0',
    ])->get($url);

    echo "   HTTP Status: " . $r->status() . "\n";
    if (! $r->successful()) {
        echo "   Body: " . substr($r->body(), 0, 200) . "\n";
    } else {
        echo "   value count: " . count($r->json('value') ?? []) . "\n";
    }
}

// 6. ampGet result
echo "\n6. TrebPropertyHelper::ampGet() status:\n";
$result = TrebPropertyHelper::ampGet($url, 15);
echo "   returned: " . (is_array($result) ? count($result['value'] ?? []) . ' records' : 'null') . "\n";
