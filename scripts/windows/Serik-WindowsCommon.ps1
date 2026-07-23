# Shared helpers for Serik Windows NSSM deployment scripts.
# Dot-source from scripts in this directory: . (Join-Path $PSScriptRoot 'Serik-WindowsCommon.ps1')

function Get-SerikAppRoot {
    if ($env:SERIK_APP_ROOT) {
        return (Resolve-Path -LiteralPath $env:SERIK_APP_ROOT).Path
    }

    return (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..')).Path
}

function Get-SerikPhpExe {
    if ($env:SERIK_PHP_EXE -and (Test-Path -LiteralPath $env:SERIK_PHP_EXE)) {
        return (Resolve-Path -LiteralPath $env:SERIK_PHP_EXE).Path
    }

    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if (-not $cmd) {
        throw 'php.exe not found. Set SERIK_PHP_EXE or add PHP to PATH.'
    }

    return $cmd.Source
}

function Find-SerikNssm {
    $searchPaths = @(
        $env:SERIK_NSSM,
        'C:\nssm\nssm.exe',
        'C:\nssm\win64\nssm.exe',
        'C:\nssm\win32\nssm.exe',
        'C:\tools\nssm\nssm.exe',
        'C:\Program Files\nssm\nssm.exe',
        'C:\Program Files (x86)\nssm\nssm.exe'
    )

    foreach ($path in $searchPaths) {
        if ([string]::IsNullOrWhiteSpace($path)) {
            continue
        }

        if (Test-Path -LiteralPath $path) {
            return (Resolve-Path -LiteralPath $path).Path
        }
    }

    $cmd = Get-Command nssm -ErrorAction SilentlyContinue
    if ($cmd) {
        return $cmd.Source
    }

    throw 'nssm.exe not found. Download from https://nssm.cc/download and set SERIK_NSSM.'
}

function Set-SerikNssmService {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Nssm,

        [Parameter(Mandatory = $true)]
        [string]$ServiceName,

        [Parameter(Mandatory = $true)]
        [string]$Key,

        [Parameter(Mandatory = $true)]
        [string]$Value
    )

    & $Nssm set $ServiceName $Key $Value | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "nssm set $Key failed with exit code $LASTEXITCODE"
    }
}

function Set-SerikNssmServiceExit {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Nssm,

        [Parameter(Mandatory = $true)]
        [string]$ServiceName,

        [Parameter(Mandatory = $true)]
        [string]$Action,

        [Parameter(Mandatory = $true)]
        [string]$ExitCode,

        [Parameter(Mandatory = $true)]
        [string]$RestartAction
    )

    & $Nssm set $ServiceName $Action $ExitCode $RestartAction | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "nssm set $Action $ExitCode $RestartAction failed with exit code $LASTEXITCODE"
    }
}

function Invoke-SerikNssm {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Nssm,

        [Parameter(Mandatory = $true)]
        [string[]]$Arguments
    )

    & $Nssm @Arguments | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw "nssm $($Arguments -join ' ') failed with exit code $LASTEXITCODE"
    }
}
