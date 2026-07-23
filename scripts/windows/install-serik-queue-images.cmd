@echo off
setlocal EnableExtensions

rem Install or update SerikQueueImages NSSM service (requires Administrator).
rem Usage: right-click -> Run as administrator
rem    OR:  scripts\windows\install-serik-queue-images.cmd

set "SCRIPT_DIR=%~dp0"
set "PS1=%SCRIPT_DIR%Install-SerikQueueImages.ps1"

if not exist "%PS1%" (
    echo ERROR: Missing %PS1%
    exit /b 1
)

net session >nul 2>&1
if errorlevel 1 (
    echo Requesting Administrator elevation...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process powershell -ArgumentList '-NoProfile -ExecutionPolicy Bypass -File \"\"%PS1%\"\"' -Verb RunAs -Wait"
    exit /b %ERRORLEVEL%
)

powershell -NoProfile -ExecutionPolicy Bypass -File "%PS1%"
exit /b %ERRORLEVEL%
