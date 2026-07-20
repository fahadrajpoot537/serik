@echo off
REM Run on LIVE: C:\project\serik
cd /d C:\project\serik

echo === Clearing bootstrap + framework caches (fixes stale translator deferred map) ===
del /F /Q bootstrap\cache\config.php 2>nul
del /F /Q bootstrap\cache\services.php 2>nul
del /F /Q bootstrap\cache\packages.php 2>nul
del /F /Q bootstrap\cache\routes-v7.php 2>nul
del /F /Q bootstrap\cache\events.php 2>nul
del /F /Q storage\framework\views\*.php 2>nul

php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo === Verify translator binding ===
php -r "require 'vendor/autoload.php'; $a=require 'bootstrap/app.php'; $a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); App\Support\EnsuresTranslator::ensure(); echo app()->bound('translator') ? 'translator OK' : 'translator MISSING'; echo PHP_EOL;"

echo === Done. Recycle IIS App Pool next. ===
pause
