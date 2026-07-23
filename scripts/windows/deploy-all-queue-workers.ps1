#Requires -RunAsAdministrator
<#
.SYNOPSIS
    Install or update ALL Serik NSSM queue workers (high, images, low).

    Run once on every fresh Windows production server after git pull.
#>
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$AppRoot = if ($env:SERIK_APP_ROOT) {
    (Resolve-Path $env:SERIK_APP_ROOT).Path
} else {
    (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
}

$PhpExe = if ($env:SERIK_PHP_EXE -and (Test-Path $env:SERIK_PHP_EXE)) {
    (Resolve-Path $env:SERIK_PHP_EXE).Path
} else {
    (Get-Command php).Source
}

function Find-Nssm {
    $candidates = @(
        $env:SERIK_NSSM,
        'C:\nssm\nssm.exe',
        'C:\nssm\win64\nssm.exe',
        'C:\tools\nssm\nssm.exe'
    ) | Where-Object { $_ -and (Test-Path $_) }
    if ($candidates.Count -gt 0) { return (Resolve-Path $candidates[0]).Path }
    return (Get-Command nssm).Source
}

$Nssm = Find-Nssm
$logsDir = Join-Path $AppRoot 'storage\logs'
if (-not (Test-Path $logsDir)) { New-Item -ItemType Directory -Path $logsDir -Force | Out-Null }

$workers = @(
    @{
        Name = 'SerikQueueHigh'
        DisplayName = 'Serik Queue High Worker'
        Description = 'Laravel queue worker for live sync, geocode, and history (high queue).'
        Parameters = 'artisan queue:work database --queue=high --sleep=1 --tries=5 --timeout=200 --memory=384 --max-jobs=200 --max-time=3600'
        Stdout = 'queue-high.log'
        Stderr = 'queue-high-error.log'
    },
    @{
        Name = 'SerikQueueImages'
        DisplayName = 'Serik Queue Images Worker'
        Description = 'Laravel queue worker for TREB image WebP persistence (images queue).'
        Parameters = 'artisan queue:work database --queue=images --sleep=3 --tries=3 --timeout=300 --memory=384 --max-jobs=50 --max-time=1800'
        Stdout = 'queue-images.log'
        Stderr = 'queue-images-error.log'
    },
    @{
        Name = 'SerikQueueLow'
        DisplayName = 'Serik Queue Low Worker'
        Description = 'Laravel queue worker for backlog geocode and maintenance (low queue).'
        Parameters = 'artisan queue:work database --queue=low --sleep=2 --tries=4 --timeout=120 --memory=256 --max-jobs=100 --max-time=3600'
        Stdout = 'queue-low.log'
        Stderr = 'queue-low-error.log'
    }
)

function Install-OrUpdateWorker($worker) {
    $name = $worker.Name
    $stdout = Join-Path $logsDir $worker.Stdout
    $stderr = Join-Path $logsDir $worker.Stderr

    $existing = Get-Service -Name $name -ErrorAction SilentlyContinue
    if (-not $existing) {
        Write-Host "Installing $name..." -ForegroundColor Yellow
        & $Nssm install $name $PhpExe | Out-Null
    } else {
        Write-Host "Updating $name..." -ForegroundColor Yellow
    }

    & $Nssm set $name Application $PhpExe | Out-Null
    & $Nssm set $name AppDirectory $AppRoot | Out-Null
    & $Nssm set $name AppParameters $worker.Parameters | Out-Null
    & $Nssm set $name DisplayName $worker.DisplayName | Out-Null
    & $Nssm set $name Description $worker.Description | Out-Null
    & $Nssm set $name AppStdout $stdout | Out-Null
    & $Nssm set $name AppStderr $stderr | Out-Null
    & $Nssm set $name AppStdoutCreationDisposition 4 | Out-Null
    & $Nssm set $name AppStderrCreationDisposition 4 | Out-Null
    & $Nssm set $name AppRotateFiles 1 | Out-Null
    & $Nssm set $name AppRotateOnline 1 | Out-Null
    & $Nssm set $name AppRotateSeconds 86400 | Out-Null
    & $Nssm set $name AppRotateBytes 10485760 | Out-Null
    & $Nssm set $name Start SERVICE_AUTO_START | Out-Null
    & $Nssm set $name AppExit Default Restart | Out-Null
    & $Nssm set $name AppRestartDelay 5000 | Out-Null
    & $Nssm set $name AppThrottle 15000 | Out-Null

    if ($existing -and $existing.Status -eq 'Running') {
        & $Nssm restart $name | Out-Null
    } else {
        & $Nssm start $name | Out-Null
    }
}

Write-Host "=== Deploy all Serik queue workers ===" -ForegroundColor Cyan
Write-Host "App: $AppRoot"
Write-Host "PHP: $PhpExe"
Write-Host "NSSM: $Nssm"
Write-Host ""

foreach ($w in $workers) {
    Install-OrUpdateWorker $w
}

Start-Sleep -Seconds 3

Push-Location $AppRoot
& $PhpExe artisan queue:restart | Out-Null
& $PhpExe artisan serik:queue:status
Pop-Location

Write-Host ""
Write-Host "SUCCESS: All queue workers deployed." -ForegroundColor Green
