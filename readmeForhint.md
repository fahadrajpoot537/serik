# SERIK Project — Setup & Operations Guide

Project path: `c:\xampp\htdocs\SERIK-01-06-2026`

This file documents migrations, cron jobs, sync commands, and features implemented for the Ontario map / TREB (AMP) property system.

---

## 1. First-time setup

```powershell
cd c:\xampp\htdocs\SERIK-01-06-2026
composer install
copy .env.example .env
php artisan key:generate
```

Configure `.env`:
- `APP_URL=http://127.0.0.1:8000`
- Database credentials (MySQL via XAMPP)
- TREB/AMP token (used by `TrebPropertyHelper`)

---

## 2. Migrations (run in order)

Run all plugin migrations:

```powershell
php artisan migrate
```

**SERIK-specific migrations added during map/date/visit work:**

| Migration | Purpose |
|-----------|---------|
| `2026_07_07_120000_add_listing_dates_to_re_properties_table.php` | Adds `listing_contract_date`, `listing_modified_at`, `close_date` |
| `2026_07_08_020000_add_purchase_contract_date_to_re_properties_table.php` | Adds `purchase_contract_date` |
| `2026_07_08_100000_create_re_property_visits_table.php` | User property visit history + delete requests |

Run a single migration if needed:

```powershell
php artisan migrate --path=platform/plugins/real-estate/database/migrations/2026_07_08_100000_create_re_property_visits_table.php
```

---

## 3. Artisan sync commands

| Command | What it does |
|---------|--------------|
| `php artisan serik:sync-sold --days=30` | Import/update sold & leased from AMP + auto-geocode |
| `php artisan serik:sync-dates --days=60` | Refresh listing dates + MlsStatus from AMP |
| `php artisan serik:geocode --rounds=5` | Geocode properties missing lat/lng (batch 500) |
| `php artisan serik:sync-now` | Quick sync: dates + sold 30 days + geocode (8 batches) |
| `php artisan schedule:run` | Run scheduled tasks once (use every minute via Task Scheduler) |
| `php artisan cms:accounts:purge-expired` | Delete frontend user accounts expired after **90 days** (TREB/VOW rule) |

**Recommended manual refresh (after deploy or when map looks stale):**

```powershell
php artisan serik:sync-now
```

Then clear cache:
```
http://127.0.0.1:8000/clear-serik-cache.php?key=serik2026clear
```

---

## 4. HTTP API sync endpoints (browser / cron URL)

| URL | Purpose |
|-----|---------|
| `/api/v1/addpropertiescron` | Import active/modified listings by city (rotates 20 Ontario cities) |
| `/api/v1/sync-recent-sold?days=30` | Sold/leased sync + geocode |
| `/api/v1/sync-amp-listing-dates?days=60` | Refresh listing date columns from AMP |
| `/api/v1/addAllOntarioProperties` | Geocode batch (500 per call) |
| `/api/v1/map-properties` | Map GeoJSON API |

Run `addpropertiescron` **15–20 times** to cycle all cities.

---

## 4b. User account auto-expire (90 days — TREB/VOW)

Frontend users (`re_accounts`) automatically expire **90 days** after registration (or after `password_expire` date if set).

### Manual command

```powershell
php artisan cms:accounts:purge-expired
```

**Output example:** `Deleted 3 expired account(s).`

### What it does

- Finds accounts where `created_at` (or `password_expire`) is older than **90 days**
- **Permanently deletes** those accounts from `re_accounts`
- User must **register again** to access sold listings / VOW content

### Automatic schedule

Already registered in the plugin — runs **daily at 1:00 AM** when scheduler is active:

| Command | Schedule |
|---------|----------|
| `cms:accounts:purge-expired` | Daily at **01:00** |

This works automatically if Windows Task Scheduler runs `serik-scheduler.bat` every minute (see section 5).

### Also checked on login

Even without cron, expired users are blocked when they try to log in:
- Middleware: `EnsureAccountRegistrationNotExpired`
- Message: *"Your registration has expired after 90 days. Please register again to continue using the site."*

### Related files

| File | Purpose |
|------|---------|
| `platform/plugins/real-estate/src/Commands/PurgeExpiredAccountsCommand.php` | Artisan command |
| `platform/plugins/real-estate/src/Supports/AccountRegistrationExpiry.php` | 90-day logic |
| `platform/plugins/real-estate/src/Http/Middleware/EnsureAccountRegistrationNotExpired.php` | Blocks expired users on each request |

---

## 5. Local Windows cron (Task Scheduler)

### A) Scheduler (automatic — recommended)

