# Serik Queue Workers (Windows / NSSM)

Four Laravel database queues on the **same** `jobs` table, each with a **dedicated** Windows service. Never point one worker at multiple heavy lanes.

| Queue | Service | Jobs |
|-------|---------|------|
| `high` | `SerikQueueHigh` | `SyncLiveJob`, `GeocodePropertyJob`, `SyncPropertyHistoryJob`, auth emails |
| `default` | *(optional)* | Botble / misc default-queue jobs |
| `images` | `SerikQueueImages` | `PersistTrebImagesJob` (TREB WebP + gallery) |
| `low` | `SerikQueueLow` | `GeocodeBacklogPropertyJob`, `RunArtisanOnLowQueueJob`, `DispatchTrebImagesWebpJob` |

The scheduler (`schedule:run` every minute) **only dispatches** work. It must **not** run `queue:work` or long `Artisan::call()` for images.

---

## 1. Migrate + `.env`

```bat
cd C:\project\serik
php artisan migrate --force
php artisan config:clear
```

```env
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=600

SERIK_QUEUE_HIGH=high
SERIK_QUEUE_DEFAULT=default
SERIK_QUEUE_IMAGES=images
SERIK_QUEUE_LOW=low

# Image lane throttling (t3.medium defaults)
SERIK_IMAGES_MAX_CONCURRENT=2
SERIK_IMAGES_MAX_PENDING=120
SERIK_IMAGES_GALLERY_DELAY_MS=100
SERIK_IMAGES_SLOT_WAIT=15
```

---

## 2. NSSM services (Administrator)

### Automated install (required on every fresh Windows server)

Run **as Administrator** from the project root after `git pull` and `php artisan migrate`:

```bat
rem Install or update ONLY the images worker (idempotent):
scripts\windows\install-serik-queue-images.cmd

rem Or install/update ALL queue workers (high + images + low):
scripts\windows\deploy-all-queue-workers.cmd

rem Post-deploy verification:
scripts\windows\verify-serik-queue-workers.cmd
```

Equivalent Artisan entry point (launches the `.cmd` with UAC):

```bat
php artisan serik:queue:install-images-worker
```

**Expected after images worker starts:**

```bat
sc query SerikQueueImages
php artisan serik:queue:status
```

- `SerikQueueImages` state: **RUNNING**
- `Image workers: 1`
- `images` row: **Reserved > 0** while backlog drains; **Pending** decreases over time

Scripts live in `scripts/windows/` — see `scripts/windows/README.md` for env overrides (`SERIK_APP_ROOT`, `SERIK_PHP_EXE`, `SERIK_NSSM`).

### Manual NSSM (reference only — prefer scripts above)

Replace `C:\project\serik` and PHP path with production paths.

### HIGH — live sync / geocode

```bat
nssm install SerikQueueHigh "C:\xampp\php\php.exe"
nssm set SerikQueueHigh AppDirectory "C:\project\serik"
nssm set SerikQueueHigh AppParameters "artisan queue:work database --queue=high --sleep=1 --tries=5 --timeout=200 --memory=384 --max-jobs=200 --max-time=3600"
nssm set SerikQueueHigh AppStdout "C:\project\serik\storage\logs\queue-high.log"
nssm set SerikQueueHigh AppStderr "C:\project\serik\storage\logs\queue-high-error.log"
nssm set SerikQueueHigh AppRotateFiles 1
nssm set SerikQueueHigh Start SERVICE_AUTO_START
nssm start SerikQueueHigh
```

### IMAGES — TREB WebP (CPU-heavy, rate-limited in job middleware)

**Run exactly one images worker on t3.medium** unless CPU stays under 60%:

```bat
nssm install SerikQueueImages "C:\xampp\php\php.exe"
nssm set SerikQueueImages AppDirectory "C:\project\serik"
nssm set SerikQueueImages AppParameters "artisan queue:work database --queue=images --sleep=3 --tries=3 --timeout=300 --memory=384 --max-jobs=50 --max-time=1800"
nssm set SerikQueueImages AppStdout "C:\project\serik\storage\logs\queue-images.log"
nssm set SerikQueueImages AppStderr "C:\project\serik\storage\logs\queue-images-error.log"
nssm set SerikQueueImages AppRotateFiles 1
nssm set SerikQueueImages Start SERVICE_AUTO_START
nssm start SerikQueueImages
```

### LOW — backlog geocode + maintenance dispatchers

```bat
nssm install SerikQueueLow "C:\xampp\php\php.exe"
nssm set SerikQueueLow AppDirectory "C:\project\serik"
nssm set SerikQueueLow AppParameters "artisan queue:work database --queue=low --sleep=2 --tries=4 --timeout=120 --memory=256 --max-jobs=100 --max-time=3600"
nssm set SerikQueueLow AppStdout "C:\project\serik\storage\logs\queue-low.log"
nssm set SerikQueueLow AppStderr "C:\project\serik\storage\logs\queue-low-error.log"
nssm set SerikQueueLow AppRotateFiles 1
nssm set SerikQueueLow Start SERVICE_AUTO_START
nssm start SerikQueueLow
```

### Graceful restart after deploy

```bat
php artisan queue:restart
```

Workers exit after the current job and NSSM restarts them.

---

## 3. Monitoring

```bat
php artisan serik:queue:status
php artisan serik:queue:status --json
php artisan queue:failed
php artisan queue:monitor high,images,low
scripts\windows\verify-serik-queue-workers.cmd
```

`serik:queue:status` reports Windows NSSM service states and **Image workers** (1 when `SerikQueueImages` is RUNNING). Exit code is non-zero if images are pending but the images worker is not running.

---

## 4. Cron (unchanged)

Task Scheduler every 1 minute:

```bat
php -d max_execution_time=120 -d memory_limit=256M artisan schedule:run
```

Image backfill is dispatched every 30 minutes as `DispatchTrebImagesWebpJob` (LOW), which queues `PersistTrebImagesJob` rows on **images**. This is **recovery only** — live imports dispatch images immediately via `PersistTrebImagesJob::dispatchForImport()` after each DB commit.

Manual backfill dispatch (does not block cron):

```bat
php artisan serik:treb-images-webp --dispatch --gallery --chunk=50
```

---

## 5. Expected CPU impact

| Before | After |
|--------|-------|
| Hundreds of `PersistTrebImagesJob` on `low` worker | Images isolated; max 2 concurrent AMP fetches |
| `serik:treb-images-webp` runs 10 min inside LOW job | Dispatcher queues 50 jobs / 30 min |
| No jobs table composite index | Faster `queue:work` pop queries |
| Duplicate gallery jobs per property | `ShouldBeUniqueUntilProcessing` + `dispatchUnique()` |

Target: **&lt;60% CPU** on t3.medium with one images worker and `SERIK_IMAGES_MAX_CONCURRENT=2`.
