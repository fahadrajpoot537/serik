# SERIK — Smart Hydration & Listing-History Report

_Continuation work. The project was NOT rebuilt. Meilisearch, the local
database, the historical importer, and the existing hybrid architecture are all
intact. This phase ADDS non-blocking "smart hydration" and documents the real,
verified limits of TREB listing history. No schema changes. No endpoints
removed._

---

## 0. TL;DR

- **Listing history investigated with real AMP + SQL.** Our feed **cannot**
  provide the multi-year Sold/Expired/Relisted timeline HouseSigma shows —
  `HistoryTransactional` is **HTTP 403** for our vendor feed, the `Property`
  record (301 fields) has **no** cross-reference to previous MLS numbers, and
  aged-out sold listings are **removed** from the queryable feed. We do **not**
  fake history. Full explanation in §7.
- **Smart hydration added:** opening a property detail now completes any missing
  DB-backed sections (image, coordinates) and warms history+rooms caches **after
  the HTTP response is sent** (non-blocking), guarded by a per-listing
  checkpoint keyed to `ModificationTimestamp` so nothing is re-fetched until TREB
  changes.
- **Verified end-to-end** on `C13543752`, `W13550014`, `X13543492`: all now have
  image + coordinates + Meili index + rooms; detail page never blocks.

---

## 1. Files changed

| File | Change |
|---|---|
| `platform/plugins/real-estate/src/Http/Controllers/API/PropertyController.php` | Added `ensureListingHydrated()` (persist missing image/coords, warm history+rooms caches, checkpoint + lock) and `listingNeedsHydration()`; wired a non-blocking `dispatch(...)->afterResponse()` hydration call into `getPropertyDetails()`. |
| `docs/SERIK_HYDRATION_REPORT.md` | This report. |

_(Previous phase — already in place — added `ingestListingFromAmp()` smart TREB
fallback in the same controller; unchanged here.)_

No migrations, no config edits, no removed code this phase.

---

## 2. SQL changes

**None this phase.** History remains in the existing `re_property_history`
table (append-only snapshots via `PropertyHistoryRecorder`, written on every
tracked-field change through Eloquent model events). Verified integrity:
`0` duplicate `external_id` groups; unique index enforced.

---

## 3. Laravel changes (behaviour)

### `ensureListingHydrated(string $listingKey, bool $force = false): array`
Completeness-driven, section-by-section hydration:

1. **Checkpoint gate (smart cache).** `serik:hydrated:{KEY}` stores the listing's
   `listing_modified_at`. If it matches, the method returns immediately (zero
   remote work). A newer TREB `ModificationTimestamp` invalidates it.
2. **Concurrency lock.** `Cache::lock('serik:hydrate-lock:{KEY}')` → one pass per
   key even under a burst of detail requests.
3. **image_val** — if empty, fetch first `Media` URL and persist to MySQL.
4. **coordinates** — if `lat==0`, geocode (Nominatim), persist, re-index Meili.
5. **listing history + rooms** — warm the exact caches the lazy detail endpoints
   (`listing-history`, `property-rooms`) already read from.

### `getPropertyDetails()`
After building the response, if `listingNeedsHydration()` is true it schedules
`ensureListingHydrated()` via `dispatch(...)->afterResponse()` — the payload is
sent first, hydration runs after. Fully-hydrated listings schedule nothing.

### Map — unchanged and confirmed LOCAL-ONLY
`fetchMapProperties()` / `fetchMapPropertiesViaMeili()` contain **no** AMP calls
(verified by scanning the method bodies). Viewport = Meilisearch → MySQL only.

---

## 4. Verification results (real SQL / AMP / Meili / HTTP)

### Smart hydration (final state)
| MLS | Image | Coordinates | Meili | History (cached) | Rooms (cached) | Checkpoint |
|---|---|---|---|---|---|---|
| `C13543752` | ✅ | 43.697292, -79.4307606 | INDEXED | 1 | 13 | set |
| `W13550014` | ✅ | 43.5060143, -79.6570834 | INDEXED | 1 | 9 | set |
| `X13543492` | ✅ | 42.98617, -81.2153083 | INDEXED | 1 | 10 | set |

- `C13543752` before: **no image, no coordinates** → after hydration: both
  persisted, 13 rooms warmed.
- Second hydration call for every listing returned `skipped: true` (checkpoint
  fresh) → **no re-fetch** (smart cache proven).
- HTTP `getPropertyDetails/C13543752` returned in ~0.7 s (dev server + concurrent
  geocode job) with hydration deferred; the checkpoint was re-set **after** the
  response — proving `afterResponse` fired without blocking the user.

### Non-blocking proof
`dispatch(closure)->afterResponse()` runs during framework `terminate()` (after
`$response->send()`), so the browser receives the detail JSON first. Queue driver
is `sync`, so no external worker is required for this to run.

