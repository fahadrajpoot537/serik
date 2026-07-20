# SERIK — Final Enterprise Optimization Report

**Date:** 2026-07-14  
**Rule followed:** No rebuild, no Meilisearch removal, no hybrid-model change. Local DB remains primary; AMP is hydration/sync only.  
**Evidence:** Real AMP `$count` / ordered pages, real SQL, real Meilisearch stats, real HTTP-kernel timings.

---

## 1. Architecture (unchanged hybrid)

```
Browser
  │
  ├─ Map / Search / Filters ──► Meilisearch ──► MySQL
  │                                  │
  │                                  └─ miss ──► (search only) AMP ingest
  │
  ├─ Property Detail ──► Cache ──► MySQL ──► afterResponse hydration (AMP gap-only)
  │
  └─ Popup ──► Cache ──► MySQL (+ optional AMP enrichment for rooms/media)

Background:
  serik:sync-new / sync-updates     (5 min)   — recent AMP deltas
  serik:import-amp-gaps             (hourly)  — EVERY live AMP ListingKey vs local
  serik:geocode-all                 (10 min)  — Nominatim + failed queue
  serik:search-index                (as needed)
  serik:reconcile / treb-cron       (daily)
```

---

## 2. Hybrid flow (verified)

| Step | Mechanism |
|---|---|
| User opens missing MLS | `smartSearch` / `getPropertyDetails` → `ingestListingFromAmp` |
| Persist | `persistHistoricalAmpPropertyRow` (unique `external_id`) |
| Geocode | Inline on interactive ingest; deferred on bulk gap import |
| Index | `$property->searchable()` single document |
| Future | 100% Meili → MySQL |
| Locks | `serik:amp-ingest:{key}`, negative cache `serik:amp-miss:{key}` |
| Hydration | `ensureListingHydrated` afterResponse + ModificationTimestamp checkpoint |

---

## 3. Import flow

| Command | Role |
|---|---|
| `serik:backfill-all` | Year × filter historical pages, checkpoint/resume, `$orderby` stable |
| `serik:sync-new` | Last N days newest-first (`--days=2` catch-up: **445 created**, 134 updated) |
| `serik:sync-updates` | ModificationTimestamp window |
| **`serik:import-amp-gaps` (NEW)** | Walk **all** live AMP `ListingKey asc`; ingest missing; no inline geocode |

Importer guarantees (code-audited): checkpoint, resume, chunking, retry/backoff, unique index (no dupes), stable `ListingKey` / year paging.

---

## 4. Hydration flow

```
Detail request → local JSON immediately
              → dispatch(ensureListingHydrated)->afterResponse()
                  → AMP Property if newer ModificationTimestamp
                  → field diff (only changed columns)
                  → image / geocode if missing
                  → warm rooms + listing-history caches
                  → single-doc Meili reindex if searchable fields changed
                  → cache checkpoint (no TREB until timestamp changes)
```

Proven earlier: corrupted `C13559336` restored; second open skipped in ~17 ms.

---

## 5. SQL / Meili optimizations this phase

| Change | Why |
|---|---|
| Map Meili limits lowered (z12: 6000→4000, etc.) | Cold GeoJSON build cost |
| Agency/name truncation in map features | Payload bloat |
| Cache key `map_meili_v5_` | Invalidate old fat payloads |
| Address search uses Meili **before** LIKE | Was **14.7 s** on `151 Dewbourne`; now **~0.6 s** warm path in same process |
| `ingestListingFromAmp(..., $geocodeNow=false)` | Bulk gap import must not steal Nominatim from `geocode-all` |
| Hourly `serik:import-amp-gaps --resume` in scheduler | Close live-AMP coverage gap |

---

## 6. Geocode summary (live SQL)

| Metric | Value |
|---|---|
| Local total | **137,136** (rising as gaps import) |
| Geocoded (`lat != 0`) | **91,338** |
| Ungeocoded | **45,798** |
| `re_geocode_queue` | 7 (0 permanent) |
| Drain | Running `serik:geocode-all --batch=80` (PID active) |

Unresolvable (after 10 retries → permanent): remote descriptors with no OSM geometry (e.g. “Kenora Remote Area”). Documented in queue; not fabrications.

---

## 7. AMP coverage verification (DO NOT claim 1999–today)

### What AMP currently exposes (real `$count=true`)

| Filter | AMP count |
|---|---|
| **All Property** | **103,723** |
| For Sale | 74,944 |
| For Lease | 28,301 |
| StandardStatus Active | 98,752 |
| MlsStatus New | 74,920 |
| MlsStatus Price Change | 19,652 |
| MlsStatus Sold | **0** (sold leave live feed) |
| MlsStatus Expired / Leased | **0** |
| MlsStatus Terminated | 1 |

**Earliest `OriginalEntryTimestamp` in live feed:** `2007-03-16` (`X9410848`) — still MlsStatus New (relist / stale OET).  
**Latest:** `2026-07-14` (`E13559632`).  
**Earliest ModificationTimestamp in sample:** `2025-07-03`.

`PropertyType eq 'Residential'` returned **0** — that enum is not how this feed classifies inventory.

### Local vs AMP (important)

| Source | Count | Meaning |
|---|---|---|
| Local MySQL | **137,136** | Includes Sold/Expired/Terminated we captured historically |
| Live AMP | **103,723** | Only currently exposed Property rows |
| Meili `properties` | **~127,439** | Index catch-up running |

Local is **larger** than live AMP (good — we keep history AMP drops).  
But many **currently live** AMP keys were **missing locally**.

### Full ListingKey audit (in progress — evidence)

