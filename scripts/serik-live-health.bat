@echo off
REM ============================================================
REM SERIK live health: cron/queues + storage images (IIS)
REM Run as Administrator on C:\project\serik
REM ============================================================
cd /d C:\project\serik

echo.
echo ===== 0) GIT =====
git fetch origin
git status -sb
git log -1 --oneline
echo Expected: recent commit from origin/main
echo If behind: git stash / checkout ForgotPasswordController then git pull origin main

echo.
echo ===== 1) APP / QUEUE =====
php artisan tinker --execute="file_put_contents(storage_path('logs/health-snap.txt'), 'APP_URL='.config('app.url').PHP_EOL.'tz='.config('app.timezone').PHP_EOL.'now='.now().PHP_EOL.'high='.DB::table('jobs')->where('queue','high')->count().PHP_EOL.'low='.DB::table('jobs')->where('queue','low')->count().PHP_EOL.'failed='.DB::table('failed_jobs')->count().PHP_EOL.'updated_3h='.\Botble\RealEstate\Models\Property::where('updated_at','>=',now()->subHours(3))->count().PHP_EOL.'created_3h='.\Botble\RealEstate\Models\Property::where('created_at','>=',now()->subHours(3))->where('created_at','<','2100-01-01')->count().PHP_EOL);"
type storage\logs\health-snap.txt

echo.
echo ===== 2) NSSM WORKERS (must be SERVICE_RUNNING) =====
nssm status SerikQueueHigh
nssm status SerikQueueLow
nssm restart SerikQueueHigh
nssm restart SerikQueueLow
timeout /t 3 /nobreak >nul
nssm status SerikQueueHigh
nssm status SerikQueueLow

echo.
echo ===== 3) SCHEDULE =====
php artisan schedule:list
echo.
echo Task Scheduler must run every 1 minute:
echo   php artisan schedule:run
schtasks /Query /FO LIST /V 2>nul | findstr /i "Serik schedule artisan"
php artisan schedule:run -v

echo.
echo ===== 4) FORCE LIVE SYNC =====
php artisan tinker --execute="Cache::forget('amp_recent_lock'); try{Cache::lock('serik_sync_live_lock')->forceRelease();}catch(Throwable $e){}"
php artisan serik:sync-live --force --days=1 --pages=6 --max-new=100 --max-seconds=90
php artisan serik:sync-live:dispatch --force --days=1 --pages=4 --max-new=80

echo.
echo ===== 5) STORAGE LINK (IIS-safe junction) =====
if exist public\storage (
  echo public\storage exists
  dir public\storage | findstr /i "JUNCTION SYMLINK DIR"
  if exist public\storage\avatars\1.jpg (
    echo OK: public\storage\avatars\1.jpg found
  ) else (
    echo WARN: avatars\1.jpg not visible via public\storage
  )
) else (
  echo public\storage MISSING - creating junction...
  mklink /J public\storage storage\app\public
)

if not exist storage\app\public (
  echo ERROR: storage\app\public missing
) else (
  echo storage\app\public OK
  if exist storage\app\public\avatars\1.jpg (
    echo OK: storage\app\public\avatars\1.jpg found
  )
)

echo.
echo If link is a real empty folder (not JUNCTION), fix as Admin:
echo   rmdir public\storage
echo   mklink /J public\storage storage\app\public
echo.

echo ===== 6) IMAGE URL SAMPLES =====
php artisan tinker --execute="$rows=DB::table('re_properties')->select('id','external_id','image_val')->whereNotNull('image_val')->where('image_val','!=','')->orderByDesc('updated_at')->limit(8)->get(); $out=''; foreach($rows as $r){ $u=\App\Support\SerikMediaUrl::toPublic($r->image_val); $out.='id='.$r->id.' raw='.$r->image_val.PHP_EOL.' url='.$u.PHP_EOL; } file_put_contents(storage_path('logs/image-samples.txt'), $out);"
type storage\logs\image-samples.txt
echo.
echo Browser checks:
echo   https://serik.ca/storage/avatars/1.jpg
echo   https://serik.ca/clear-serik-cache.php?key=serik2026clear^&diag_images=1
echo   https://serik.ca/clear-serik-cache.php?key=serik2026clear^&diag_cron=1
echo.

echo ===== 7) REINDEX RECENT =====
php artisan serik:search-index-recent --days=1 --limit=3000
php artisan view:clear
php artisan config:clear

echo.
echo Done. Check storage\logs\queue-high.log and treb-sync-live.log LastWriteTime.
pause
