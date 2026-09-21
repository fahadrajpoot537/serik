# Fix Serik admin HTTP 500 / DataTables Ajax failures on the IIS host.
#
# Run as Administrator on C:\project\serik:
#   powershell -NoProfile -ExecutionPolicy Bypass -File .\ops\windows\Fix-SerikAdmin500.ps1
#
# What this does:
# 1) Restarts NSSM queue workers that lock storage\logs\laravel*.log
# 2) Clears / recreates log files
# 3) Forces LOG_CHANNEL=stack (ignore_exceptions) in .env
# 4) git pull + optimize:clear + IIS app pool recycle
# 5) Prints diag_infra URL result hint

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
    Write-Host "==== $Msg ====" -ForegroundColor Cyan
}

if (-not (Test-Path $Root)) {
    throw "Root not found: $Root"
}

Set-Location $Root

Write-Step "0) Pre-check"
Write-Host "Root: $Root"
Write-Host "User: $env:USERNAME"
git -C $Root rev-parse --short HEAD 2>$null
git -C $Root status -sb 2>$null

Write-Step "1) Restart NSSM queue workers (release log file locks)"
$services = @("SerikQueueHigh", "SerikQueueLow", "SerikQueueGhl", "SerikQueueSearch", "SerikMeilisearch")
foreach ($svc in $services) {
    $status = & nssm status $svc 2>$null
    if ($LASTEXITCODE -ne 0 -and -not $status) {
        Write-Host "$svc : not installed (skip)"
        continue
    }
    Write-Host "$svc : before=$status"
    if ($svc -eq "SerikMeilisearch") {
        # Do not bounce search unless it is stopped
        if ("$status" -notmatch "SERVICE_RUNNING") {
            & nssm start $svc 2>$null | Out-Null
        }
        continue
    }
    & nssm restart $svc 2>&1 | ForEach-Object { Write-Host $_ }
    Start-Sleep -Seconds 1
    $after = & nssm status $svc 2>$null
    Write-Host "$svc : after=$after"
}

Write-Step "2) Fix storage\logs permissions + recreate laravel logs"
$logDir = Join-Path $Root "storage\logs"
New-Item -ItemType Directory -Force -Path $logDir | Out-Null
try {
    & icacls $logDir /grant "IIS_IUSRS:(OI)(CI)M" /T | Out-Null
    & icacls $logDir /grant "IUSR:(OI)(CI)M" /T | Out-Null
} catch {
    Write-Host "icacls warn: $($_.Exception.Message)"
}

Get-ChildItem -Path $logDir -Filter "laravel*.log" -ErrorAction SilentlyContinue | ForEach-Object {
    try {
        Remove-Item $_.FullName -Force -ErrorAction Stop
        Write-Host "deleted $($_.Name)"
    } catch {
        $aside = "$($_.FullName).locked-$(Get-Date -Format 'yyyyMMddHHmmss')"
        try {
            Move-Item $_.FullName $aside -Force
            Write-Host "renamed $($_.Name) -> $(Split-Path $aside -Leaf)"
        } catch {
            Write-Host "LOCKED $($_.Name): $($_.Exception.Message)"
        }
    }
}

$stamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
$probeLine = "[$stamp] production.INFO: Fix-SerikAdmin500 probe`r`n"
$today = Join-Path $logDir ("laravel-{0}.log" -f (Get-Date -Format "yyyy-MM-dd"))
$single = Join-Path $logDir "laravel.log"
try {
    Add-Content -Path $today -Value $probeLine -Encoding utf8
    Write-Host "probe OK: $(Split-Path $today -Leaf)"
} catch {
    Write-Host "probe FAIL today: $($_.Exception.Message)" -ForegroundColor Yellow
}
try {
    Add-Content -Path $single -Value $probeLine -Encoding utf8
    Write-Host "probe OK: laravel.log"
} catch {
    Write-Host "probe FAIL single: $($_.Exception.Message)" -ForegroundColor Yellow
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

Write-Step "4) git pull + optimize:clear"
if (-not $SkipGitPull) {
    git -C $Root fetch origin 2>&1 | ForEach-Object { Write-Host $_ }
    git -C $Root pull --ff-only origin main 2>&1 | ForEach-Object { Write-Host $_ }
} else {
    Write-Host "SkipGitPull set — not pulling"
}

php artisan optimize:clear 2>&1 | ForEach-Object { Write-Host $_ }

Write-Step "5) Recycle IIS app pool"
try {
    Import-Module WebAdministration -ErrorAction Stop
    Restart-WebAppPool -Name $AppPool
    Write-Host "Recycled app pool: $AppPool"
} catch {
    Write-Host "App pool recycle skipped ($AppPool): $($_.Exception.Message)"
    Write-Host "Fallback: iisreset /noforce (manual if needed)"
}

Write-Step "6) Verify"
php artisan tinker --execute="echo 'log='.config('logging.default').PHP_EOL.'cache='.config('cache.default').PHP_EOL.'session='.config('session.driver').PHP_EOL;" 2>&1 | ForEach-Object { Write-Host $_ }

Write-Host ""
Write-Host "Browser checks:" -ForegroundColor Green
Write-Host "  https://serik.ca/clear-serik-cache.php?key=serik2026clear&diag_infra=1"
Write-Host "  https://serik.ca/health/live"
Write-Host "  https://serik.ca/admin  (login, open Properties / Property Visits / Reviews)"
Write-Host ""
Write-Host "Done."
