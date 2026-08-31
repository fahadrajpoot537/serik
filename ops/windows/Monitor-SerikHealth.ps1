# Monitor Serik liveness and recycle DefaultAppPool after consecutive failures.
#
# Usage (on the IIS server):
#   powershell -NoProfile -ExecutionPolicy Bypass -File .\Monitor-SerikHealth.ps1
#
# Optional parameters:
#   -LiveUrl "https://serik.ca/health/live"
#   -FailureThreshold 3
#   -TimeoutSeconds 8
#   -CooldownMinutes 10
#   -LogPath "C:\project\serik\storage\logs\serik-health-monitor.log"
#
# Recycles ONLY DefaultAppPool. Never restarts Windows, MySQL, Memurai, or Meilisearch.
# Do not auto-install a Scheduled Task; see the documented command at the bottom of
# docs/serik-500-deployment.md.

[CmdletBinding()]
param(
    [string]$LiveUrl = "https://serik.ca/health/live",
    [int]$FailureThreshold = 3,
    [int]$TimeoutSeconds = 8,
    [int]$CooldownMinutes = 10,
    [string]$StatePath = "C:\ProgramData\Serik\health-monitor-state.json",
    [string]$LogPath = "C:\project\serik\storage\logs\serik-health-monitor.log"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Continue"

function Write-MonitorLog([string]$Message) {
    $line = "{0:o} {1}" -f (Get-Date), $Message
    $dir = Split-Path -Parent $LogPath
    if ($dir -and -not (Test-Path $dir)) {
        New-Item -ItemType Directory -Force -Path $dir | Out-Null
    }
    Add-Content -Path $LogPath -Value $line -ErrorAction SilentlyContinue
    Write-Host $line
}

function Get-State {
    if (Test-Path $StatePath) {
        try { return Get-Content -Raw -Path $StatePath | ConvertFrom-Json } catch { }
    }
    return [pscustomobject]@{ consecutiveFailures = 0; lastRecycleUtc = $null }
}

function Save-State($State) {
    $dir = Split-Path -Parent $StatePath
    if ($dir -and -not (Test-Path $dir)) {
        New-Item -ItemType Directory -Force -Path $dir | Out-Null
    }
    ($State | ConvertTo-Json) | Set-Content -Path $StatePath -Encoding UTF8
}

function Test-Liveness([string]$Url, [int]$Timeout) {
    try {
        $resp = Invoke-WebRequest -Uri $Url -Method GET -TimeoutSec $Timeout -UseBasicParsing
        $ok = ($resp.StatusCode -eq 200) -and ($resp.Content -match '"status"\s*:\s*"ok"')
        return @{ ok = $ok; status = [int]$resp.StatusCode; error = $null }
    } catch {
        return @{ ok = $false; status = 0; error = $_.Exception.Message }
    }
}

$state = Get-State
$result = Test-Liveness -Url $LiveUrl -Timeout $TimeoutSeconds

if ($result.ok) {
    Write-MonitorLog "OK status=$($result.status) url=$LiveUrl failures=0"
    $state.consecutiveFailures = 0
    Save-State $state
    exit 0
}

$state.consecutiveFailures = [int]$state.consecutiveFailures + 1
Write-MonitorLog "FAIL status=$($result.status) error=$($result.error) consecutive=$($state.consecutiveFailures)/$FailureThreshold"

if ($state.consecutiveFailures -lt $FailureThreshold) {
    Save-State $state
    exit 2
}

$now = [datetime]::UtcNow
if ($state.lastRecycleUtc) {
    try {
        $last = [datetime]::Parse($state.lastRecycleUtc).ToUniversalTime()
        $elapsed = $now - $last
        if ($elapsed.TotalMinutes -lt $CooldownMinutes) {
            Write-MonitorLog "COOLDOWN skip recycle last=$($state.lastRecycleUtc) minutes=$([math]::Round($elapsed.TotalMinutes,1))"
            Save-State $state
            exit 3
        }
    } catch { }
}

Write-MonitorLog "RECYCLE DefaultAppPool after $FailureThreshold consecutive liveness failures"
try {
    Import-Module WebAdministration -ErrorAction Stop
    Restart-WebAppPool -Name "DefaultAppPool"
} catch {
    Write-MonitorLog "RECYCLE_FAILED $($_.Exception.Message)"
    Save-State $state
    exit 4
}

Start-Sleep -Seconds 8
$verify = Test-Liveness -Url $LiveUrl -Timeout $TimeoutSeconds
if ($verify.ok) {
    Write-MonitorLog "RECOVERED status=$($verify.status) after DefaultAppPool recycle"
    $state.consecutiveFailures = 0
    $state.lastRecycleUtc = $now.ToString("o")
    Save-State $state
    exit 0
}

Write-MonitorLog "STILL_DOWN status=$($verify.status) error=$($verify.error) after recycle"
$state.lastRecycleUtc = $now.ToString("o")
Save-State $state
exit 5