---

## 5. Performance timings (dev: `php -S`, single-thread, background geocode
running — production Apache+opcache is faster)

| Path | Timing |
|---|---|
| Property detail (local, complete) | ~0.7 s dev; hydration deferred, non-blocking |
| `ensureListingHydrated` — needs image+coords+rooms (cold) | ~8–10 s (once), fully persisted |
| `ensureListingHydrated` — already complete (warm) | 13–49 ms (cache) |
| `ensureListingHydrated` — checkpoint fresh | ~0 ms (skipped) |
| Map viewport (prior phase) | 10–14 ms warm / ~0.8 s cold, ~337 KB gz, **no AMP** |

---

## 6. Remaining TREB limitations (verified, not assumed)

| Capability | Real result | Impact |
|---|---|---|
| `HistoryTransactional` | **HTTP 403** "Feed does not include HistoryTransactional resource" | No transactional price/status log available. |
| `Property` cross-references | 301 fields; only `CrossStreet`, `OriginalEntryTimestamp` are history-adjacent. **No** PreviousListingId / linked MLS / prior key. | Cannot walk to a listing's earlier MLS numbers. |
| Same-address AMP siblings | `C13543752`, `W13550014`, `X13543492` each return **1** (the listing itself) | No prior/re-listed records exist in the feed for these. |
| Aged-out sold listings | e.g. `S11216183` → empty OData `value` | Sold/expired listings drop off; cannot be back-fetched. |
| `Property.Latitude/Longitude` | Not returned | Geocoding always required (MapLibre/Nominatim). |
| `Media`, `PropertyRooms` | HTTP 200 ✅ | Images + rooms hydrate fine. |

---

## 7. Why HouseSigma shows more history (honest explanation)

For `C13543752` (151 Dewbourne Ave) HouseSigma may show Sold / Expired /
Terminated / Re-listed / price changes across years. **We cannot reproduce that
from our feed**, and here is exactly why:

1. **Each re-listing is a separate MLS number.** When a property is re-listed,
   TREB issues a **new** `ListingKey`. A property's "timeline" is therefore
   spread across many keys.
2. **Our feed has no link between those keys** — verified: no
   PreviousListingId / cross-reference field exists on the `Property` resource.
3. **Old keys are not queryable.** Once a listing closes and ages out, AMP
   returns empty for it (verified). So we can neither discover nor fetch a
   property's historical MLS numbers on demand.
4. **`HistoryTransactional` is forbidden (403)** for our vendor feed — the one
   resource that would expose an event log is not licensed to us.

**HouseSigma's extra history therefore comes from sources we do not have:**
- **Years of continuous ingestion** — they captured each MLS listing (and its
  status changes) live, at the time it happened, and archived it. That
  accumulated archive cannot be back-filled from today's feed.
- **Licensed historical datasets** (e.g. board/CREA historical archives or
  purchased sold-data feeds) that include closed/expired records we are not
  entitled to.
- **Address-level merging** of all those archived MLS numbers into one timeline.

What SERIK legitimately does:
- **Builds history going forward** — `PropertyHistoryRecorder` logs every
  price/status/date change our crons observe from now on. Over time each listing
  accrues a real, first-party timeline.
- **Merges what it truly has** — `fetchListingHistoryForDetail()` already unions
  (a) AMP same-unit siblings, (b) AMP same-building siblings, and (c) local DB
  rows at the same address, deduped into one timeline. This surfaces multiple
  events **when the records exist** (they simply don't for brand-new listings
  like the three tested).
- **Never fabricates events.** A single-entry history means only one real record
  exists — not a bug.

---

## 8. Deployment — files to upload

Standard Laravel/Botble deploy. Upload the whole app tree except local-only
artifacts:

| Path | Upload? | Notes |
|---|---|---|
| `app/` | ✅ | |
| `bootstrap/` (incl. `app.php` with the schedule) | ✅ | exclude `bootstrap/cache/*.php` (regenerated) |
| `config/` | ✅ | incl. `config/scout.php`, `config/treb.php` |
| `database/` | ✅ | migrations |
| `platform/` | ✅ | **plugins/real-estate** + **themes/homzen** (all changes live here) |
| `public/` | ✅ | incl. `public/.htaccess` (gzip/brotli), exclude `public/storage` symlink target |
| `resources/` | ✅ | |
| `routes/` | ✅ | incl. `routes/console.php` |
| `storage/` | ⚠️ | upload structure only; do **not** overwrite prod `storage/app`, `storage/logs`; ensure writable |
| `vendor/` | ➖ | prefer `composer install` on server; upload only if server has no Composer |
| `composer.json` / `composer.lock` | ✅ | |
| `package.json` / `package-lock.json` | ✅ | only if building assets on server |
| `artisan` | ✅ | |
| `.env` | ⚠️ | do NOT copy dev `.env`; set prod values (see §8.1) |

