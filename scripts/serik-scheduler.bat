@echo off
REM SERIK local scheduler - run this every 1 minute via Windows Task Scheduler
cd /d "c:\xampp\htdocs\SERIK-01-06-2026"
"C:\xampp\php\php.exe" artisan schedule:run >> storage\logs\scheduler.log 2>&1
