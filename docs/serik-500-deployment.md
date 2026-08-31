# Serik HTTP 500 hardening — manual production deployment

Do **not** run these steps from a developer machine against production unless you are on the IIS host. This document is for the operator who already copies files to `C:\project\serik`.

These local changes do **not** by themselves prove that `serik.ca` 500s are gone. Recycle + logs after deploy are required.

## 1. Files to upload/change

Copy from this repository onto production, preserving paths:

**Required**

- `config/logging.php` (must include `search_sync` and the other custom channels)
- `config/serik.php`
- `config/scout.php`
- `config/database.php` (Redis timeouts — only if production file is older)
- `.env.example` (reference only; do not overwrite production `.env`)
- `bootstrap/app.php`
- `app/Support/SerikAuditLog.php`
- `app/Support/SerikSafeLog.php` *(new)*
- `app/Support/SerikCache.php`
- `app/Support/SerikQueueLock.php`
- `app/Support/PropertySearchSync.php`
- `app/Support/HomepageResponseCache.php`
- `app/Support/HomepageFragmentCache.php`
- `app/Support/MlsStatus.php`
- `app/Jobs/SearchBatchJob.php`
- `app/Http/Controllers/HealthController.php` *(new)*
- `app/Http/Middleware/RequestCorrelationMiddleware.php` *(new)*
- `app/Http/Middleware/GeoBlockMiddleware.php`
- `app/Http/Middleware/WagesMaintenanceMiddleware.php`
- `app/Providers/AppServiceProvider.php`
- `platform/plugins/real-estate/src/Services/PropertySearchService.php`
- `platform/themes/homzen/functions/shortcodes-real-estate.php`

**Optional ops (do not execute until you choose to)**

- `ops/windows/Collect-SerikDiagnostics.ps1`
- `ops/windows/Monitor-SerikHealth.ps1`
- `docs/serik-500-deployment.md`
- `docs/serik-500-rollback.md`

Do **not** upload `.env`, `storage/`, `vendor/` from local, or test-only files as a substitute for production config.

## 2. Pre-deployment backup

On the production host (PowerShell, adjust paths if needed):

```powershell
$root = "C:\project\serik"
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$bak = "C:\backup\serik-500-$stamp"
New-Item -ItemType Directory -Force -Path $bak | Out-Null
Copy-Item "$root\.env" "$bak\.env" -Force
# Keep a file list, not secrets:
Copy-Item "$root\config\logging.php" "$bak\logging.php" -Force
Copy-Item "$root\config\serik.php" "$bak\serik.php" -Force
Copy-Item "$root\config\scout.php" "$bak\scout.php" -Force
Copy-Item "$root\bootstrap\app.php" "$bak\app.php" -Force
Compress-Archive -Path @(
  "$root\app",
  "$root\bootstrap",
  "$root\config"
) -DestinationPath "$bak\code-app-bootstrap-config.zip" -Force
```

## 3. git status / diff verification (local, before copy)

```powershell
cd C:\xampp\htdocs\SERIK-01-06-2026
git status --short
git diff -- config/logging.php config/serik.php config/scout.php bootstrap/app.php app/Support/SerikAuditLog.php app/Support/SerikCache.php app/Jobs/SearchBatchJob.php
```

Confirm unrelated local work is not accidentally copied.

## 4. Production environment values to verify (do not paste values)

In production `.env` (edit only if missing; do not lower security):

| Variable | Expected |
| --- | --- |
| `APP_DEBUG` | `false` |
| `APP_ENV` | `production` |
| `LOG_CHANNEL` | `stack` |
| `LOG_STACK` | `single` (or `daily`) |
| `LOG_LEVEL` | `info` |
| `CACHE_STORE` | `redis` (Memurai) unless you have already chosen otherwise |
| `SESSION_DRIVER` | **do not change in this deploy** unless you have a planned session migration |
| `REDIS_TIMEOUT` | `1.5` (or similar fail-fast) |
| `REDIS_READ_TIMEOUT` | `1.5` |
| `SCOUT_DRIVER` | `meilisearch` |
| `MEILISEARCH_HOST` | local Meilisearch URL |
| `MEILISEARCH_TIMEOUT` | `0.8` (optional new) |
| `MEILISEARCH_CONNECT_TIMEOUT` | `0.2` (optional new) |
| `SERIK_HEALTH_TOKEN` | new random token for detailed `/health/ready` |
| `SERIK_REQUEST_SLOW_MS` | `2000` (optional) |

If `SESSION_DRIVER=redis` and Memurai is down, **every** request that starts a session can 500. This deploy does **not** auto-switch the session driver. Safest later migration: `SESSION_DRIVER=database` (or `file` on a single IIS node) after confirming the `sessions` table exists, then recycle the app pool once. Do that as a separate change.

## 5. Safe Laravel cache commands (on production, after files are in place)

Do **not** run `config:cache` until `php artisan config:show logging` shows `search_sync`.

```bat
cd /d C:\project\serik
php artisan about
php artisan config:show logging
php artisan config:show cache
php artisan config:show session
php artisan route:list --path=health
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

Do **not** run `migrate`, Meilisearch reindex, `composer update`, or queue `flush`.

## 6. Queue workers

Graceful restart is recommended (new job class + logging helpers):

- Touch `storage\framework\queue-restart.flag` **or** restart NSSM workers one at a time (`SerikQueueLow` first — it runs `SearchBatchJob`).
- Do **not** delete pending/failed jobs.
- Prefer worker recycle flags already documented in `docs/SERIK_QUEUE_WORKERS.md`, e.g. `--max-jobs=500 --max-time=3600`.

## 7. IIS app pool

One recycle of `DefaultAppPool` after PHP files + `optimize:clear` / `config:cache`:

```powershell
Restart-WebAppPool -Name "DefaultAppPool"
```

Do not restart MySQL, Memurai, or Meilisearch as part of this deploy.

## 8. Smoke-test URLs

```text
https://serik.ca/health/live
https://serik.ca/health/ready
https://serik.ca/up
https://serik.ca/
```

Expect `/health/live` HTTP 200 `{"status":"ok"}` and `X-Request-ID`.  
`/health/ready` without token: `{ "status": "ok"|"degraded"|"down" }` only.  
With header `X-Serik-Health-Token: <value>`: includes `checks` (no SQL, paths, or secrets).

## 9. Service verification

```powershell
Get-Service Memurai, MySQL80, SerikMeilisearch, SerikQueueHigh, SerikQueueLow, SerikQueueImages, SerikQueueImports, SerikQueueGhl | Format-Table Name, Status
Get-WebAppPoolState -Name DefaultAppPool
```

## 10. Log verification

```powershell
Get-ChildItem C:\project\serik\storage\logs | Sort-Object LastWriteTime -Descending | Select-Object -First 15 Name, LastWriteTime
Select-String -Path C:\project\serik\storage\logs\*.log -Pattern "search_sync is not defined|Unable to create configured logger" | Select-Object -Last 20
```

After deploy, that logger error must not recur. Correlate IIS 500 timestamps with Laravel `request_id` / `X-Request-ID`.

## Scheduled Task for the monitor (manual; do not auto-install)

```powershell
$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-NoProfile -ExecutionPolicy Bypass -File C:\project\serik\ops\windows\Monitor-SerikHealth.ps1"
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration ([TimeSpan]::MaxValue)
Register-ScheduledTask -TaskName "SerikHealthMonitor" -Action $action -Trigger $trigger -User "SYSTEM" -RunLevel Highest
```

Only install this after `/health/live` returns 200 in production.

## Rollback

See `docs/serik-500-rollback.md`.
