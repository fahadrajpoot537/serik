# Serik — TREB/AMP Cron Jobs (Live Server Setup)

Yeh document batata hai ke production server par TRREB (PropTX/AMP) se saara zaroori data kaise automatically sync ho.

---

## 1. Pehle `.env` set karein

```env
TRREB_AUTH=your_amp_bearer_token_1
TRREB_AUTH1=your_amp_bearer_token_2
```

Config cache ke baad bhi tokens load hon:

```bash
php artisan config:cache
```

Verify:

```bash
php scripts/debug-treb-auth.php
```

---

## 2. Server par EK cron entry (required)

Laravel scheduler har minute chalna chahiye. Server crontab mein yeh line add karein:

```cron
* * * * * cd /var/www/serik && php artisan schedule:run >> /dev/null 2>&1
```

**Path change karein** apne project folder ke mutabiq, maslan:

- Linux: `/var/www/html/serik`
- cPanel: `/home/username/public_html`

Windows Task Scheduler par har minute:

```bat
cd C:\xampp\htdocs\SERIK-01-06-2026 && php artisan schedule:run
```

---

## 3. Automatic jobs (schedule se chalti hain)

| Command | Kab chalti hai | Kya karti hai |
|--------|----------------|---------------|
| `serik:treb-cron --mode=light` | **Har ghante** | Listing dates refresh (14 din), geocode |
| `serik:treb-cron --mode=standard` | **Har 4 ghante** | Naye listings import, sold/leased (30 din), dates (60 din), geocode, active listings AMP detail resync, legacy backfill, address history (500/batch) |
| `serik:treb-cron --mode=full` | **Roz 2:30 AM** | Upar wala sab + zyada geocode, sold 120 din, resync 30 din, history 2000/batch |

### Har mode mein kya data aata hai

| Data | Light | Standard | Full |
|------|-------|----------|------|
| Naye/for-sale listings (AMP import) | — | ✅ | ✅ |
| Sold / Leased listings | — | ✅ 30 din | ✅ 120 din |
| Listing dates, MlsStatus | ✅ 14 din | ✅ 60 din | ✅ 90 din |
| Property detail fields (beds, bath, garage, etc.) | — | ✅ active, 7 din | ✅ active, 30 din |
| Listing history per address | — | ✅ 500/run | ✅ 2000/run |
| Legacy backfill (purane galat fields) | — | ✅ 200/run | ✅ 200/run |
| Geocode (map lat/lng) | ✅ | ✅ | ✅ |

---

## 4. Logs

Cron output in files mein save hota hai:

```
storage/logs/treb-cron-light.log
storage/logs/treb-cron-standard.log
storage/logs/treb-cron-full.log
storage/logs/laravel.log
```

---

## 5. Manual commands (deploy / debug)

### 30-year historical bootstrap (local or AWS — one command, resumable)

`.ini` change ki zaroorat nahi`. Har run apna checkpoint save karti hai:

```bash
# Pehli dafa — 30 saal; har run ~4 min (PHP timeout safe), auto-checkpoint
php artisan serik:import-historical

# Wahi command dubara — checkpoint se auto-resume ( --resume optional)
php artisan serik:import-historical

# Geocode alag se (agar import ke baad 403 aaye)
php artisan serik:geocode --rounds=5

# Shuru se dubara
php artisan serik:import-historical --reset
```

Yeh command: har saal sold + listings import karti hai → geocode → purani listings detail update → address history → legacy backfill.

### Master cron (ek hi command se sab)

```bash
# Normal production cycle (4h wala)
php artisan serik:treb-cron --mode=standard

# Halka hourly cycle
php artisan serik:treb-cron --mode=light

# Raat wala full cycle
php artisan serik:treb-cron --mode=full
```

### Ek listing ke liye

```bash
php artisan serik:full-property-resync --listing=W13024458
php artisan serik:sync-address-history --listing=W13024458
php artisan serik:backfill-legacy --listing=W13024458
```

### Alag alag steps

```bash
php artisan serik:sync-properties          # Naye listings + sold + dates + geocode
php artisan serik:sync-sold --days=30    # Sold/leased import
php artisan serik:sync-dates --days=60   # Dates & status refresh
php artisan serik:geocode --rounds=5     # Missing map coordinates
php artisan serik:full-property-resync --active --days=7   # AMP detail refresh
php artisan serik:sync-address-history --limit=500         # History batch
php artisan serik:backfill-legacy --limit=200            # Fix old bad fields
```

---

## 6. Pehli dafa live deploy par

```bash
cd /path/to/serik

composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Pehla full import (time lagega)
php artisan serik:treb-cron --mode=full

# Ya step by step:
php artisan serik:sync-properties
php artisan serik:full-property-resync --active --chunk=300
```

Crontab line add karein (section 2).

---

## 7. Schedule verify karein

```bash
php artisan schedule:list
```

Test run:

```bash
php artisan serik:treb-cron --mode=light
```

---

## 8. Agar cron overlap / lock error aaye

```bash
php artisan serik:treb-cron --mode=standard --force
```

Ya cache clear:

```bash
php artisan cache:forget serik_treb_cron_lock
```

---

## 9. Summary — server par sirf yeh karna hai

1. `.env` mein `TRREB_AUTH` / `TRREB_AUTH1`
2. Crontab: `* * * * * php artisan schedule:run`
3. Pehli deploy par: `php artisan serik:treb-cron --mode=full`
4. Logs check: `storage/logs/treb-cron-*.log`

Is ke baad listings, sold data, property details, rooms, listing history, aur map coordinates automatically update hote rahenge.
