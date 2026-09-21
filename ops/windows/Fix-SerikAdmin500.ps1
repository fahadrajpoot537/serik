# Fix Serik admin HTTP 500 / DataTables Ajax failures on the IIS host.
#
# Run as Administrator on C:\project\serik:
#   powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\windows\Fix-SerikAdmin500.ps1
#
# What this does:
# 1) Restarts NSSM queue workers that lock storage\logs\laravel*.log
# 2) Clears / recreates log files
# 3) Forces LOG_CHANNEL=stack in .env
# 4) git pull + FAST cache wipe (file-cache safe) + IIS app pool recycle
# 5) Prints verify hints

[CmdletBinding()]
param(
    [string]$Root = "C:\project\serik",
    [string]$AppPool = "SerikAppPool",
    [switch]$SkipGitPull
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Continue"

function Write-Step([string]$Msg) {
    Write-Host ""
    Write-Host ("==== {0} ====" -f $Msg) -ForegroundColor Cyan
}

function Find-Nssm {
    $candidates = @(
        $env:SERIK_NSSM,
        (Get-Command nssm -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source),
        'C:\nssm\nssm.exe',
        'C:\nssm\win64\nssm.exe',
        'C:\tools\nssm\nssm.exe',
        'C:\Program Files\nssm\nssm.exe'
    ) | Where-Object { $_ -and (Test-Path $_) }
    if ($candidates) { return [string]$candidates[0] }
    return $null
}

function Restart-SerikService([string]$Name, [string]$NssmPath) {
    $svc = Get-Service -Name $Name -ErrorAction SilentlyContinue
    if (-not $svc) {
        Write-Host ("{0} : not installed (skip)" -f $Name)
        return
    }
    Write-Host ("{0} : before={1}" -f $Name, $svc.Status)
    if ($Name -eq "SerikMeilisearch") {
        if ($svc.Status -ne "Running") {
            try { Start-Service -Name $Name -ErrorAction Stop; Write-Host ("{0} : started" -f $Name) }
            catch { Write-Host ("{0} : start failed: {1}" -f $Name, $_.Exception.Message) }
        }
        return
    }
    try {
        if ($NssmPath) {
            & $NssmPath restart $Name 2>&1 | ForEach-Object { Write-Host $_ }
        } else {
            Restart-Service -Name $Name -Force -ErrorAction Stop
        }
    } catch {
        try {
            Stop-Service -Name $Name -Force -ErrorAction SilentlyContinue
            Start-Sleep -Seconds 1
            Start-Service -Name $Name -ErrorAction Stop
        } catch {
            Write-Host ("{0} : restart failed: {1}" -f $Name, $_.Exception.Message)
            return
        }
    }
    Start-Sleep -Seconds 1
    $after = (Get-Service -Name $Name -ErrorAction SilentlyContinue).Status
    Write-Host ("{0} : after={1}" -f $Name, $after)
}

if (-not (Test-Path $Root)) {
    throw ("Root not found: {0}" -f $Root)
}

Set-Location $Root

Write-Step "0) Pre-check"
Write-Host ("Root: {0}" -f $Root)
Write-Host ("User: {0}" -f $env:USERNAME)
git -C $Root rev-parse --short HEAD 2>$null
git -C $Root status -sb 2>$null

Write-Step "1) Restart queue workers (Get-Service; nssm optional)"
$nssm = Find-Nssm
if ($nssm) {
    Write-Host ("nssm: {0}" -f $nssm)
} else {
    Write-Host "nssm not on PATH - using Restart-Service"
}
$services = @(
    "SerikQueueHigh",
    "SerikQueueLow",
    "SerikQueueGhl",
    "SerikQueueSearch",
    "SerikQueueImages",
    "SerikQueueImports",
    "SerikMeilisearch"
)
foreach ($svc in $services) {
    Restart-SerikService -Name $svc -NssmPath $nssm
}

Write-Step "2) Fix storage\logs permissions + recreate laravel logs"
$logDir = Join-Path $Root "storage\logs"
New-Item -ItemType Directory -Force -Path $logDir | Out-Null
try {
    & icacls $logDir /grant "IIS_IUSRS:(OI)(CI)M" /T | Out-Null
    & icacls $logDir /grant "IUSR:(OI)(CI)M" /T | Out-Null
} catch {
    Write-Host ("icacls warn: {0}" -f $_.Exception.Message)
}

Get-ChildItem -Path $logDir -Filter "laravel*.log" -ErrorAction SilentlyContinue | ForEach-Object {
    try {
        Remove-Item $_.FullName -Force -ErrorAction Stop
        Write-Host ("deleted {0}" -f $_.Name)
    } catch {
        $aside = "{0}.locked-{1}" -f $_.FullName, (Get-Date -Format "yyyyMMddHHmmss")
        try {
            Move-Item $_.FullName $aside -Force
            Write-Host ("renamed {0} -> {1}" -f $_.Name, (Split-Path $aside -Leaf))
        } catch {
            Write-Host ("LOCKED {0}: {1}" -f $_.Name, $_.Exception.Message)
        }
    }
}

$stamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
$probeLine = "[{0}] production.INFO: Fix-SerikAdmin500 probe" -f $stamp
$today = Join-Path $logDir ("laravel-{0}.log" -f (Get-Date -Format "yyyy-MM-dd"))
$single = Join-Path $logDir "laravel.log"
try {
    Add-Content -Path $today -Value $probeLine -Encoding utf8
    Write-Host ("probe OK: {0}" -f (Split-Path $today -Leaf))
} catch {
    Write-Host ("probe FAIL today: {0}" -f $_.Exception.Message) -ForegroundColor Yellow
}
try {
    Add-Content -Path $single -Value $probeLine -Encoding utf8
    Write-Host "probe OK: laravel.log"
} catch {
    Write-Host ("probe FAIL single: {0}" -f $_.Exception.Message) -ForegroundColor Yellow
}

Write-Step "3) Force LOG_CHANNEL=stack in .env"
$envPath = Join-Path $Root ".env"
if (Test-Path $envPath) {
    $raw = Get-Content $envPath -Raw
    if ($raw -match '(?m)^LOG_CHANNEL=') {
        $raw = [regex]::Replace($raw, '(?m)^LOG_CHANNEL=.*$', 'LOG_CHANNEL=stack')
    } else {
        $raw = $raw.TrimEnd() + "`r`n`r`nLOG_CHANNEL=stack`r`n"
    }
    if ($raw -notmatch '(?m)^LOG_STACK=') {
        $raw = $raw.TrimEnd() + "`r`nLOG_STACK=single`r`n"
    }
    Set-Content -Path $envPath -Value $raw -Encoding utf8 -NoNewline
    Write-Host "LOG_CHANNEL=stack set"
} else {
    Write-Host ".env missing!" -ForegroundColor Red
}

$cfg = Join-Path $Root "bootstrap\cache\config.php"
if (Test-Path $cfg) {
    Remove-Item $cfg -Force
    Write-Host "deleted bootstrap\cache\config.php"
}

Write-Step "4) git pull + FAST file-cache wipe (skip slow artisan cache:clear)"
if (-not $SkipGitPull) {
    git -C $Root fetch origin 2>&1 | ForEach-Object { Write-Host $_ }
    git -C $Root pull --ff-only origin main 2>&1 | ForEach-Object { Write-Host $_ }
} else {
    Write-Host "SkipGitPull set - not pulling"
}

# File cache: artisan cache:clear walks every file and often fails on IIS locks.
# cmd rd /s /q is much faster than Remove-Item on huge trees.
$cacheData = Join-Path $Root "storage\framework\cache\data"
$viewCache = Join-Path $Root "storage\framework\views"
$bootCache = Join-Path $Root "bootstrap\cache"

foreach ($dir in @($cacheData, $viewCache)) {
    if (Test-Path $dir) {
        Write-Host ("Wiping {0} (cmd rd) ..." -f $dir)
        cmd /c "rd /s /q `"$dir`"" 2>$null | Out-Null
        New-Item -ItemType Directory -Force -Path $dir | Out-Null
        Write-Host ("Wiped {0}" -f $dir)
    }
}

try {
    & icacls (Join-Path $Root "storage") /grant "IIS_IUSRS:(OI)(CI)M" /T | Out-Null
    & icacls (Join-Path $Root "storage") /grant "IUSR:(OI)(CI)M" /T | Out-Null
    & icacls $bootCache /grant "IIS_IUSRS:(OI)(CI)M" /T | Out-Null
} catch {
    Write-Host ("storage icacls warn: {0}" -f $_.Exception.Message)
}

Get-ChildItem -Path $bootCache -Filter "*.php" -ErrorAction SilentlyContinue | ForEach-Object {
    try {
        Remove-Item $_.FullName -Force -ErrorAction Stop
        Write-Host ("deleted bootstrap cache {0}" -f $_.Name)
    } catch {
        Write-Host ("bootstrap cache locked {0}" -f $_.Name)
    }
}

php artisan view:clear 2>&1 | ForEach-Object { Write-Host $_ }
php artisan route:clear 2>&1 | ForEach-Object { Write-Host $_ }
php artisan config:clear 2>&1 | ForEach-Object { Write-Host $_ }
Write-Host "Skipped artisan cache:clear (too slow / permission errors on file driver)"

Write-Step "5) Recycle IIS app pool"
try {
    Import-Module WebAdministration -ErrorAction Stop
    Restart-WebAppPool -Name $AppPool
    Write-Host ("Recycled app pool: {0}" -f $AppPool)
} catch {
    Write-Host ("App pool recycle skipped ({0}): {1}" -f $AppPool, $_.Exception.Message)
    Write-Host "Fallback: iisreset /noforce (manual if needed)"
}

Write-Step "6) Verify"
php artisan tinker --execute="echo 'log='.config('logging.default').PHP_EOL.'cache='.config('cache.default').PHP_EOL.'session='.config('session.driver').PHP_EOL;" 2>&1 | ForEach-Object { Write-Host $_ }

Write-Host ""
Write-Host "Browser checks:" -ForegroundColor Green
Write-Host '  https://serik.ca/clear-serik-cache.php?key=serik2026clear&diag_infra=1'
Write-Host '  https://serik.ca/health/live'
Write-Host '  https://serik.ca/admin'
Write-Host ""
Write-Host "Done."
Write-Host "Note: CACHE_STORE=file is slow. Prefer CACHE_STORE=redis + SESSION_DRIVER=redis on live."
