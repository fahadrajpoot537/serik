@echo off
REM Manual full sync - run when you need fresh Ontario sold map data immediately
cd /d "c:\xampp\htdocs\SERIK-01-06-2026"
echo === SERIK Quick Sync (sold + geocode) ===
"C:\xampp\php\php.exe" artisan serik:sync-now
echo.
echo === Done. Clear cache: http://127.0.0.1:8000/clear-serik-cache.php?key=serik2026clear ===
pause
