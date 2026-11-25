@echo off
REM RoadRunner PHP worker wrapper for Windows XAMPP / production PHP.
REM Disables CLI opcache to avoid known ZTS access-violation crashes.
set LARAVEL_OCTANE=1
"C:\xampp\php\php.exe" -d opcache.enable_cli=0 "%~dp0..\..\vendor\bin\roadrunner-worker" %*
