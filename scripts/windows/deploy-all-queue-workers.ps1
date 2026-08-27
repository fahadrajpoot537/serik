#Requires -RunAsAdministrator
<#
.SYNOPSIS
    Install or update Serik NSSM queue workers.

    Lanes (isolated - imports NEVER share a process with user-facing queues):
      high              SerikQueueHigh
      images            SerikQueueImages
      low               SerikQueueLow          (also drains search-index when used)
      imports           SerikQueueImports      (sold-history / archive only)
      ghl               SerikQueueGhl
      aux               SerikQueueAux          (critical,default,emails,notifications)
      cache-refresh     SerikQueueCacheRefresh (background warm only)

    Existing high/images/low job destinations are unchanged.
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
        Description = 'Laravel queue worker for live sync, geocode, history, auth emails (high queue).'
        Parameters = '-d max_execution_time=0 artisan queue:work database --queue=high --sleep=1 --tries=5 --timeout=200 --memory=384 --max-jobs=200 --max-time=1800'
        Stdout = 'queue-high.log'
        Stderr = 'queue-high-error.log'
    },
    @{
        Name = 'SerikQueueImages'
        DisplayName = 'Serik Queue Images Worker'
        Description = 'Laravel queue worker for TREB image WebP persistence (images queue).'
        Parameters = '-d max_execution_time=0 artisan queue:work database --queue=images --sleep=3 --tries=3 --timeout=300 --memory=384 --max-jobs=50 --max-time=1800'
        Stdout = 'queue-images.log'
        Stderr = 'queue-images-error.log'
    },
    @{
        Name = 'SerikQueueLow'
        DisplayName = 'Serik Queue Low Worker'
        Description = 'Laravel queue worker for backlog/maintenance; also drains search-index if opted in.'
        # search-index first so dedicated indexing wins when SERIK_QUEUE_SEARCH=search-index
        # timeout=300 aligns with SearchBatchJob::$timeout (was 120 - prematurely killed Meili drains)
        Parameters = '-d max_execution_time=0 artisan queue:work database --queue=search-index,low --sleep=2 --tries=4 --timeout=300 --memory=384 --max-jobs=100 --max-time=1800'
        Stdout = 'queue-low.log'
        Stderr = 'queue-low-error.log'
    },
    @{
        Name = 'SerikQueueImports'
        DisplayName = 'Serik Queue Imports Worker'
        Description = 'Laravel queue worker for TREB archive / sold-history ONLY (imports). Never user-facing.'
        Parameters = '-d max_execution_time=0 artisan queue:work database --queue=imports --sleep=1 --tries=5 --timeout=300 --memory=512 --max-jobs=40 --max-time=1800'
        Stdout = 'queue-imports.log'
        Stderr = 'queue-imports-error.log'
    },
    @{
        Name = 'SerikQueueGhl'
        DisplayName = 'Serik Queue GHL Worker'
        Description = 'Laravel queue worker for GoHighLevel MLS sync (ghl queue).'
        Parameters = '-d max_execution_time=0 artisan queue:work database --queue=ghl --sleep=1 --tries=8 --timeout=180 --memory=256 --max-jobs=100 --max-time=1800'
        Stdout = 'queue-ghl.log'
        Stderr = 'queue-ghl-error.log'
    },
    @{
        Name = 'SerikQueueAux'
        DisplayName = 'Serik Queue Aux Worker'
        Description = 'Laravel queue worker for critical/default/emails/notifications lanes.'
        Parameters = '-d max_execution_time=0 artisan queue:work database --queue=critical,emails,notifications,default --sleep=2 --tries=5 --timeout=120 --memory=256 --max-jobs=100 --max-time=1800'
        Stdout = 'queue-aux.log'
        Stderr = 'queue-aux-error.log'
    },
    @{
        Name = 'SerikQueueCacheRefresh'
        DisplayName = 'Serik Queue Cache Refresh Worker'
        Description = 'Laravel queue worker for background cache warming (cache-refresh). Never user-facing.'
        Parameters = '-d max_execution_time=0 artisan queue:work database --queue=cache-refresh --sleep=2 --tries=2 --timeout=240 --memory=256 --max-jobs=50 --max-time=1800'
        Stdout = 'queue-cache-refresh.log'
        Stderr = 'queue-cache-refresh-error.log'
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
    # Self-healing: NSSM restarts worker on crash / unexpected exit (reboot + deploy safe)
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

# Deployment self-heal marker - schedule heal / queue:restart picks this up
$restartFlag = Join-Path $AppRoot 'storage\framework\queue-restart.flag'
Set-Content -LiteralPath $restartFlag -Value (Get-Date).ToString('o') -Encoding ascii

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
Write-Host "SUCCESS: All queue workers deployed (high/images/low/imports/ghl/aux/cache-refresh)." -ForegroundColor Green
Write-Host "Imports are isolated - they cannot block high/low/ghl/emails/cache-refresh." -ForegroundColor Green
