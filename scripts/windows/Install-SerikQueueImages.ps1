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

. (Join-Path $PSScriptRoot 'Serik-WindowsCommon.ps1')

$ServiceName = 'SerikQueueImages'
$DisplayName = 'Serik Queue Images Worker'
$Description = 'Laravel database queue worker for Serik TREB image WebP persistence (images queue).'

$AppRoot = Get-SerikAppRoot
$PhpExe = Get-SerikPhpExe
$Nssm = Find-SerikNssm

$StdoutLog = Join-Path $AppRoot 'storage\logs\queue-images.log'
$StderrLog = Join-Path $AppRoot 'storage\logs\queue-images-error.log'
$Parameters = 'artisan queue:work database --queue=images --sleep=3 --tries=3 --timeout=300 --memory=384 --max-jobs=50 --max-time=1800'

$logsDir = Split-Path $StdoutLog -Parent
if (-not (Test-Path -LiteralPath $logsDir)) {
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
    Invoke-SerikNssm -Nssm $Nssm -Arguments @('install', $ServiceName, $PhpExe)
} else {
    Write-Host "Updating existing service..." -ForegroundColor Yellow
}

Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'Application' -Value $PhpExe
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'AppDirectory' -Value $AppRoot
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'AppParameters' -Value $Parameters
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'DisplayName' -Value $DisplayName
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'Description' -Value $Description
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'AppStdout' -Value $StdoutLog
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'AppStderr' -Value $StderrLog
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'AppStdoutCreationDisposition' -Value '4'
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'AppStderrCreationDisposition' -Value '4'
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'AppRotateFiles' -Value '1'
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'AppRotateOnline' -Value '1'
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'AppRotateSeconds' -Value '86400'
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'AppRotateBytes' -Value '10485760'
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'Start' -Value 'SERVICE_AUTO_START'
Set-SerikNssmServiceExit -Nssm $Nssm -ServiceName $ServiceName -Action 'AppExit' -ExitCode 'Default' -RestartAction 'Restart'
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'AppRestartDelay' -Value '5000'
Set-SerikNssmService -Nssm $Nssm -ServiceName $ServiceName -Key 'AppThrottle' -Value '15000'

if ($null -ne $existing -and $existing.Status -eq 'Running') {
    Write-Host "Restarting service..." -ForegroundColor Yellow
    Invoke-SerikNssm -Nssm $Nssm -Arguments @('restart', $ServiceName)
} else {
    Write-Host "Starting service..." -ForegroundColor Yellow
    Invoke-SerikNssm -Nssm $Nssm -Arguments @('start', $ServiceName)
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