### 8.1 `.env` values that matter
```
APP_ENV=production
APP_DEBUG=false
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=<strong-prod-master-key>      # rotate; do not reuse dev key
SCOUT_QUEUE=false                              # hydration/index run inline
QUEUE_CONNECTION=sync                          # or 'redis' when workers added
CACHE_STORE=file                               # 'redis' recommended for locks at scale
TRREB_AUTH=<prod AMP token>
TRREB_AUTH1=<prod AMP token 2>
```
> Note: hydration uses `Cache::lock`. On the `file`/`database` cache store this
> works single-server. For multi-server, switch `CACHE_STORE=redis` so the locks
> and `serik:hydrated:*` checkpoints are shared.

---

## 9. Commands to run after deployment (in order)

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload -o

php artisan optimize:clear
php artisan migrate --force
php artisan storage:link

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Search
php artisan scout:sync-index-settings
php artisan serik:search-index --resume       # (re)build Meili documents

# Data completeness (safe to run/resume anytime)
php artisan serik:geocode-all --resume
php artisan serik:backfill-all --resume        # historical importer

# Warm caches (optional)
php artisan config:cache && php artisan event:cache
```

If Meili filterable/sortable attributes were changed, also force a settings sync
against the live index (already covered by `scout:sync-index-settings`).

---

## 10. Required packages / environment (verified on this machine)

**PHP 8.2.12** — extensions present & required: `curl, gd, mbstring, pdo_mysql,
zip, bcmath, fileinfo, openssl, exif`. (Recommended additionally: `intl`,
`imagick` optional — GD is sufficient; SERIK does not require Imagick.)

**Composer packages (from `composer.lock`):**
- `laravel/framework v12.38.1`
- `laravel/scout v11.3.0`
- `meilisearch/meilisearch-php v1.16.1`
- `guzzlehttp/guzzle 7.10.0`
- `botble/platform dev-main`

**Services:**
- **Meilisearch 1.49.0** (running, master-key auth) — keep ≥ 1.16 for the geo +
  filterable-timestamp features in use.
- **MySQL/MariaDB** (MariaDB 10.4 dev) — InnoDB; unique index on `external_id`;
  date/geo composite indexes from prior phases.
- **Apache** with `mod_deflate` (+ optional `mod_brotli`), `mod_rewrite`,
  `mod_headers` — `public/.htaccess` already ships gzip rules incl.
  `application/json` / `application/geo+json`.

**Frontend:** MapLibre GL JS (already vendored in the theme; no build step
required for the map). Node/npm only needed if rebuilding theme assets.

**Redis:** not required today (cache/queue = file/sync). Recommended for
production scale (shared locks, faster cache, real queue workers) — a future,
non-blocking upgrade.

---

## 11. Cron jobs (already configured in `bootstrap/app.php`, verified)

A single system cron drives Laravel's scheduler:
```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```
The scheduler (all with `withoutOverlapping` locks + background + per-job log):

| Job | Schedule | Purpose |
|---|---|---|
| `serik:sync-new --days=1 --pages=6` | every 5 min | Import NEW listings |
| `serik:sync-updates --days=3 --chunk=300` | every 5 min (offset `2-59/5`) | Import MODIFIED listings (never collides with sync-new) |
| `serik:geocode-all --batch=100 --max-runtime=3300` | daily 01:15 | Geocode backlog (resumable) |
| `serik:reconcile --days=7 --fix-coords` | daily 03:30 | Deep data reconciliation + re-index |
| `serik:treb-cron --mode=light` | hourly | Light periodic pass + geocode |
| `serik:treb-cron --mode=standard` | every 4 h | Standard resync |
| `serik:treb-cron --mode=full` | daily 02:30 | Full resync + address history |

All jobs are idempotent, checkpoint/lock protected, and recover after crashes
(the historical importer resumes from its stored checkpoint). Health endpoint:
`GET /up`.

---

## 12. Did we meet the objective?

**Yes, within verified TREB capability:**
- The app **automatically hydrates incomplete listings** (image, coordinates,
  rooms/history caches) on first view and **does not affect frontend
  performance** — hydration runs after the response, guarded by a
  ModificationTimestamp checkpoint and a per-listing lock.
- **Local-first everywhere; map is local-only.** TREB is touched only to fill a
  genuine gap, then the data is permanent.
- **History is complete to the extent the feed allows**, merged across
  same-address records, and **never fabricated**. The reason HouseSigma shows
  more is documented and is a data-licensing / historical-archive difference,
  not a bug in SERIK (see §7).

**Cannot be claimed (honest):** we cannot retroactively display a property's
pre-existing multi-year MLS history, because our TREB feed neither exposes
`HistoryTransactional` (403) nor links prior MLS numbers, and closed listings are
removed from the feed.
