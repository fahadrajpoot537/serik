# Laravel Octane — Production Deployment Guide (AWS Windows EC2 + IIS)

**Project:** Serik Realty  
**Octane server:** RoadRunner (`rr.exe`)  
**Public edge:** IIS (ports 80/443)  
**Octane bind:** `127.0.0.1` only (never public)  
**Queues:** existing NSSM queue workers — **leave unchanged**

This document is for **manual steps on the live/pre-prod Windows EC2** after you pull the updated code from GitHub.  
Local code changes are already in the repository; **do not** paste production secrets into Git.

Related: `docs/OCTANE_COMPATIBILITY_REPORT.md`

---

## LOCAL DEVELOPMENT vs LIVE SERVER

### LOCAL DEVELOPMENT (already implemented in code)

- `laravel/octane` + `spiral/roadrunner-http` + `spiral/roadrunner-cli` in `composer.json` / lock
- Published `config/octane.php` (RoadRunner default, GC, Serik flush listeners)
- `App\Listeners\FlushSerikStaticState`
- Static cache flush helpers on `ImageAlt` and `TrebPropertyHelper`
- `ops/windows/start-octane.ps1` and `ops/windows/composer-with-ext.ps1`
- `.env.example` Octane keys (template only)
- This deployment guide + live checklist

### LIVE SERVER MANUAL TASKS (you must do these)

Everything in sections **A–F** below, plus the checklist **WHAT I MUST DO ON LIVE SERVER**.

---

## A. Server prerequisites

### PHP

- PHP **8.2+** (your target: **8.5.x**). Must match what Composer packages expect.
- **Thread-safe vs NTS:** RoadRunner workers use CLI PHP. Prefer a stable **NTS** build for production CLI if you have choice; avoid known-buggy ZTS+opcache CLI combinations.
- Required / strongly recommended extensions:
  - `curl`, `openssl`, `mbstring`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`, `zip`, `intl`, `bcmath` (if used), **`sockets`** (RoadRunner packages), `redis` **or** use Predis (already in app)
- Confirm:

```powershell
php -v
php -m
php -i | Select-String -Pattern "opcache|sockets|pdo_mysql"
```

### Application binaries

- Project root must contain **`rr.exe`** (RoadRunner) for Windows after `php artisan octane:install --server=roadrunner` **or**:

```powershell
cd C:\path\to\serik   # your live app path
php .\vendor\bin\rr get-binary -n
# On Windows the binary is typically rr.exe. Ensure findable as rr / rr.exe in project root.
```

- Composer dependencies installed with `--no-dev` on production.

### Windows / IIS considerations

- Use **`php artisan serik:octane-start`** on Windows (not `octane:start`). Stock Octane start requires Symfony signal handling / `pcntl`.
- `App\Support\WindowsPosixExtension` rebinds Octane’s process inspector so `octane:status` / `octane:stop` work without `posix_kill`.
- Enable PHP **`extension=sockets`**. Keep **`opcache.enable_cli=0`** for Octane worker PHP on XAMPP ZTS builds (workers inherit `OCTANE_PHP_ARGS` / `-d opcache.enable_cli=0` via `serik:octane-start`).
- Prefer `php artisan octane:stop` or NSSM restart instead of relying on Ctrl+C.
- Octane does **not** replace IIS. IIS remains the public HTTPS terminator.
- Install **URL Rewrite** and **Application Request Routing (ARR)** for reverse proxy.
- Keep existing FastCGI site until Octane is verified, then switch the site (or a second binding) to proxy.
- **Do not** open the Octane port in AWS Security Groups or Windows Firewall for `0.0.0.0`.

### Meilisearch / MySQL / queues

- No changes required for Meilisearch or MySQL for Octane itself.
- **Do not** modify existing NSSM queue service definitions.

---

## B. Deployment commands (PowerShell)

Adjust `$AppRoot` to your real path (example only):

```powershell
$AppRoot = 'C:\inetpub\wwwroot\serik'   # CHANGE ME
cd $AppRoot
```

### 1) Backup before cutover

```powershell
Copy-Item .\.env .\.env.octane-backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')
# Optional: export IIS site config
# %windir%\system32\inetsrv\appcmd.exe list site "serik.ca" /config /xml > C:\backup\iis-serik-$(Get-Date -Format 'yyyyMMdd').xml
```

### 2) Pull code

```powershell
cd $AppRoot
git fetch origin
git pull origin <your-branch>   # e.g. main
```

### 3) Composer (production)

```powershell
composer install --no-dev --optimize-autoloader --no-interaction
# If ext-sockets is missing temporarily (fix preferred):
# composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-sockets
```

### 4) Ensure RoadRunner binary

```powershell
if (-not (Test-Path .\rr.exe) -and -not (Test-Path .\rr)) {
  php .\vendor\bin\rr get-binary -n
}
.\rr.exe --version
```

### 5) Octane config (already in repo — publish only if missing)

```powershell
if (-not (Test-Path .\config\octane.php)) {
  php artisan vendor:publish --provider="Laravel\Octane\OctaneServiceProvider" --no-interaction
}
```

### 6) `.env` keys (manual — do not overwrite whole file)

Add **only if missing** (keep existing DB/Redis/Meili/queue values):

```env
OCTANE_SERVER=roadrunner
OCTANE_HTTPS=true
OCTANE_GARBAGE_MB=64
OCTANE_MAX_EXECUTION_TIME=60
```

Prefer `SESSION_DRIVER=redis` and `CACHE_STORE=redis` under Octane (if Memurai already runs).

### 7) Laravel optimize (after env confirmed)

```powershell
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
# event:cache only if you already use it successfully in this app
```

**Maintenance mode:** only if you must stop FastCGI mid-cutover:

```powershell
php artisan down --retry=60
# ... switch IIS / start Octane ...
php artisan up
```

Often you can start Octane on `127.0.0.1:8001` **before** changing IIS, then flip the reverse proxy with near-zero downtime — then `down` may be unnecessary.

### 8) Start Octane (manual smoke before NSSM)

**Windows (required):** stock `octane:start` needs `pcntl` (unavailable). Use:

```powershell
php artisan serik:octane-start --host=127.0.0.1 --port=8001 --workers=4 --max-requests=250
```

**Worker sizing (start conservative, measure, then raise):**

| EC2 RAM (approx) | Suggested `--workers` | `--max-requests` |
|------------------|-----------------------|------------------|
| 8 GB | 2–4 | 200–250 |
| 16 GB | 4–8 | 250–500 |
| 32 GB+ | 8–16 | 500 |

Homzen + 160k+ listings payloads are memory-heavy; prefer **more recycle** over max workers.

### 9) Status / stop

```powershell
php artisan octane:status
php artisan octane:reload    # graceful worker recycle after deploy
php artisan octane:stop
```

Local helper (dev machines with XAMPP):

```powershell
.\ops\windows\start-octane.ps1 -Port 8001 -Workers 2 -MaxRequests 250
```

---

## C. IIS configuration (reverse proxy to Octane)

IIS does **not** reverse-proxy by default. Install:

1. **IIS URL Rewrite** module  
2. **Application Request Routing (ARR)** module  

Then enable proxy in ARR:

```powershell
# Run elevated
Import-Module WebAdministration
# ARR UI: IIS root → Application Request Routing Cache → Server Proxy Settings → Enable proxy = true
# Preserve host header = true (recommended)
```

### Reverse proxy rule (concept)

For site `serik.ca` (and similarly `pre-con.serik.ca` when ready):

- Match URL: `(.*)`
- Action: Rewrite to `http://127.0.0.1:8001/{R:1}`
- Stop processing of subsequent rules as appropriate