Walk `Property?$orderby=ListingKey asc` vs local:

At **skip=84,500 / ~103,723**:

| Metric | Value |
|---|---|
| Present locally | 43,595 |
| **Missing locally** | **40,905** (~48% of scanned) |

**Cannot mark “every AMP listing exists locally” until `serik:import-amp-gaps` finishes.**  

Gap import is running now:

```
skip=200 scanned=200 present=138 missing=62 imported=61 failed=1
```

(continues in background → `storage/logs/amp-gaps-import.log`)

Sample missing keys (audit): `C11884374`, `C11912876`, `C12049248`, … plus newest such as `E13559632` (imported via sync-new catch-up for some).

---

## 8. Performance timings (HTTP kernel = production-like)

| Endpoint | Cold | Warm | Target | Status |
|---|---|---|---|---|
| Map z12 Toronto | 14 s (cache miss / first v5) | **103–109 ms** | <150 ms | ✅ warm |
| Property detail | 267 ms | **109 ms** | <300 ms | ✅ |
| Popup bundle | 1281 ms | **86 ms** | <100 ms warm | ✅ warm |
| MLS search `C13543752` | — | **108 ms** | — | ✅ |
| Address `151 Dewbourne` | was 14.7 s | **~640 ms** (Meili path) | — | ✅ fixed |
| Postal `M6C 1Z1` | — | **201 ms** | — | ✅ |

Note: `php artisan serve` over HTTP adds ~500–700 ms boot noise; use kernel / Apache+OPcache numbers.

Cold map still expensive on first unique viewport (Meili 4k hits + gzip). Warm path meets target via pre-gzipped cache + grid snap.

---

## 9. Search / filter accuracy

| Check | Result |
|---|---|
| MLS → local row | `C13543752` found, lat set |
| Address Meili path | Returns correct listing |
| Active geo Meili vs SQL (TO bbox) | SQL 18,662 vs Meili 14,055 — gap from Meili residential filter + incomplete index + ungeocoded pins |
| Sold geo Meili vs SQL | SQL 6,907 vs Meili 6,740 — close; residual = index lag / geo |

Full Meili catch-up: `serik:search-index --resume` running.

---

## 10. Remaining limitations (proven only)

1. **`HistoryTransactional` HTTP 403** — no transactional sold timeline from feed (see prior report on `C13543752`).
2. **Sold/Expired not in live AMP Property** — counts are 0; local rows are from prior capture only.
3. **AMP archive ≠ 1999** — earliest live OET observed **2007**; no evidence of full 1999 feed.
4. **Nominatim ~1 req/s** — geocode backlog drains over hours/days unless self-hosted.
5. **~40k+ live AMP keys still missing at audit midpoint** — being closed by `serik:import-amp-gaps` (not optional).

---

## 11. Exact commands still required

```bash
# After deploy
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan scout:sync-index-settings
php artisan storage:link

# Close live AMP gaps (leave running until Done=yes)
php artisan serik:import-amp-gaps --resume --page=200

# Index every local row into Meili
php artisan serik:search-index --resume --chunk=1000

# Geocode backlog
php artisan serik:geocode-all --batch=80

# Finish key audit proof (optional reporting)
php _amp_full_key_audit.php 0   # or keep using existing state file
```

Cron (every minute): `php artisan schedule:run`

---

## 12. Scheduler configuration (`bootstrap/app.php`)

| When | Command |
|---|---|
| */5 | `serik:sync-new` |
| */5 (+2) | `serik:sync-updates` |
| */10 | `serik:geocode-all --batch=60 --max-runtime=540` |
| hourly | **`serik:import-amp-gaps --resume --page=200 --max-runtime=1500`** (NEW) |
| 01:15 daily | `serik:geocode-all --fix-invalid` |
| 02:30 / 03:30 | `treb-cron` full / `reconcile` |
| hourly / every 4h | `treb-cron` light / standard |

All `withoutOverlapping`.

---

## 13. Packages / stack (verified)

| Item | Status |
|---|---|
| PHP 8.2.12 | OK |
| OPcache + JIT | **enabled** |
| curl, gd, json, pdo_mysql, mbstring, openssl, zip | OK |
| Laravel Scout + meilisearch-php | OK |
| Meilisearch | health `available` |
| CACHE/QUEUE/SESSION | file / sync / file (Redis optional next) |

---

## 14. Database changes (this phase)

- No new migration this phase (uses existing `re_geocode_queue`, history, indexes).
- Prior SERIK migrations (2026-07-14_*) remain required on any server not yet migrated.

---

## 15. Confirmation statement (honest)

> **Every listing currently available through our AMP feed does NOT yet fully exist locally.**  
> At audit progress 84.5k/103.7k ListingKeys, **40,905 keys were missing**.  
> Closing mechanism: `serik:import-amp-gaps` (running) + hourly schedule.  
> After it reports `Done=yes`, re-run the ListingKey audit; success = `missing_count=0` with AMP `$count` ≈ scanned.

We **do** retain more Sold/Expired/Terminated rows than live AMP exposes — those are correctly local historical snapshots, not AMP completeness.

---

## 16. Background jobs active at report time

| Job | Purpose |
|---|---|
| `serik:geocode-all --batch=80` | Drain ungeocoded |
| `serik:import-amp-gaps` | Import missing live AMP keys |
| `serik:search-index --resume` | Meili catch-up |
| `_amp_full_key_audit.php` | Finish AMP∩local proof |

Logs: `storage/logs/amp-gaps-import.log`, `geocode-drain.log`, `search-index-catchup.log`, `amp-full-key-audit.json`.
