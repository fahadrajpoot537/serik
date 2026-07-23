@echo off
set PHP=C:\xampp\php\php.exe
set APP=C:\xampp\htdocs\SERIK-01-06-2026
set LOG=%APP%\storage\logs\schedule-run.log
set MARK=%APP%\storage\logs\schedule-heartbeat.txt
echo %DATE% %TIME% > "%MARK%"
cd /d "%APP%"
echo.>> "%LOG%"
echo ===== %DATE% %TIME% START =====>> "%LOG%"
"%PHP%" -d max_execution_time=120 -d memory_limit=256M "%APP%\artisan" schedule:run >> "%LOG%" 2>&1
echo ===== %DATE% %TIME% END rc=%ERRORLEVEL% =====>> "%LOG%"
exit /b 0
