#Requires -RunAsAdministrator
<#
.SYNOPSIS
    Install or update ALL Serik NSSM queue workers (high, images, low).

    Run once on every fresh Windows production server after git pull.
#>
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

. (Join-Path $PSScriptRoot 'Serik-WindowsCommon.ps1')

$AppRoot = Get-SerikAppRoot
$PhpExe = Get-SerikPhpExe
$Nssm = Find-SerikNssm

$logsDir = Join-Path $AppRoot 'storage\logs'
if (-not (Test-Path -LiteralPath $logsDir)) {
    New-Item -ItemType Directory -Path $logsDir -Force | Out-Null
}

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

function Install-OrUpdateWorker {
    param(
        [Parameter(Mandatory = $true)]
        [hashtable]$Worker
    )

    $name = $Worker.Name
    $stdout = Join-Path $logsDir $Worker.Stdout
    $stderr = Join-Path $logsDir $Worker.Stderr

    $existing = Get-Service -Name $name -ErrorAction SilentlyContinue
    if (-not $existing) {
        Write-Host "Installing $name..." -ForegroundColor Yellow
        Invoke-SerikNssm -Nssm $Nssm -Arguments @('install', $name, $PhpExe)
    } else {
        Write-Host "Updating $name..." -ForegroundColor Yellow
    }

    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'Application' -Value $PhpExe
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'AppDirectory' -Value $AppRoot
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'AppParameters' -Value $Worker.Parameters
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'DisplayName' -Value $Worker.DisplayName
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'Description' -Value $Worker.Description
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'AppStdout' -Value $stdout
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'AppStderr' -Value $stderr
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'AppStdoutCreationDisposition' -Value '4'
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'AppStderrCreationDisposition' -Value '4'
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'AppRotateFiles' -Value '1'
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'AppRotateOnline' -Value '1'
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'AppRotateSeconds' -Value '86400'
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'AppRotateBytes' -Value '10485760'
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'Start' -Value 'SERVICE_AUTO_START'
    Set-SerikNssmServiceExit -Nssm $Nssm -ServiceName $name -Action 'AppExit' -ExitCode 'Default' -RestartAction 'Restart'
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'AppRestartDelay' -Value '5000'
    Set-SerikNssmService -Nssm $Nssm -ServiceName $name -Key 'AppThrottle' -Value '15000'

    if ($null -ne $existing -and $existing.Status -eq 'Running') {
        Invoke-SerikNssm -Nssm $Nssm -Arguments @('restart', $name)
    } else {
        Invoke-SerikNssm -Nssm $Nssm -Arguments @('start', $name)
    }
}

Write-Host "=== Deploy all Serik queue workers ===" -ForegroundColor Cyan
Write-Host "App: $AppRoot"
Write-Host "PHP: $PhpExe"
Write-Host "NSSM: $Nssm"
Write-Host ""

foreach ($worker in $workers) {
    Install-OrUpdateWorker -Worker $worker
}

Start-Sleep -Seconds 3

Push-Location $AppRoot
try {
    & $PhpExe artisan queue:restart | Out-Null
    & $PhpExe artisan serik:queue:status
    if ($LASTEXITCODE -ne 0) {
        throw "serik:queue:status failed with exit code $LASTEXITCODE"
    }
} finally {
    Pop-Location
}

Write-Host ""
Write-Host "SUCCESS: All queue workers deployed." -ForegroundColor Green
