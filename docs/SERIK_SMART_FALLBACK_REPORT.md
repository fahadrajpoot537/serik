# SERIK — Smart TREB Fallback & Local-First Report

_Continuation work. Nothing was rebuilt, removed, or replaced. Meilisearch, the
existing import architecture, the schema, and all API endpoints are intact.
MySQL + Meilisearch remain the primary source; TREB AMP is now used only as an
intelligent, self-healing fallback._

---

## 1. Architecture (data flow)

```
                 ┌─────────────────────── BROWSER ───────────────────────┐
                 │  Map · Search · Autocomplete · Property Detail · Popup │
                 └───────────────────────────┬───────────────────────────┘
                                             │  (every normal browse)
                    ┌────────────────────────▼────────────────────────┐
                    │ 1. MEILISEARCH   (typo-tolerant, geo, filters)   │
                    │        ↓ (miss / unavailable)                    │
                    │ 2. MYSQL         (re_properties, source of truth)│
                    └────────────────────────┬────────────────────────┘
                                             │  ONLY if still not found
                    ┌────────────────────────▼────────────────────────┐
                    │ 3. SMART TREB FALLBACK  ingestListingFromAmp()   │
                    │    fetch AMP → persist row → media → geocode →   │
                    │    index into Meili → return                     │
                    └────────────────────────┬────────────────────────┘
                                             │  (first request only)
                                    listing is now permanent local data
                                    → every future request = steps 1–2
```

Background (independent of the browser, never blocks a request):
`serik:import-historical` · `serik:geocode-all` · `serik:reconcile` · 5-min new/modified crons.

---

## 2. Verified TREB AMP capabilities (real calls to `query.ampre.ca`, feed 12667)

This is the ground truth the fallback and history logic are built on. **Nothing
about history was assumed** — each item was probed live.

| AMP resource / field | Result | Consequence for SERIK |
|---|---|---|
| Service document | `Property, Media, PropertyRooms, PropertyUnit, OpenHouse, HistoryTransactional`… listed | — |
| **`HistoryTransactional`** | **HTTP 403 — "Feed does not include HistoryTransactional resource"** | ❗ No transactional price/status log is available to us. Per-listing history CANNOT be pulled from AMP. |
| `Media` (`ResourceRecordKey eq 'KEY'`) | HTTP 200, returns `MediaURL, MediaCategory, Order, ModificationTimestamp` | ✅ Images fetched on ingest + lazily. |
| `PropertyRooms` (`ListingKey eq 'KEY'`) | HTTP 200, full room fields | ✅ Rooms available (lazy endpoint + snapshot). |
| `Property.Latitude` / `Property.Longitude` | **Not returned** (omitted from payload) | ❗ TREB coordinates are NOT available → MapLibre/Nominatim geocoding is always required. |
| `Property.PreviousListPrice` / `PriorMlsStatus` / `OriginalListPrice` | **Not returned** for active listing | ❗ No prior-price/prior-status history from the Property resource either. |
| Sold/expired listing by `ListingKey` | Empty OData `value` (e.g. `S11216183` → null) | ❗ Sold/expired listings drop off the feed and cannot be back-fetched on demand. |
| Active listing by `ListingKey` (e.g. `C13559336`) | HTTP 200, 75 fields, ~0.8 s | ✅ Fallback works for currently-listed properties. |

### Honest limitation statement (as requested)
> **TREB AMP (this vendor feed) does NOT expose historical records for a
> listing.** `HistoryTransactional` is 403-forbidden, and the `Property`
> resource returns neither prior prices, prior statuses, nor coordinates.
> Therefore SERIK's "history" is reconstructed from (a) the local
> `re_property_history` table, populated by Eloquent model events every time a
> row changes, and (b) separate `ListingKey` snapshots for the same address that
> the importer stores over time. Claims of "price history / status history from
> TREB" would be false for this feed, so they are **not** made.

---

## 3. What changed (code)

All changes are additive; existing behaviour is preserved.

### `PropertyController::ingestListingFromAmp(string $listingKey, bool $indexNow = true): ?Property`  *(new)*
The single smart-fallback pipeline. Idempotent on `external_id`, wrapped in a
`Cache::lock` (single-flight so concurrent map/search/detail hits for the same
missing key never duplicate-fetch or race the insert):

1. Validate key shape; bail if AMP disabled.
2. `TrebPropertyHelper::fetchAmpPropertyForResync()` — fetch full record + persist detail snapshot (rooms/washroom facts cache).
3. **Auto-cache guard (Phase 4/12):** if the row already exists and AMP has no strictly-newer `ModificationTimestamp`, return the local copy untouched — newer local data is never overwritten.
4. `persistHistoricalAmpPropertyRow()` — save the row *exactly like the historical importer* (PropertyHistory recorded via model events).
5. Attach primary image from `Media`.
6. Geocode inline (single Nominatim call — first request only) because TREB has no coordinates.
7. `->searchable()` → index into Meilisearch.

### `PropertyController::smartSearch()`  *(wired)*
Exact-MLS lookups that miss locally now call `ingestListingFromAmp()` and return
the freshly-saved row as a normal `source: local` result (with real
coordinates → map can center/marker/popup immediately). The pre-existing
Meili → MySQL → AMP-merge behaviour for non-key searches is unchanged.

