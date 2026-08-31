# Collect Serik diagnostics (read-only)

# Usage (run as Administrator on the IIS server, after copying this file there):
#   powershell -NoProfile -ExecutionPolicy Bypass -File .\Collect-SerikDiagnostics.ps1
# Optional:
#   -LogRoot "C:\project\serik\storage\logs"
#   -SiteName "serik.ca"
#   -Hours 6
#
# Makes no server changes. Redacts secrets. Do not run against a remote production
# share from a developer workstation unless you intend to read that machine.

[CmdletBinding()]
param(
    [string]$LogRoot = "C:\project\serik\storage\logs",
    [string]$SiteName = "serik.ca",
    [int]$Hours = 6,
    [string]$OutDir = ""
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Continue"

if (-not $OutDir) {
    $stamp = Get-Date -Format "yyyyMMdd-HHmmss"
    $OutDir = Join-Path $env:TEMP "serik-diagnostics-$stamp"
}

New-Item -ItemType Directory -Force -Path $OutDir | Out-Null
$report = Join-Path $OutDir "diagnostics.txt"

function Write-Section([string]$Title) {
    $line = "`r`n==== $Title ====`r`n"
    Add-Content -Path $report -Value $line
    Write-Host $line
}

function Redact-Text([string]$Text) {
    if ([string]::IsNullOrEmpty($Text)) { return $Text }
    $Text = [regex]::Replace($Text, '(?i)(password|passwd|secret|token|api[_-]?key|authorization|cookie)\s*[:=]\s*\S+', '$1=[redacted]')
    $Text = [regex]::Replace($Text, '(?i)(Bearer)\s+[A-Za-z0-9._\-]+', '$1 [redacted]')
    $Text = [regex]::Replace($Text, '(?i)(APP_KEY|TRREB_AUTH|TRREB_AUTH1|TRREB_AUTH2|GOHIGHLEVEL_API_TOKEN|RESEND_API_KEY|MEILISEARCH_KEY|REDIS_PASSWORD|SERIK_HEALTH_TOKEN)\s*=\s*\S+', '$1=[redacted]')
    return $Text
}

function Add-Block([string]$Title, [string]$Body) {
    Write-Section $Title
    $safe = Redact-Text $Body
    Add-Content -Path $report -Value $safe
}

Add-Block "CollectedAt" ((Get-Date).ToString("o") + "`r`nHost=$env:COMPUTERNAME")

try {
    $os = Get-CimInstance Win32_OperatingSystem
    $cpu = Get-CimInstance Win32_Processor | Select-Object -First 1
    Add-Block "MemoryAndCpu" @"
TotalVisibleMemory(GB)=$([math]::Round($os.TotalVisibleMemorySize / 1MB, 2))
FreePhysicalMemory(GB)=$([math]::Round($os.FreePhysicalMemory / 1MB, 2))
FreeVirtualMemory(GB)=$([math]::Round($os.FreeVirtualMemory / 1MB, 2))
CPU=$($cpu.Name)
LoadPercentage=$($cpu.LoadPercentage)
"@
} catch {
    Add-Block "MemoryAndCpu" $_.Exception.Message
}

$services = @(
    "Memurai", "MySQL80", "SerikMeilisearch",
    "SerikQueueAux", "SerikQueueCacheRefresh", "SerikQueueGhl",
    "SerikQueueHigh", "SerikQueueImages", "SerikQueueImports", "SerikQueueLow",
    "W3SVC", "WAS"
)
$svcLines = foreach ($name in $services) {
    $svc = Get-Service -Name $name -ErrorAction SilentlyContinue
    if ($svc) { "{0,-28} {1,-12} {2}" -f $svc.Name, $svc.Status, $svc.StartType } else { "{0,-28} NOT_FOUND" -f $name }
}
Add-Block "ServiceStates" ($svcLines -join "`r`n")

try {
    Import-Module WebAdministration -ErrorAction Stop
    $pool = Get-WebAppPoolState -Name "DefaultAppPool" -ErrorAction SilentlyContinue
    Add-Block "AppPool" ("DefaultAppPool=" + $(if ($pool) { $pool.Value } else { "UNKNOWN" }))
} catch {
    Add-Block "AppPool" $_.Exception.Message
}

$since = (Get-Date).AddHours(-1 * [math]::Abs($Hours))
try {
    $iisLogDir = "C:\inetpub\logs\LogFiles"
    $recentIis = Get-ChildItem -Path $iisLogDir -Recurse -Filter "*.log" -ErrorAction SilentlyContinue |
        Where-Object { $_.LastWriteTime -ge $since } |
        Sort-Object LastWriteTime -Descending |
        Select-Object -First 8
    $hits = @()
    foreach ($file in $recentIis) {
        $lines = Select-String -Path $file.FullName -Pattern " (500|502|503) " -ErrorAction SilentlyContinue | Select-Object -Last 40
        foreach ($line in $lines) {
            $hits += ("{0}: {1}" -f $file.Name, $line.Line)
        }
    }
    if ($hits.Count -eq 0) { $hits = @("(no 500/502/503 lines in recent IIS logs)") }
    Add-Block "IIS 500/502/503 (last $Hours h)" (($hits | Select-Object -Last 80) -join "`r`n")
} catch {
    Add-Block "IIS logs" $_.Exception.Message
}

try {
    $app = Get-WinEvent -FilterHashtable @{ LogName = "Application"; StartTime = $since } -ErrorAction SilentlyContinue |
        Where-Object { $_.LevelDisplayName -in @("Error", "Warning", "Critical") } |
        Select-Object -First 40 TimeCreated, ProviderName, Id, LevelDisplayName, Message
    $sys = Get-WinEvent -FilterHashtable @{ LogName = "System"; StartTime = $since } -ErrorAction SilentlyContinue |
        Where-Object { $_.LevelDisplayName -in @("Error", "Warning", "Critical") } |
        Select-Object -First 40 TimeCreated, ProviderName, Id, LevelDisplayName, Message
    $fmt = {
        param($ev)
        $ev | ForEach-Object {
            $msg = Redact-Text (([string]$_.Message) -replace '\s+', ' ')
            if ($msg.Length -gt 400) { $msg = $msg.Substring(0, 400) }
            "{0:o} {1} id={2} {3} {4}" -f $_.TimeCreated, $_.ProviderName, $_.Id, $_.LevelDisplayName, $msg
        }
    }
    Add-Block "Application events" ((& $fmt $app) -join "`r`n")
    Add-Block "System events" ((& $fmt $sys) -join "`r`n")
} catch {
    Add-Block "Windows events" $_.Exception.Message
}

try {
    $phpErrors = @()
    $phpCandidates = @(
        "C:\Windows\Temp",
        "C:\inetpub\temp",
        "C:\PHP*\logs"
    )
    Get-ChildItem -Path $phpCandidates -Filter "*php*.log" -ErrorAction SilentlyContinue |
        Where-Object { $_.LastWriteTime -ge $since } |
        Sort-Object LastWriteTime -Descending |
        Select-Object -First 5 |
        ForEach-Object {
            $phpErrors += "FILE=$($_.FullName) LAST=$($_.LastWriteTime.ToString('o')) SIZE=$($_.Length)"
            $phpErrors += (Get-Content -Path $_.FullName -Tail 30 -ErrorAction SilentlyContinue)
        }
    if ($phpErrors.Count -eq 0) { $phpErrors = @("(no recent PHP/FastCGI log files found in common temp paths)") }
    Add-Block "PHP/FastCGI recent errors" ((Redact-Text ($phpErrors -join "`r`n")))
} catch {
    Add-Block "PHP/FastCGI" $_.Exception.Message
}

try {
    if (Test-Path $LogRoot) {
        $logs = Get-ChildItem -Path $LogRoot -File -ErrorAction SilentlyContinue |
            Sort-Object LastWriteTime -Descending |
            Select-Object -First 25 Name, Length, LastWriteTime
        $lines = $logs | ForEach-Object { "{0,-40} {1,10} {2:o}" -f $_.Name, $_.Length, $_.LastWriteTime }
        Add-Block "Laravel log files (newest first)" ($lines -join "`r`n")
    } else {
        Add-Block "Laravel log files" "Log root not found: $LogRoot"
    }
} catch {
    Add-Block "Laravel log files" $_.Exception.Message
}

Write-Host "Wrote $report"
Write-Host "Folder $OutDir"
exit 0
