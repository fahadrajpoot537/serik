@echo off
REM ============================================================
REM SERIK live health: cron/queues + storage images (IIS)
REM Run as Administrator on C:\project\serik
REM ============================================================
cd /d C:\project\serik

echo.
echo ===== 1) APP / QUEUE =====
php artisan tinker --execute="file_put_contents(storage_path('logs/health-snap.txt'), 'APP_URL='.config('app.url').PHP_EOL.'tz='.config('app.timezone').PHP_EOL.'now='.now().PHP_EOL.'high='.DB::table('jobs')->where('queue','high')->count().PHP_EOL.'low='.DB::table('jobs')->where('queue','low')->count().PHP_EOL.'failed='.DB::table('failed_jobs')->count().PHP_EOL.'updated_3h='.\Botble\RealEstate\Models\Property::where('updated_at','>=',now()->subHours(3))->count().PHP_EOL.'created_3h='.\Botble\RealEstate\Models\Property::where('created_at','>=',now()->subHours(3))->where('created_at','<','2100-01-01')->count().PHP_EOL);"
type storage\logs\health-snap.txt

echo.
echo ===== 2) NSSM WORKERS =====
nssm status SerikQueueHigh
nssm status SerikQueueLow

echo.
echo ===== 3) SCHEDULE =====
php artisan schedule:list

echo.
echo ===== 4) FORCE LIVE SYNC =====
php artisan tinker --execute="Cache::forget('amp_recent_lock'); try{Cache::lock('serik_sync_live_lock')->forceRelease();}catch(Throwable $e){}"
php artisan serik:sync-live:dispatch --force --days=1 --pages=4 --max-new=80
php artisan schedule:run -v

echo.
echo ===== 5) STORAGE LINK (IIS-safe junction) =====
if exist public\storage (
  echo public\storage exists
  dir public\storage | findstr /i "storage"
) else (
  echo public\storage MISSING - creating junction...
  mklink /J public\storage storage\app\public
)

if not exist storage\app\public (
  echo ERROR: storage\app\public missing
) else (
  echo storage\app\public OK
)

echo.
echo ===== 6) SAMPLE FILE CHECK =====
dir /b storage\app\public 2>nul | more
echo.
echo Open in browser: https://serik.ca/storage/avatars/1.jpg
echo If that 200 but property photos 404, the FILE path in DB is wrong or file not under storage\app\public
echo.

echo ===== 7) REINDEX RECENT =====
php artisan serik:search-index-recent --days=1 --limit=3000

echo.
echo Done. Check storage\logs\queue-high.log and treb-sync-live.log LastWriteTime.
pause
