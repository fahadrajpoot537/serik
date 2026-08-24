#Requires -RunAsAdministrator
<#
.SYNOPSIS
    Harden Windows Redis/Memurai so it stays running: auto-start, crash recovery,
    and production-safe redis.conf settings for Serik (cache/session/locks).
#>
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Find-SerikRedisServiceName {
    if ($env:SERIK_REDIS_SERVICE -and -not [string]::IsNullOrWhiteSpace($env:SERIK_REDIS_SERVICE)) {
        $forced = $env:SERIK_REDIS_SERVICE.Trim()
        $svc = Get-Service -Name $forced -ErrorAction SilentlyContinue
        if (-not $svc) {
            throw "SERIK_REDIS_SERVICE='$forced' is not an installed Windows service."
        }
        return $svc.Name
    }

    foreach ($candidate in @('Memurai', 'memurai', 'Redis', 'redis')) {
        $svc = Get-Service -Name $candidate -ErrorAction SilentlyContinue
        if ($svc) {
            return $svc.Name
        }
    }

    throw 'No Memurai or Redis Windows service found.'
}

function Get-SerikRedisConfPath {
    param([string]$ServiceName)

    $cim = Get-CimInstance Win32_Service -Filter "Name='$ServiceName'"
    if (-not $cim -or [string]::IsNullOrWhiteSpace($cim.PathName)) {
        throw "Cannot read PathName for service $ServiceName"
    }

    if ($cim.PathName -match '"([^"]+\.conf)"') {
        return $Matches[1]
    }

    $candidates = @(
        'C:\Program Files\Memurai\memurai.conf',
        'C:\Program Files\Redis\redis.windows-service.conf',
        'C:\Program Files\Redis\redis.windows.conf'
    )
    foreach ($c in $candidates) {
        if (Test-Path -LiteralPath $c) {
            return $c
        }
    }

    throw "Could not locate Redis/Memurai conf from PathName: $($cim.PathName)"
}

function Update-SerikRedisConfContent {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Content,
        [Parameter(Mandatory = $true)]
        [string]$MaxMemory
    )

    # Normalize line endings for editing
    $text = $Content -replace "`r`n", "`n"

    function Set-Directive {
        param([string]$Body, [string]$Key, [string]$Value)
        $pattern = "(?m)^\s*#?\s*$([regex]::Escape($Key))(\s+.*)?$"
        $replacement = "$Key $Value"
        if ($Body -match $pattern) {
            return [regex]::Replace($Body, $pattern, $replacement, 1)
        }
        return $Body.TrimEnd() + "`n$replacement`n"
    }

    # Note: MSOpenTech Redis 3.0.x does not support protected-mode (Redis 3.2+).
    $text = Set-Directive -Body $text -Key 'bind' -Value '127.0.0.1'
    $text = Set-Directive -Body $text -Key 'tcp-keepalive' -Value '60'
    $text = Set-Directive -Body $text -Key 'timeout' -Value '0'
    $text = Set-Directive -Body $text -Key 'maxmemory' -Value $MaxMemory
    $text = Set-Directive -Body $text -Key 'maxmemory-policy' -Value 'volatile-lru'
    $text = Set-Directive -Body $text -Key 'stop-writes-on-bgsave-error' -Value 'no'

    # Collapse all active save directives to a single light RDB schedule
    $text = [regex]::Replace($text, '(?m)^\s*save\s+.*$', '# serik-hardened: save directive collapsed below')
    if ($text -notmatch '(?m)^save 900 1\s*$') {
        $text = $text.TrimEnd() + "`nsave 900 1`n"
    }

    $marker = '# serik-hardened: cache/session/locks profile (see docs/SERIK_REDIS_MEMURAI.md)'
    if ($text -notmatch [regex]::Escape($marker)) {
        $text = $marker + "`n" + $text
    }

    return ($text -replace "`n", "`r`n")
}

function Test-SerikRedisTcp {
    param([string]$HostName = '127.0.0.1', [int]$Port = 6379)
    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $iar = $client.BeginConnect($HostName, $Port, $null, $null)
        $ok = $iar.AsyncWaitHandle.WaitOne(2000, $false)
        if (-not $ok) {
            $client.Close()
            return $false
        }
        $client.EndConnect($iar)
        $client.Close()
        return $true
    } catch {
        return $false
    }
}

$serviceName = Find-SerikRedisServiceName
$svc = Get-Service -Name $serviceName
$confPath = Get-SerikRedisConfPath -ServiceName $serviceName
$maxMemory = if ($env:SERIK_REDIS_MAXMEMORY) { $env:SERIK_REDIS_MAXMEMORY.Trim() } else { '256mb' }

Write-Host '=== Serik Redis / Memurai hardening ===' -ForegroundColor Cyan
Write-Host "Service : $($svc.Name) ($($svc.Status) / $($svc.StartType))"
Write-Host "Conf    : $confPath"
Write-Host "Maxmem  : $maxMemory"

if ($svc.StartType -ne 'Automatic') {
    Write-Host 'Setting StartType=Automatic...'
    Set-Service -Name $serviceName -StartupType Automatic
}

Write-Host 'Configuring failure recovery (restart 5s / 15s / 30s, reset 1 day)...'
& sc.exe failure $serviceName reset= 86400 actions= restart/5000/restart/15000/restart/30000 | Out-Null
if ($LASTEXITCODE -ne 0) {
    throw "sc.exe failure failed with exit code $LASTEXITCODE"
}
& sc.exe failureflag $serviceName 1 | Out-Null

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = "$confPath.serik-backup-$stamp"
Copy-Item -LiteralPath $confPath -Destination $backup -Force
Write-Host "Backup  : $backup"

Write-Host 'Hardening conf...'
$original = [System.IO.File]::ReadAllText($confPath)
$updated = Update-SerikRedisConfContent -Content $original -MaxMemory $maxMemory
$utf8NoBom = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllText($confPath, $updated, $utf8NoBom)
Write-Host 'Conf hardened.'

Write-Host "Restarting $serviceName..."
Restart-Service -Name $serviceName -Force
Start-Sleep -Seconds 3
$svc.Refresh()

$tcpOk = $false
for ($attempt = 1; $attempt -le 5; $attempt++) {
    $tcpOk = Test-SerikRedisTcp
    if ($tcpOk) { break }
    Start-Sleep -Seconds 1
}

Write-Host ''
Write-Host 'Result:' -ForegroundColor Green
Write-Host "  Service status : $($svc.Status)"
Write-Host "  Start type     : $((Get-Service -Name $serviceName).StartType)"
Write-Host "  TCP 6379 open  : $tcpOk"
& sc.exe qfailure $serviceName

if ($svc.Status -ne 'Running' -or -not $tcpOk) {
    Write-Host 'WARNING: Redis/Memurai is not healthy after restart. Restoring backup...' -ForegroundColor Yellow
    Copy-Item -LiteralPath $backup -Destination $confPath -Force
    Restart-Service -Name $serviceName -Force
    exit 1
}

Write-Host 'OK — Redis/Memurai hardened for continuous availability + auto-recovery.' -ForegroundColor Green
exit 0
