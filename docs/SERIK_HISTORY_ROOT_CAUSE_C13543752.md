# SERIK — Listing History Root Cause Investigation

**Subject:** Why `C13543752` shows one history entry while HouseSigma shows many  
**Date:** 2026-07-14  
**Method:** Real SQL + real AMP HTTP responses + code tracing. No assumptions unmarked as such.

---

## TL;DR (verdict)

| Question | Answer (with evidence) |
|---|---|
| Is this a code bug that deletes / replaces history? | **No.** Recorder is append-only (`PropertyHistory::create`). UI does not even read `re_property_history` for the detail timeline. |
| Is history missing from `re_property_history` for this MLS? | **Yes — 0 rows** in SQL. |
| Does AMP expose this MLS's prior sold/terminated events? | **No.** `HistoryTransactional` = **HTTP 403**. Live `Property` feed returns **only** `C13543752` at that address. Prior MLS numbers (`C12206434`, `C12918614`) return **empty `value: []`**. |
| Can we auto-import the missing HouseSigma events from our feed? | **No — the source data is not available to vendor feed 12667.** Fix requires a licensed archive / `HistoryTransactional` entitlement, not a code change. |
| Why do some listings show “many” history rows in our UI? | Often **other CURRENT live MLS listings** at the same building/address (e.g. other units at `8 York Street`), still present in AMP — **not** sold/expired archives. |

---

## STEP 1 — Where listing history comes from (code trace)

### Detail-page timeline (what the user sees)

`TrebPropertyHelper::fetchListingHistoryForDetail()` builds the UI list from **four live sources**, then dedupes:

```2298:2353:platform/themes/homzen/src/Supports/TrebPropertyHelper.php
public static function fetchListingHistoryForDetail(...)
{
    // 1) AMP Property — same street/unit (fetchUnitPropertyRecords)
    // 2) AMP Property — same building (fetchBuildingPropertyRecords)
    // 3) Local MySQL re_properties — same address siblings (fetchLocalDbHistory)
    // 4) If current ListingKey still missing → inject current Property row
}
```

**Not consulted for the detail timeline:** `re_property_history`.

That table is written by `PropertyHistoryRecorder` on model create / significant field change (`price`, `ClosePrice`, `MlsStatus`, `status`, `close_date`). It is **observation history from the moment we started watching**, not a TREB archive.

### Source map for `C13543752`

| Source | Result for C13543752 |
|---|---|
| `re_property_history` | **0 rows** |
| AMP `Property` by ListingKey | **1 row** (current New / For Sale) |
| AMP `Property` same StreetNumber+StreetName | **1 row** (only itself) |
| AMP `Property` by ParcelNumber `104600232` | **1 row** (only itself) |
| AMP `HistoryTransactional` | **HTTP 403** |
| Local `re_properties` same address | **1 row** (only itself) |
| UI `fetchListingHistoryForDetail` | **1 row** = current listing |

### Contrast listing that “looks full”: `C13172194` (8 York Street 2710)

UI returned **6 rows** — but they are **other current MLS numbers** at the same building (different units), still live in AMP:

```
2026-07-10 For Lease $2295 C13551872  1913 - 8 York Street
2026-07-09 For Lease $2800 C13543538  1009 - 8 York Street
2026-07-08 For Lease $3900 C13540500  2812 - 8 York Street
2026-05-25 For Sale  $885000 C13172194 2710 - 8 York Street   ← this listing
2026-05-24 For Lease $3600 C13167860  2710 - 8 York Street
2026-04-16 For Sale  $573000 C13008216 2905 - 8 York Street
```

`re_property_history` for `C13172194` = **1 row**. So “many history” ≠ TREB sold archive; it is **sibling live listings** at the address/building.

Freehold `151 Dewbourne Avenue` has no units and no other live MLS at that address → UI shows exactly one.

---

## STEP 2 — SQL (exactly as requested)

```sql
SELECT * FROM re_property_history
WHERE external_id='C13543752'
ORDER BY created_at;
```

**TOTAL ROWS: 0**

### Current `re_properties` row