Example `web.config` fragment (**merge carefully** with existing Botble/Laravel `public/web.config` — do not blindly replace):

```xml
<rule name="ReverseProxyToOctane" stopProcessing="true">
  <match url="(.*)" />
  <conditions>
    <!-- Keep serving existing static files directly if desired -->
  </conditions>
  <action type="Rewrite" url="http://127.0.0.1:8001/{R:1}" />
</rule>
```

Also set server variables if needed for HTTPS awareness:

- `HTTP_X_FORWARDED_PROTO` = `https`
- Ensure Laravel `TrustProxies` / `APP_URL` / `OCTANE_HTTPS=true` match production.

### HTTPS / SSL

- Keep certificates on **IIS** (443). Octane speaks plain HTTP on localhost.
- Do not terminate TLS on RoadRunner for this architecture.

### WebSockets

- Homzen/Serik does not require Octane WebSockets for core MLS browsing.
- If you later need WS, ARR needs WebSocket protocol support enabled; out of scope for default cutover.

### Static assets

RoadRunner can serve `public/` static files. Alternatively keep IIS serving `/storage` and hashed assets and proxy only PHP routes — either works; pick one strategy and test cache headers.

---

## D. NSSM Windows Service (Octane only — separate from queues)

**Do not** change existing queue NSSM services.

Suggested service name: **`SerikOctane`**

### Install (elevated PowerShell)

```powershell
$Nssm = 'C:\nssm\nssm.exe'          # CHANGE to your nssm path
$AppRoot = 'C:\inetpub\wwwroot\serik'  # CHANGE
$Php = 'C:\php\php.exe'             # CHANGE to production PHP CLI

& $Nssm install SerikOctane $Php
& $Nssm set SerikOctane AppDirectory $AppRoot
& $Nssm set SerikOctane AppParameters "artisan serik:octane-start --host=127.0.0.1 --port=8001 --workers=4 --max-requests=250"
& $Nssm set SerikOctane Start SERVICE_AUTO_START
& $Nssm set SerikOctane AppStdout "$AppRoot\storage\logs\octane-nssm-stdout.log"
& $Nssm set SerikOctane AppStderr "$AppRoot\storage\logs\octane-nssm-stderr.log"
& $Nssm set SerikOctane AppRotateFiles 1
& $Nssm set SerikOctane AppRotateBytes 10485760
& $Nssm set SerikOctane AppExit Default Restart
& $Nssm set SerikOctane AppRestartDelay 5000
# Optional: ensure APP_ENV etc. inherited from system / .env — Laravel reads .env from AppDirectory
```