1. Open **Task Scheduler** → Create Basic Task
2. Name: `SERIK Scheduler`
3. Trigger: Daily, repeat every **1 minute**
4. Action: Start program
5. Program/script:
   ```
   c:\xampp\htdocs\SERIK-01-06-2026\scripts\serik-scheduler.bat
   ```

### B) Manual sync batch file

Double-click:
```
c:\xampp\htdocs\SERIK-01-06-2026\scripts\serik-sync-now.bat
```

### Scheduled jobs (in `bootstrap/app.php`)

| Job | Interval |
|-----|----------|
| `serik:sync-properties` | Every 4 hours |
| `serik:sync-sold --days=30` | Every 2 hours |
| `serik:sync-dates --days=60` | Every 3 hours |
| `serik:geocode --rounds=3` | Every hour |
| `cms:accounts:purge-expired` | Daily at **01:00 AM** (plugin scheduler) |

---

## 6. Map filters (HouseSigma-style)

Date filters work for **Active**, **Sold**, and **De-listed** on Ontario map.

**Sold date columns used:**
- `purchase_contract_date` / `close_date`
- `listing_modified_at` (when marked sold in AMP)
- TREB keys merged from AMP `CloseDate`, `PurchaseContractDate`, `ModificationTimestamp`

**Test URLs (Ontario):**

```
/on/ontario/map?status=Sold,Sold+Conditional,Sold+Conditional+Escape,Leased,Leased+Conditional&date_sold=last_1_day
/on/ontario/map?status=Sold,...&date_sold=last_3_day
/on/ontario/map?status=Sold,...&date_sold=last_7_day
/on/ontario/map?status=Sold,...&date_sold=last_30_day
```

Active:
```
/on/ontario/map?status=New,Price+Change,Extension,Previous+Status&date=last_7_day
```

**Login:** Not required to load map dots. Sold detail requires account login (VOW). Guest sees blurred sold markers.

---

## 7. Property count limits (map)

Map API limits were increased (was 1500 max → now up to **30,000** by zoom) in `PropertyController@fetchMapProperties`.

If counts still look lower than HouseSigma:
1. HouseSigma queries live MLS; SERIK uses local DB + periodic sync
2. Run `serik:sync-now` and geocode until `geocoded: 0`
3. Run `addpropertiescron` multiple times for all cities
4. Only geocoded properties (`latitude > 0`) appear on map

---

## 8. Property visit history (new feature)

### User (account login)
- Menu: **Account → Visit History** (`/account/visits`)
- Auto-recorded when logged-in user opens a property on the map
- User can **Request Delete** → admin must approve

### Admin
- Menu: **Real Estate → Property Visits** (`/admin/real-estate/property-visits`)
- See: user name, property, listing key, location, price, MLS status, view count, last viewed
- **Approve Delete** for pending user requests → soft delete (hidden from user)

### Permissions (assign to Admin role)
- `property-visit.index`
- `property-visit.edit`
- `property-visit.destroy`

After deploy, go to **Admin → Platform Administration → Roles → Admin** and ensure new permissions are checked (or re-save role).

---

## 9. Key files changed

| Area | File |
|------|------|
| Map API, sync, geocode | `platform/plugins/real-estate/src/Http/Controllers/API/PropertyController.php` |
| Visit history model | `platform/plugins/real-estate/src/Models/PropertyVisit.php` |
| Admin visits | `platform/plugins/real-estate/src/Http/Controllers/PropertyVisitController.php` |
| User visits | `platform/plugins/real-estate/src/Http/Controllers/Fronts/AccountPropertyVisitController.php` |
| Artisan commands | `routes/console.php` |
| Scheduler | `bootstrap/app.php` |
| Map UI | `platform/themes/homzen/partials/shortcodes/hero-banner/styles/style-4.blade.php` |
| Windows scripts | `scripts/serik-scheduler.bat`, `scripts/serik-sync-now.bat` |

---

## 10. Troubleshooting

| Problem | Fix |
|---------|-----|
| Map empty / few dots | `php artisan serik:sync-now` then clear cache |
| Sold last 1–30 days empty | `php artisan serik:sync-sold --days=30` |
| Properties in DB but not on map | `php artisan serik:geocode --rounds=10` |
| `addpropertiescron` already running | Wait 10 min or `GET /api/v1/clear-amp-lock` |
| Stale map after sync | `clear-serik-cache.php?key=serik2026clear` |
| Admin can't see Property Visits | Add permissions to Admin role |
| User can't login after 90 days | Normal TREB rule — run `php artisan cms:accounts:purge-expired` or user re-registers |

---

## 11. Production server cron (Linux)

```cron
* * * * * cd /path/to/SERIK && php artisan schedule:run >> /dev/null 2>&1
```

---

*Last updated: July 2026 — SERIK Ontario map + visit history*
