# Dual-Priority Queue Workers (Windows / NSSM)

> **Superseded for production:** use `docs/SERIK_QUEUE_WORKERS.md` — it documents **high**, **images**, and **low** lanes plus automated install scripts in `scripts/windows/`.

SERIK uses two Laravel database queues on the **same** `jobs` table, processed by **separate** Windows services. Never point one worker at both queues.

| Queue | Env | Jobs |
|-------|-----|------|
| `high` | `SERIK_QUEUE_HIGH` | `SyncLiveJob`, `GeocodePropertyJob`, `SyncPropertyHistoryJob` |
| `low` | `SERIK_QUEUE_LOW` | `GeocodeBacklogPropertyJob`, maintenance (`RunArtisanOnLowQueueJob`) |

The Laravel scheduler (`schedule:run` every minute) **only dispatches** work and must finish in ~1–2 seconds. It must **not** run `queue:work` or `serik:geocode-all`.

---

## 1. Migrate + config

```bat
cd C:\project\serik
php artisan migrate --force
php artisan config:clear
```

Optional `.env`:

```env
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=300
SERIK_QUEUE_HIGH=high
SERIK_QUEUE_LOW=low
SERIK_BACKLOG_DISPATCH=40
SERIK_BACKLOG_PAUSE_HIGH_DEPTH=5
SERIK_BACKLOG_ACTIVE_ONLY=true
SERIK_BACKLOG_DAYS=90
```

---

## 2. Install NSSM workers (recommended)

Download [NSSM](https://nssm.cc/download) and run an **Administrator** command prompt.

### HIGH worker (live path — keep responsive)

```bat
nssm install SerikQueueHigh "C:\xampp\php\php.exe"
nssm set SerikQueueHigh AppDirectory "C:\project\serik"
nssm set SerikQueueHigh AppParameters "artisan queue:work database --queue=high --sleep=1 --tries=5 --timeout=200 --memory=512 --max-time=3600"
nssm set SerikQueueHigh AppStdout "C:\project\serik\storage\logs\queue-high.log"
nssm set SerikQueueHigh AppStderr "C:\project\serik\storage\logs\queue-high-error.log"
nssm set SerikQueueHigh AppRotateFiles 1
nssm set SerikQueueHigh Start SERVICE_AUTO_START
nssm start SerikQueueHigh
```

### LOW worker (backlog geocode — throttle-friendly)

```bat
nssm install SerikQueueLow "C:\xampp\php\php.exe"
nssm set SerikQueueLow AppDirectory "C:\project\serik"
nssm set SerikQueueLow AppParameters "artisan queue:work database --queue=low --sleep=2 --tries=4 --timeout=120 --memory=512 --max-time=3600"
nssm set SerikQueueLow AppStdout "C:\project\serik\storage\logs\queue-low.log"
nssm set SerikQueueLow AppStderr "C:\project\serik\storage\logs\queue-low-error.log"
nssm set SerikQueueLow AppRotateFiles 1
nssm set SerikQueueLow Start SERVICE_AUTO_START
nssm start SerikQueueLow
```

`--max-time=3600` recycles the worker hourly (memory hygiene). NSSM restarts it automatically.

### Optional: second LOW worker (faster backlog drain)

Only if CPU/MySQL stay healthy:

```bat
nssm install SerikQueueLow2 "C:\xampp\php\php.exe"
nssm set SerikQueueLow2 AppDirectory "C:\project\serik"
nssm set SerikQueueLow2 AppParameters "artisan queue:work database --queue=low --sleep=3 --tries=4 --timeout=120 --memory=512 --max-time=3600"
nssm start SerikQueueLow2
```

Do **not** add a worker that listens to `high,low` in one process.

---

## 3. Task Scheduler (unchanged cadence)

Keep the existing every-1-minute task:

```bat
php -d max_execution_time=0 artisan schedule:run
```

`schedule:list` should show:

- `serik:sync-live:dispatch` every minute  
- `serik:backlog:dispatch` every minute  
- `serik:geocode:reset-stuck` every 5 minutes  
- `serik:geocode:retry-failed` hourly  
- **No** `serik:geocode-all`  
- **No** `queue:work`

---

## 4. Verify

```bat
php artisan schedule:list
php artisan serik:sync-live:dispatch
php artisan serik:backlog:dispatch --limit=10
php artisan queue:monitor high,low

rem Watch depths
php artisan tinker --execute="echo 'high='.DB::table('jobs')->where('queue','high')->count().' low='.DB::table('jobs')->where('queue','low')->count();"
```

Expected live path: new listings imported on HIGH → geocoded → history chained after geocode.  
Expected backlog: `geocoding_status=pending` rows drain on LOW without blocking HIGH.

---

## 5. Manual / emergency

```bat
rem Run live pipeline in-process (debug only — not for scheduler)
php artisan serik:sync-live --force

rem Old bulk geocoder still exists for one-off ops (do not schedule it)
php artisan serik:geocode-all --batch=60 --max-runtime=300 --active-only --days=90

rem Recover stuck / retry soft failures
php artisan serik:geocode:reset-stuck
php artisan serik:geocode:retry-failed
```

---

## 6. Stop / remove services

```bat
nssm stop SerikQueueHigh
nssm stop SerikQueueLow
nssm remove SerikQueueHigh confirm
nssm remove SerikQueueLow confirm
```