### Control

```powershell
nssm start SerikOctane
nssm stop SerikOctane
nssm restart SerikOctane
nssm status SerikOctane
Get-Content $AppRoot\storage\logs\octane-nssm-stderr.log -Tail 100
php artisan octane:status
```

After code deploy, prefer:

```powershell
php artisan octane:reload
# or
nssm restart SerikOctane
```

---

## E. Networking and security

| Item | Requirement |
|------|-------------|
| Octane listen | `127.0.0.1:8001` (or another **localhost** port) |
| IIS | 80 / 443 public |
| Windows Firewall | **Do not** allow inbound TCP 8001 from LAN/WAN |
| AWS Security Group | Only 80/443 (and admin RDP/SSH as you already allow) — **no** 8001 |
| RoadRunner RPC | Bound near Octane host (Octane sets RPC relative to HTTP port); keep localhost |

Verify nothing listens publicly:

```powershell
netstat -ano | findstr ":8001"
# Expect 127.0.0.1:8001 only — not 0.0.0.0:8001
```

If you ever see `0.0.0.0:8001`, stop the service and restart with `--host=127.0.0.1`.

---

## F. Deployment and rollback

### Safe deploy procedure

1. Backup `.env` and note current IIS rewrite rules.
2. `git pull` + `composer install --no-dev --optimize-autoloader`.
3. Confirm `rr.exe` present and `config/octane.php` present.
4. Add Octane `.env` keys if missing (**do not** clobber DB/Redis).
5. `php artisan optimize:clear` then `config:cache` / `route:cache` / `view:cache`.
6. Start or restart **SerikOctane** NSSM service (`octane:reload` if already running).
7. Smoke-test via `http://127.0.0.1:8001/` from the server, then via public HTTPS.
8. Monitor `storage/logs/laravel.log`, Octane NSSM logs, Task Manager memory for `php` / `rr` workers.
9. Leave queue NSSM services untouched; confirm jobs still process.

### Running requests during restart

- `octane:reload` recycles workers; in-flight requests on a worker finish or fail depending on timing — expect **brief** errors possible.
- `nssm restart` stops the master process — expect **short downtime** (seconds) unless you blue/green two ports (advanced).
- Minimize impact: deploy off-peak; use `reload` when only PHP code changed; keep IIS returning 502 briefly rather than corrupting data (Octane is request/response — DB transactions still commit per request).

### Rollback

1. Point IIS reverse proxy **back** to previous FastCGI/PHP handler (restore prior `web.config` / ARR rules).
2. `nssm stop SerikOctane` (optional — stop listening).
3. Restore previous Git revision if code is bad: `git checkout <previous-sha>` + `composer install --no-dev`.
4. Restore `.env` from backup only if you changed it.
5. `php artisan optimize:clear` && re-cache.
6. Confirm site on IIS FastCGI; queues still running.

Data loss risk from Octane rollback itself is **low** if you did not run destructive migrations (this guide does not).

---

## WHAT I MUST DO ON LIVE SERVER

Checklist (print / tick):

- [ ] Confirm PHP 8.2+ (preferably 8.5.x) CLI with `sockets` and MySQL extensions
- [ ] Backup `.env` and IIS site config
- [ ] `git pull` the Octane-enabled commit
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] Ensure `rr.exe` in app root (`vendor/bin/rr get-binary -n` if needed)
- [ ] Add `OCTANE_*` keys to production `.env` **without** overwriting other secrets
- [ ] Prefer Redis sessions/cache if Memurai is healthy
- [ ] Install/enable IIS **URL Rewrite** + **ARR** proxy
- [ ] Start Octane on `127.0.0.1:8001` and smoke-test locally on the EC2
- [ ] Create NSSM service **`SerikOctane`** (do **not** touch queue services)
- [ ] Flip IIS reverse proxy to `http://127.0.0.1:8001`
- [ ] Verify HTTPS site: home, listings, detail, search, admin login, queues, Meilisearch
- [ ] Confirm AWS SG + Windows Firewall do **not** expose 8001
- [ ] Monitor memory / logs for 24–48h; tune `--workers` and `--max-requests`
- [ ] Keep rollback plan ready (IIS → FastCGI)

---

## Pre-production (`pre-con.serik.ca`)

Repeat the same procedure on pre-prod **before** production `serik.ca`. Use a different localhost port if both apps share one EC2 (e.g. `8001` vs `8002`) and separate NSSM service names (`SerikOctanePrecon`).
