# Serik Realty — Laravel Octane Compatibility Report (Phase 1)

**Date:** local analysis before Windows RoadRunner integration  
**App:** Serik Realty (Botble Homzen)  
**Constraint:** production stability / zero data loss; no production `.env` / IIS / queue service changes in this phase

## Stack verified locally

| Item | Finding |
|------|---------|
| Laravel | **12.38.1** (`composer.lock`) — Octane `^2.23` supported |
| PHP (local XAMPP) | **8.2.x ZTS** — production target stated as **8.5.x**; Octane requires `^8.2` |
| Botble / Homzen | Theme + platform packages; long-running worker support is **not officially guaranteed** by Botble — treat as **compatible with care** (flush statics, recycle workers) |
| Queue workers | Separate **NSSM** services — Octane HTTP workers must **not** replace them |
| Search | Meilisearch via Scout — HTTP client per request; no Octane-specific conflict identified |
| Sessions / cache | `.env.example` defaults to `file`; production intended Redis/Memurai — prefer Redis under Octane |

## Octane server selection

| Server | Windows local | Windows IIS production | Verdict |
|--------|---------------|------------------------|---------|
| **FrankenPHP** | Possible but ships bundled PHP / different lifecycle; awkward behind IIS | Not a clean fit for “system PHP + IIS reverse proxy” | **Not selected** |
| **Swoole** | No first-class Windows support | Unsuitable | **Rejected** |
| **RoadRunner** | `rr.exe` + system PHP workers | IIS → `127.0.0.1:port` reverse proxy; NSSM can supervise `artisan octane:start` | **Selected** |

## Risk findings (application)

1. **`TrebPropertyHelper::$factRecordMemo`** — request-scoped fact records could leak across requests → **flush on request/operation end**.
2. **`ImageAlt::$mediaCache`** — MediaFile memoization → **flush**.
3. **`scheduleThinDetailHydrate` function-static `$scheduled`** — could suppress hydrates across requests → converted to clearable class static.
4. **Singletons** in `AppServiceProvider` (SiteMapManager, AdsManager, GeocodingManager) — typically app-wide config, not user session; Octane default listeners reset request auth/session.
5. **Debugbar / heavy local tooling** — disable on Octane production workers (`APP_DEBUG=false`, no Debugbar).
6. **`ext-sockets`** — required by RoadRunner Composer packages; enable on production PHP. Local XAMPP may crash Composer with sockets+opcache CLI — use `ops/windows/composer-with-ext.ps1`.
7. **Worker memory** — property detail pages are heavy; use **low worker counts**, **`max-requests` recycling**, and **`OCTANE_GARBAGE_MB`**.

## What Octane will not replace

- MySQL 8 schema / data  
- Meilisearch process  
- Existing NSSM **queue** workers  
- IIS public listeners on **80/443**

## Recommendation

Proceed with **Laravel Octane + RoadRunner**, bind **127.0.0.1 only**, put IIS in front, recycle workers aggressively until production memory profile is measured.