| Field | Value |
|---|---|
| id | 107880 |
| external_id | C13543752 |
| name | 151 Dewbourne Avenue, Toronto, ON M6C 1Z1 |
| price | 3888000 |
| MlsStatus | New |
| TransactionType | For Sale |
| listing_contract_date | 2026-07-09 15:51:27 |
| listing_modified_at | 2026-07-12 14:11:00 |

Exact-name siblings in `re_properties`: **1** (only itself).

### Table-wide stats (for context)

| Metric | Value |
|---|---|
| `re_property_history` total rows | 28,056 |
| Distinct MLS with ≥1 history row | 27,907 |
| MLS with exactly 1 history row | 27,759 |
| MLS with ≥5 history rows | **0** |

Max rows for any one MLS was 3 — and that was from a forced hydrate/diff test on `C13559336`, not a HouseSigma-style archive.

---

## STEP 3 — Append vs replace (code evidence)

`PropertyHistoryRecorder::record()`:

```78:95:platform/plugins/real-estate/src/Supports/PropertyHistoryRecorder.php
PropertyHistory::query()->create([ ... ]);
```

- **Append-only INSERT.** No `delete`, no `truncate`, no “replace history” path in the import/upsert pipeline.
- Grep across `platform/` found **no** history wipe. The only `re_property_history` UPDATE remaps `property_id` during external_id dedupe.
- Why `C13543752` has 0 recorder rows: property was first inserted **before** the history table existed (`created_at` 2026-07-09; migration `2026_07_14_100200`). Later updates kept `MlsStatus=New` / same price, so they did not hit `SIGNIFICANT` fields → no new history rows. **This is expected recorder behavior, not data loss.**

UI “one entry” is **not** caused by replace — it is the current listing injected because AMP + local siblings return nothing else.

---

## STEP 4 — Every AMP endpoint tested (raw)

### OData service document (resources advertised)

Includes: `HistoryTransactional`, `Property`, `Media`, `PropertyRooms`, `OpenHouse`, `Member`, `Office`, … (20 total).

### Results for `C13543752`

| Endpoint | Filter | HTTP | Result |
|---|---|---|---|
| `Property` | `ListingKey eq 'C13543752'` | **200** | 1 active listing |
| `HistoryTransactional` | `ListingKey eq 'C13543752'` | **403** | `"Feed does not include HistoryTransactional resource"` |
| `HistoryTransactional` | `ResourceRecordKey eq 'C13543752'` | **403** | same |
| `PropertyChange` / `PropertyChanges` / `ListingHistory` | ListingKey | **500** | Resource not found |
| `Media` | ResourceRecordKey | **200** | photos (works; not history) |
| `PropertyRooms` | ListingKey | **200** | rooms (works; not history) |
| `OpenHouse` | ListingKey | **200** | `value: []` |
| `Property` siblings | StreetNumber+StreetName | **200** | **value_count=1** (self) |
| `Property` | ParcelNumber `104600232` | **200** | **value_count=1** (self) |
| `Property` | Sold + Dewbourne | **200** | **value_count=0** |
| `PropertyComplex` / `PropertyUnit` / `FloorPlan` | — | 500 | Entity type not available to feed |

Metadata **does** define `HistoryTransactional` and Property fields like `OriginalListPrice`, `PreviousListPrice`, `ClosePrice`, `PriorMlsStatus`.  
For the **live** `C13543752` record those price-history fields are **absent from the payload** (OData omits nulls) — this listing cycle has no previous list price / close price / prior status to expose.  
`LinkProperty=null`, `LinkYN=false` — **no linked prior MLS** on the current record.

---

## STEP 5 — HouseSigma-style timeline vs AMP vs local DB

Documented public third-party events for **151 Dewbourne Avenue** (not scraped into our DB; used only for comparison):

| Event (public market history) | Approx | Our AMP | Our `re_properties` | Our UI |
|---|---|---|---|---|
| Current For Sale **C13543752** @ $3,888,000 | Jul 9, 2026 | ✅ Present | ✅ Present | ✅ Present (only row) |
| Prior sold ~**$3,333,000** (Feb 2026) | Feb 2026 | ❌ Not in live Property | ❌ Not local | ❌ Missing |
| Terminated listing **C12206434** @ $4,795,000 | Jun–Jul 2025 | ❌ AMP `value:[]` | ❌ NOT IN LOCAL DB | ❌ Missing |
| Prior sold ~**$2,000,000** (Mar 2021) | 2021 | ❌ | ❌ | ❌ |
| Prior sold ~**$860,000** (May 2011) | 2011 | ❌ | ❌ | ❌ |