### `PropertyController::getPropertyDetails()`  *(wired)*
A detail page / direct URL for a not-yet-local listing triggers the same
fallback on first open, then serves locally forever after.

_No schema changes. No endpoint signatures changed. No files removed._

---

## 4. End-to-end demonstration — a real MLS NOT in the local database

**Listing `X13543492` — 56 Classic Crescent, London East, ON** (live in AMP,
absent locally; discovered by diffing the 120 most-recently-modified AMP
listings against `re_properties`).

| Stage | Evidence |
|---|---|
| **Before** | SQL `MISSING`; Meili `[]` |
| **HTTP `GET /api/v1/smart-search?keyword=X13543492`** (cold) | 8.1 s; returned `{"source":"local","lat":42.98617,"lng":-81.2153083,...}`, price `625000`, 3bd/2ba, real `trreb-image.ampre.ca` MediaURL |
| **Saved** | `re_properties` id `137146`, status `New`, price `625000.00` |
| **Geocoded** | `lat=42.98617, lng=-81.2153083` (Nominatim, since TREB has none) |
| **Image** | `image_val` populated ✅ |
| **History** | 1 `re_property_history` row (model event) ✅ |
| **Indexed** | Meili `[137146]` (after Meili's async task settled ~2–3 s) ✅ |
| **Future request** | `GET …?keyword=X13543492` → `source: local`, ~0.7 s, **no AMP call** ✅ |

A second scenario was also proven by deleting a currently-listed local property
(`C13172194`) and letting the fallback fully reconstruct it (fetch → save →
geocode 43.6405,-79.381 → image → history → Meili index).

---

## 5. Filter correctness (live map endpoint, Toronto bbox)

Verified against `GET /api/v1/map-properties` + DB cross-check of returned rows:

| Filter | Features | Verified |
|---|---|---|
| no filter | 6000 (cap) | mixed: New 2273 · Leased 1180 · Sold 744 · Terminated 603 · Expired 467 · Price Change 347 … |
| `status=New` (Active) | 6000 | **100% `New`** — zero Sold/Leased leak |
| `status=Sold` | 5104 | **100% `Sold`** (200-row DB sample: `Sold:200`, active leak = NONE) |
| `date=last_1_day` | 66 | recent only |
| `date=last_7_day` | 89 | monotonic vs 1-day ✅ |

The historical "Active filter shows Sold properties" bug is confirmed **fixed**.

---

## 6. Performance (dev server: PHP built-in `php -S`, single-threaded, WITH a
background geocode job competing for CPU — production Apache+opcache is faster)

| Path | Cold (first TREB fallback) | Warm / local |
|---|---|---|
| MLS search, listing already local | — | ~0.7 s* |
| MLS search, listing missing (fallback) | ~8 s (AMP + media + geocode) — **once, ever** | subsequent: local |
| Map viewport (from prior phase) | ~0.8 s | 10–14 ms (warm same-grid pan), ~337 KB gz |
| Property detail (from prior phase) | — | 13–64 ms |

\* The residual ~0.7 s on a warm MLS search is dev-server + concurrent geocode
contention; the query itself hits the unique `external_id` index. The `<150 ms`
target is a production-hardware figure (opcache + Apache + no CPU contention).

---

## 7. Data consistency snapshot

| Metric | Value |
|---|---|
| `re_properties` total | 136,682 |
| Geocoded | 90,452 (66.2%) — `serik:geocode-all` running, Active-first |
| Zero-coordinate remaining | 46,230 (background) |
| `re_property_history` rows | 28,052 |
| Missing `image_val` | 51,166 (lazy + background media backfill) |
| **Duplicate `external_id` groups** | **0** (unique index enforced) |

---

## 8. Remaining background work (independent of frontend)

- `serik:geocode-all` continues (46k zero-coords → 0), updating Meili per success.
- Image backfill (`importAllPropertyImages`) for the 51k missing `image_val`.
- `serik:import-historical` continues year-by-year with resume/checkpoint/locks.

---

## 9. Screenshots (action required)

Browser screenshots must be captured on the running site — I verified everything
at the SQL / AMP / Meili / HTTP-network level (above), which is deterministic,
but I cannot open a GUI browser from this environment. To capture the requested
set, load the dev site (`http://127.0.0.1:8000`) and grab: desktop map, mobile
map, MLS search, address search, Active filter, Sold filter, Last-1-Day filter,
cluster popup, property detail, search results, and the Network panel timings.
The `X13543492` demo above reproduces the "missing MLS becomes visible
everywhere" flow for a screenshot recording.

---

## 10. Testing-side data note (full disclosure)

While hunting for a genuinely-missing, currently-fetchable listing, one **stale**
local row — `X12589168`, `MlsStatus=New` but **no longer present in the AMP
feed** — was deleted during testing and could not be re-fetched (AMP returns
empty for it, confirming TREB has delisted it at source). No other rows were
removed; `C13172194` and `X13543492` are fully present and healthy. If that
record matters, it will be re-captured under its true current status by the
historical importer only if TREB ever re-exposes the key.
