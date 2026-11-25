<#
.SYNOPSIS
  Start Laravel Octane (RoadRunner) bound to localhost for local / IIS reverse-proxy use.

.DESCRIPTION
  Does NOT touch IIS, NSSM queue workers, Meilisearch, or production .env.
  Default bind: 127.0.0.1:8001 — keep this private; IIS should reverse-proxy to it.

.EXAMPLE
  .\ops\windows\start-octane.ps1
  .\ops\windows\start-octane.ps1 -Workers 2 -Port 8001 -MaxRequests 250
#>
param(
    [string]$HostAddress = '127.0.0.1',
    [int]$Port = 8000,
    [int]$Workers = 4,
    [int]$MaxRequests = 250,
    [string]$Php = 'C:\xampp\php\php.exe'
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
Set-Location $Root

if (-not (Test-Path $Php)) {
    throw "PHP not found at $Php"
}

# Prefer rr.exe on Windows; Octane also accepts ExecutableFinder('rr').
if (-not (Test-Path (Join-Path $Root 'rr.exe')) -and -not (Test-Path (Join-Path $Root 'rr'))) {
    Write-Host 'RoadRunner binary missing. Download with:' -ForegroundColor Yellow
    Write-Host "  & `"$Php`" -d opcache.enable_cli=0 .\vendor\bin\rr get-binary -n"
    throw 'rr.exe / rr not found in project root'
}

Write-Host "Starting Octane RoadRunner on http://${HostAddress}:${Port} (workers=$Workers, max-requests=$MaxRequests)" -ForegroundColor Cyan

# Use serik:octane-start on Windows — stock octane:start requires pcntl signals.
& $Php -d opcache.enable_cli=0 artisan serik:octane-start `
    --host=$HostAddress `
    --port=$Port `
    --workers=$Workers `
    --max-requests=$MaxRequests
