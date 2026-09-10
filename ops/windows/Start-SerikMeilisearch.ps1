# Start (or verify) local Meilisearch for Serik XAMPP / Windows without Docker.
#
# Usage:
#   powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\windows\Start-SerikMeilisearch.ps1
#
# Prefers NSSM service SerikMeilisearch when installed; otherwise launches
# storage\meilisearch\start-meilisearch.bat (master key must match .env MEILISEARCH_KEY).

[CmdletBinding()]
param(
    [string]$ServiceName = "SerikMeilisearch",
    [string]$HealthUrl = "http://127.0.0.1:7700/health",
    [string]$BatPath = ""
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Continue"

function Test-MeiliHealthy {
    try {
        $resp = Invoke-WebRequest -Uri $HealthUrl -UseBasicParsing -TimeoutSec 2
        return ($resp.StatusCode -eq 200) -and ($resp.Content -match 'available')
    } catch {
        return $false
    }
}

if (Test-MeiliHealthy) {
    Write-Host "Meilisearch already healthy at $HealthUrl"
    exit 0
}

$svc = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
if ($svc) {
    if ($svc.Status -ne "Running") {
        Write-Host "Starting Windows service $ServiceName ..."
        Start-Service -Name $ServiceName
        Start-Sleep -Seconds 2
    }
    if (Test-MeiliHealthy) {
        Write-Host "Meilisearch healthy via service $ServiceName"
        exit 0
    }
    Write-Host "Service $ServiceName did not become healthy; falling back to bat launcher."
}

if (-not $BatPath) {
    # $PSScriptRoot = <repo>/ops/windows → repo root is parent of ops
    $root = Split-Path -Parent $PSScriptRoot
    $BatPath = Join-Path $root "storage\meilisearch\start-meilisearch.bat"
}

if (-not (Test-Path $BatPath)) {
    Write-Error "Meilisearch start script not found: $BatPath"
    exit 1
}

Write-Host "Launching $BatPath ..."
Start-Process -FilePath $BatPath -WindowStyle Hidden
Start-Sleep -Seconds 3

if (Test-MeiliHealthy) {
    Write-Host "Meilisearch healthy at $HealthUrl"
    exit 0
}

Write-Error "Meilisearch still unhealthy after start attempt. Check master key / port 7700 / data.ms path."
exit 1
