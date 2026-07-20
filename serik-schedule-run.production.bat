@echo off
REM Live server runner — Task Scheduler every 1 minute
REM Forces CLI max_execution_time=0 so Botble's 300s never kills sync-live.
set PHP=php
set APP=C:\project\serik
set LOG=%APP%\storage\logs\schedule-run.log
set MARK=%APP%\storage\logs\schedule-heartbeat.txt

cd /d "%APP%"
echo %DATE% %TIME% > "%MARK%"
echo.>> "%LOG%"
echo ===== %DATE% %TIME% START =====>> "%LOG%"
"%PHP%" -d max_execution_time=0 -d memory_limit=1024M "%APP%\artisan" schedule:run >> "%LOG%" 2>&1
echo ===== %DATE% %TIME% END rc=%ERRORLEVEL% =====>> "%LOG%"
exit /b 0
