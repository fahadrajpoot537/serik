<#
.SYNOPSIS
  Start the local Serik stack: Meilisearch + Octane (RoadRunner).

  Do not use `php artisan serve` for this app. That server is one thread, so a
  slow API holds every JavaScript, CSS, and image file until it finishes.

.EXAMPLE
  powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\windows\start-local-dev.ps1
#>
param(
    [string]$Php = 'C:\xampp\php\php.exe',
    [string]$HostAddress = '127.0.0.1',
    [int]$Port = 8000,
    [int]$Workers = 4
)

$ErrorActionPreference = 'Continue'
$Root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
Set-Location $Root

Write-Host '=== 0) Quick tips ===' -ForegroundColor DarkGray
Write-Host 'Census: local .env should use CENSUS_PROFILE_LEVELS=ada,csd (DF_DA hangs).' -ForegroundColor DarkGray
Write-Host 'Keep Meilisearch up or search falls back to slow MySQL.' -ForegroundColor DarkGray

Write-Host '=== 1) Meilisearch ===' -ForegroundColor Cyan
& powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'Start-SerikMeilisearch.ps1')
if ($LASTEXITCODE -ne 0) {
    Write-Host 'Trying bat launcher directly...' -ForegroundColor Yellow
    Start-Process -FilePath (Join-Path $Root 'storage\meilisearch\start-meilisearch.bat') -WindowStyle Hidden
    Start-Sleep -Seconds 3
}

try {
    $h = Invoke-WebRequest -Uri 'http://127.0.0.1:7700/health' -UseBasicParsing -TimeoutSec 3
    Write-Host "Meili: $($h.Content)" -ForegroundColor Green
} catch {
    Write-Host 'Meili still DOWN — search will fall back to slow MySQL.' -ForegroundColor Red
}

Write-Host '=== 2) Clear negative Meili health cache (if Redis up) ===' -ForegroundColor Cyan
& $Php -d opcache.enable_cli=0 -d opcache.enable=0 artisan tinker --execute="try { \App\Support\SerikCache::forget('serik_meili_health_v2'); echo 'cache_cleared'; } catch (Throwable `$e) { echo 'skip'; }" 2>$null

Write-Host "=== 3) Octane http://${HostAddress}:${Port} (workers=$Workers) ===" -ForegroundColor Cyan
Write-Host 'Static JS/CSS/images are served by RoadRunner, not the PHP workers.' -ForegroundColor Yellow
Write-Host 'Stop with: php artisan octane:stop' -ForegroundColor Yellow
& $Php -d opcache.enable_cli=0 -d opcache.enable=0 artisan serik:octane-start --host=$HostAddress --port=$Port --workers=$Workers --max-requests=500
