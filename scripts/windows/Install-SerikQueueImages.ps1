#Requires -RunAsAdministrator
<#
.SYNOPSIS
    Install or update the SerikQueueImages NSSM Windows service.

.DESCRIPTION
    Idempotent deployment for the Laravel images queue worker.
    Run from an elevated PowerShell prompt, or use install-serik-queue-images.cmd.

    Environment overrides (optional):
      SERIK_APP_ROOT   — Laravel project root
      SERIK_PHP_EXE    — path to php.exe
      SERIK_NSSM       — path to nssm.exe
#>
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$ServiceName = 'SerikQueueImages'
$DisplayName = 'Serik Queue Images Worker'
$Description = 'Laravel database queue worker for Serik TREB image WebP persistence (images queue).'

$AppRoot = if ($env:SERIK_APP_ROOT) {
    (Resolve-Path $env:SERIK_APP_ROOT).Path
} else {
    (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
}

$PhpExe = if ($env:SERIK_PHP_EXE -and (Test-Path $env:SERIK_PHP_EXE)) {
    (Resolve-Path $env:SERIK_PHP_EXE).Path
} else {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if (-not $cmd) {
        throw 'php.exe not found. Set SERIK_PHP_EXE or add PHP to PATH.'
    }
    $cmd.Source
}

function Find-Nssm {
    $candidates = @(
        $env:SERIK_NSSM,
        'C:\nssm\nssm.exe',
        'C:\nssm\win64\nssm.exe',
        'C:\nssm\win32\nssm.exe',
        'C:\tools\nssm\nssm.exe',
        'C:\Program Files\nssm\nssm.exe',
        'C:\Program Files (x86)\nssm\nssm.exe'
    ) | Where-Object { $_ -and (Test-Path $_) }

    if ($candidates.Count -gt 0) {
        return (Resolve-Path $candidates[0]).Path
    }

    $cmd = Get-Command nssm -ErrorAction SilentlyContinue
    if ($cmd) {
        return $cmd.Source
    }

    throw 'nssm.exe not found. Download from https://nssm.cc/download and set SERIK_NSSM.'
}

$Nssm = Find-Nssm

$StdoutLog = Join-Path $AppRoot 'storage\logs\queue-images.log'
$StderrLog = Join-Path $AppRoot 'storage\logs\queue-images-error.log'
$Parameters = 'artisan queue:work database --queue=images --sleep=3 --tries=3 --timeout=300 --memory=384 --max-jobs=50 --max-time=1800'

$logsDir = Split-Path $StdoutLog -Parent
if (-not (Test-Path $logsDir)) {
    New-Item -ItemType Directory -Path $logsDir -Force | Out-Null
}

Write-Host "=== SerikQueueImages deployment ===" -ForegroundColor Cyan
Write-Host "App root : $AppRoot"
Write-Host "PHP      : $PhpExe"
Write-Host "NSSM     : $Nssm"
Write-Host "Service  : $ServiceName"
Write-Host ""

$existing = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue

if (-not $existing) {
    Write-Host "Installing new service..." -ForegroundColor Yellow
    & $Nssm install $ServiceName $PhpExe | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "nssm install failed with exit code $LASTEXITCODE"
    }
} else {
    Write-Host "Updating existing service..." -ForegroundColor Yellow
}

function Set-Nssm([string]$Key, [string]$Value) {
    & $Nssm set $ServiceName $Key $Value | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "nssm set $Key failed with exit code $LASTEXITCODE"
    }
}

Set-Nssm 'Application' $PhpExe
Set-Nssm 'AppDirectory' $AppRoot
Set-Nssm 'AppParameters' $Parameters
Set-Nssm 'DisplayName' $DisplayName
Set-Nssm 'Description' $Description
Set-Nssm 'AppStdout' $StdoutLog
Set-Nssm 'AppStderr' $StderrLog
Set-Nssm 'AppStdoutCreationDisposition' '4'
Set-Nssm 'AppStderrCreationDisposition' '4'
Set-Nssm 'AppRotateFiles' '1'
Set-Nssm 'AppRotateOnline' '1'
Set-Nssm 'AppRotateSeconds' '86400'
Set-Nssm 'AppRotateBytes' '10485760'
Set-Nssm 'Start' 'SERVICE_AUTO_START'
Set-Nssm 'AppExit' 'Default' 'Restart'
Set-Nssm 'AppRestartDelay' '5000'
Set-Nssm 'AppThrottle' '15000'

if ($existing -and $existing.Status -eq 'Running') {
    Write-Host "Restarting service..." -ForegroundColor Yellow
    & $Nssm restart $ServiceName | Out-Null
} else {
    Write-Host "Starting service..." -ForegroundColor Yellow
    & $Nssm start $ServiceName | Out-Null
}

Start-Sleep -Seconds 3

Write-Host ""
Write-Host "=== Verification ===" -ForegroundColor Cyan
& sc.exe query $ServiceName
Write-Host ""

Push-Location $AppRoot
try {
    & $PhpExe artisan serik:queue:status
    if ($LASTEXITCODE -ne 0) {
        throw "serik:queue:status failed with exit code $LASTEXITCODE"
    }
} finally {
    Pop-Location
}

$state = (Get-Service -Name $ServiceName -ErrorAction Stop).Status
if ($state -ne 'Running') {
    throw "Service $ServiceName is not RUNNING (current: $state). Check $StderrLog"
}

Write-Host ""
Write-Host "SUCCESS: $ServiceName is RUNNING." -ForegroundColor Green
