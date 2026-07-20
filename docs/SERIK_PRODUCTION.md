# SERIK Enterprise Production Deployment Guide

Local DB is the source of truth. Users never wait on TREB during browsing.

---

## 1. Required Stack

| Component | Role | Notes |
|-----------|------|-------|
| PHP 8.2+ | App runtime | `max_execution_time=0` for CLI; 300 ok for web |
| MariaDB 10.4+ / MySQL 8 | Source of truth | InnoDB; unique index on `external_id` |
| Composer | Dependencies | Scout + Meilisearch PHP SDK required |
| Meilisearch 1.x | Search + map geo | Persist `data.ms` to disk; never ephemeral |
| Laravel Scheduler | Cron orchestration | `schedule:run` every minute |
| MapLibre | Map UI | Client clustering enabled |

Redis is intentionally optional/postponed. File cache + sync/database queues work.

---

## 2. First-time Install

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env   # or use existing .env
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

### Meilisearch (Windows)

```bat
storage\meilisearch\start-meilisearch.bat
```

Or register as a Windows service with NSSM:

```bat
nssm install SerikMeilisearch "C:\path\to\meilisearch.exe"
nssm set SerikMeilisearch AppParameters "--http-addr 127.0.0.1:7700 --master-key SerikMeiliMasterKey2026Prod --db-path C:\serik\meili-data"
nssm start SerikMeilisearch
```

### Meilisearch (Linux)

```bash
curl -L https://install.meilisearch.com | sh
./meilisearch --http-addr 127.0.0.1:7700 --master-key "$MEILISEARCH_KEY" --db-path /var/lib/meilisearch
# Prefer a systemd unit for production
```

`.env` keys:

```env
SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=false
SCOUT_PREFIX=serik_
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=SerikMeiliMasterKey2026Prod
```

(Index name used by Property model is `properties`.)

---

## 3. Initial Data Load

```bash
# Import every TREB listing the AMP feed can serve (newest → oldest).
# Safe to re-run. Checkpoints after every page. Never duplicates (unique external_id).
php artisan serik:backfill-all --from-year=1999 --to-year=2026 --skip-existing --chunk=200 --batch=5

# Resume after interrupt:
php artisan serik:backfill-all --resume --from-year=1999 --skip-existing --chunk=200

# After (or during) backfill, catch Meilisearch up (bulk import disables
# per-row Scout sync for throughput — reindex is required):
php artisan serik:search-index --resume --chunk=1000

# Force-update existing rows too (slower):
php artisan serik:backfill-all --from-year=2018 --force

# Geocode missing coordinates (Nominatim ~1 req/sec, fully resumable):
php artisan serik:geocode-all --batch=100
```

**AMP reality:** The TRREB AMP OData feed does **not** archive 1999–2017. Proven empty for those years. Earliest `OriginalEntryTimestamp` coverage starts ~2018. All available feed data is imported; historical rows already in MySQL from earlier captures are preserved.

---

## 4. Scheduler / Cron

Run Laravel's scheduler every minute.

### Linux

```cron
* * * * * cd /var/www/serik && php artisan schedule:run >> /dev/null 2>&1
```

### Windows Task Scheduler

Program: `C:\xampp\php\php.exe`  
Arguments: `artisan schedule:run`  
Start in: `C:\xampp\htdocs\SERIK-01-06-2026`  
Trigger: every 1 minute, indefinitely.

### Registered Serik Jobs (dual-priority queues)

| Schedule | Command | Purpose |
|----------|---------|---------|
| every minute | `serik:sync-live:dispatch` | Queue HIGH live import/geocode/history |
| every minute | `serik:backlog:dispatch` | Queue LOW backlog geocode jobs |
| every 5 min | `serik:geocode:reset-stuck` | Recover stuck processing/queued |
| hourly | `serik:geocode:retry-failed` | Requeue soft geocode failures |
| every 10 min | `serik:search-index-recent` | Meili warm for recent actives |

**Workers (NSSM):** see `docs/SERIK_DUAL_QUEUE_WORKERS.md` — separate `high` and `low` queue services. Do **not** schedule `serik:geocode-all` or `queue:work`.

After migrate: `php artisan serik:geocode:backfill-status` (chunked; marks rows with coords as `done`).

**First-time dual-queue cutover:** install NSSM `SerikQueueHigh` + `SerikQueueLow` before relying on the new schedule (scheduler no longer runs `queue:work`).

| every 4h | `serik:treb-cron --mode=standard` | Standard sync |

All use `withoutOverlapping()` so crashes never stampede AMP.

---

## 5. Production Artisan Commands

| Command | Purpose |
|---------|---------|
| `serik:backfill-all` | Full historical import 1999→today (resumable) |
| `serik:import-historical` | Multi-phase bootstrap (import+geocode+history) |
| `serik:sync-new` | Incremental NEW listings |
| `serik:sync-updates` | Incremental MODIFIED listings |
| `serik:sync-history` | Address / sale history |
| `serik:sync-sold` | Recent sold/leased |
| `serik:sync-all` | One-shot new+updates+history+geocode |
| `serik:geocode` / `serik:geocode-all` | Missing coordinates |
| `serik:search-index` | Meilisearch reindex |
| `serik:reconcile` | Daily accuracy repair |
| `serik:full-property-resync` | Field-level resync of existing rows |
| `serik:treb-cron` | Master profiled cron |

---

## 6. Cron Safety Guarantees

- `withoutOverlapping(N)` on every scheduled job
- Cache locks inside `backfill-all`, `reconcile`, sync commands
- Checkpoint resume after crash (`--resume`)
- Chunked AMP pages + exponential backoff on HTTP errors
- Unique `external_id` index → impossible to duplicate
- Skip-unchanged on updates (`buildAmpResyncChanges` empty → no write)

---

## 7. Map & Search

- Map defaults to Meilisearch geo (`engine=mysql` forces SQL fallback)
- Warm cached map responses ≈ **35–50ms**
- Popup details lazy-load via `/api/v1/map-property-bundle/{listingKey}`
- Search uses Meilisearch with MySQL FULLTEXT fallback

---

## 8. Health Checks

```bash
php artisan serik:reconcile --dry-run
curl http://127.0.0.1:7700/health
php artisan schedule:list
```