### Proof prior MLS is gone from AMP + local

```
C12206434: AMP HTTP=200 count=0  value=[]
C12918614: AMP HTTP=200 count=0  value=[]
C12206434: NOT IN LOCAL DB
C12918614: NOT IN LOCAL DB
HistoryTransactional for all three keys: HTTP=403
  {"error":{"code":"1109","message":"Feed does not include HistoryTransactional resource"}}
```

### UI output we actually serve

```json
[
  {
    "date_start": "2026-07-09",
    "date_end": null,
    "price": 3888000,
    "event": "For Sale",
    "listing_id": "C13543752",
    "address": "151 Dewbourne Avenue"
  }
]
```

That single row is **exactly** the live AMP/current local listing. Every HouseSigma historical event is missing because **those ListingKeys are not in the live feed and we never captured them while they were live**.

---

## STEP 6 — Implement if data exists?

**Data does not exist in our AMP feed for these events.** There is nothing correct to import without fabricating or scraping (forbidden).

What our architecture **already** does correctly when data *is* available:

- Merge **live** same-address / same-building AMP `Property` rows into the UI timeline.
- Append observation rows into `re_property_history` when price/status change while we watch.

No additional AMP resource returns the missing sold/terminated timeline for this vendor key.

---

## STEP 7 — Proof summary (why each missing event cannot be recovered)

1. **`HistoryTransactional`** — only RESO resource designed for field-level change history — returns **403** for our feed. Entity exists in `$metadata`, but **entitlement is denied**.
2. **Prior MLS numbers** known publicly (`C12206434`, etc.) — AMP `Property` returns **empty**. Sold/terminated listings leave the live Property resource under board feed rules.
3. **Address / parcel sibling search** — only the current active listing.
4. **`LinkProperty` / prior-price fields on current Property** — no link; no previous/close prices populated for this cycle.
5. **Local DB** — never ingested those prior MLS numbers (they were already off-market before / outside our capture window for this address).

Therefore recovery from **current AMP credentials alone is impossible**. This is proven by raw HTTP status codes and empty `value` arrays, not assumed.

---

## STEP 8 — Enterprise recommendations (no scraping)

Ordered by impact:

1. **Request `HistoryTransactional` (and historical Property) entitlement from TRREB/AMPRE** for vendor key `12667` — same resource already named in the service document but blocked with code `1109`.
2. **License a TRREB / AMPRE historical archive (or data product)** that retains Sold/Expired/Terminated listings and sold prices after they drop from the live `Property` feed.
3. **Continuous local snapshotting going forward** (already started via `re_property_history` + 5‑min sync): once a listing is Sold/Terminated while we are syncing, we keep it permanently. This **fills the archive forward** but cannot reconstruct 2011–2025 events we never saw.
4. **Optional commercial licensed comps datasets** (board-approved) for sold history display — not web scraping.

HouseSigma’s longer timeline is explained by **retained historical MLS archives / licensed sold data / years of continuous observation**, not by a secret live `Property` field our code forgot to read.

---

## FINAL — Why every HouseSigma event is present or missing on Serik for `C13543752`

| Event | Present? | Exact reason |
|---|---|---|
| Current For Sale C13543752 $3,888,000 | **Present** | In AMP Property + local DB; UI shows it. |
| All prior sold / terminated / price-path events at 151 Dewbourne | **Missing** | Not in AMP live Property; `HistoryTransactional` forbidden (403); never stored locally under prior MLS keys. |

**Not a bug in append/replace.**  
**Not recoverable by coding against another endpoint in this feed.**  
**Fixable only by feed entitlement / licensed historical data / continuous capture going forward.**

---

### Artefacts from this investigation

Raw probe logs (local):

- `storage/logs/amp-history-probe.log`
- `storage/logs/amp-history-probe2.log`
- `storage/logs/amp-history-probe3.log`
