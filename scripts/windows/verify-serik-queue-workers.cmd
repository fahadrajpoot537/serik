@echo off
setlocal EnableExtensions

rem Post-deploy verification for Serik queue NSSM workers.
rem Run from project root or anywhere.

set "SCRIPT_DIR=%~dp0"
for %%I in ("%SCRIPT_DIR%..\..") do set "APP_ROOT=%%~fI"

if defined SERIK_APP_ROOT set "APP_ROOT=%SERIK_APP_ROOT%"

if defined SERIK_PHP_EXE (
    set "PHP=%SERIK_PHP_EXE%"
) else (
    set "PHP=php"
)

echo === Windows services ===
for %%S in (SerikQueueHigh SerikQueueImages SerikQueueLow SerikMeilisearch) do (
    sc query %%S 2>nul | findstr /I "SERVICE_NAME STATE" 
    if errorlevel 1 echo %%S: NOT INSTALLED
    echo.
)

echo === Laravel queue status ===
cd /d "%APP_ROOT%"
"%PHP%" artisan serik:queue:status
set "STATUS_EXIT=%ERRORLEVEL%"

echo.
echo === Expected (when images backlog exists) ===
echo   SerikQueueImages: RUNNING
echo   Image workers: 1
echo   images queue: Reserved ^> 0, Pending decreasing
echo.

exit /b %STATUS_EXIT%
