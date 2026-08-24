# Serik Redis / Memurai (Windows)

## What runs on this machine

| Item | Local evidence (DESKTOP / Serik workstation) |
|------|-----------------------------------------------|
| Product | **MSOpenTech Redis 3.0.504** (not Memurai) |
| Service name | `Redis` |
| Binary | `C:\Program Files\Redis\redis-server.exe` |
| Conf | `C:\Program Files\Redis\redis.windows-service.conf` |
| Account | `NT AUTHORITY\NETWORK SERVICE` |
| Port | `6379` (listen `0.0.0.0` before harden → `127.0.0.1` after) |
| Data | `C:\Program Files\Redis\dump.rdb` |
| Log | `C:\Program Files\Redis\Logs\redis_log.txt` + Application Event Log (`syslog-ident=redis`) |

Production may still use **Memurai** — the same harden script auto-detects `Memurai` or `Redis`.

## Actual root cause (evidence)

**Not** “Laravel broke Redis.”

1. **System low virtual memory (Windows Event ID 2004)** — multiple times on 2026-08-19. Top consumers included `usocoreworker.exe` (~3.2–3.4 GB), Chrome (~0.8–2.4 GB), and `meilisearch.exe` (~748 MB) on an **~8 GB** machine (~88% RAM used when sampled).
2. **Redis service had empty failure recovery** (`sc qfailure` showed `RESET_PERIOD=0` and no restart actions). Any stop (reboot mid-pressure, manual stop, rare kill) left Redis **permanently down** until someone started it again — this is why the outage “kept coming back.”
3. **No Redis Application Error / WER crash dump** for `redis-server.exe` was found. Redis logs show successful BGSAVE only (no OOM/panic). Exact kill reason for a past stop is **not proven** as a Redis bug; the durable failure mode is: **host memory pressure + no auto-restart**.
4. Contributing config risk: default aggressive `save 300 10` / `save 60 10000` on Windows Redis (fork-heavy) with **no `maxmemory`**, on a machine that already hits Event 2004.

No Serik script/`net stop Redis` automation was found that stops Redis.

## Permanent fix

As **Administrator** (once per server):

```bat
scripts\windows\configure-serik-redis-service.cmd
php artisan serik:redis:status
```

This:

1. Sets `StartType=Automatic`
2. Sets recovery: restart after 5s / 15s / 30s (reset 1 day) — **safety net only**
3. Backs up and hardens conf:
   - `bind 127.0.0.1`
   - `maxmemory 256mb` + `maxmemory-policy volatile-lru` (cache/session TTL keys)
   - light RDB only: `save 900 1` (disables aggressive 300s/60s saves)
   - `stop-writes-on-bgsave-error no` (Windows BGSAVE under low RAM must not freeze cache)
   - `tcp-keepalive 60`
4. Restarts service and verifies TCP `127.0.0.1:6379`

Override memory: `set SERIK_REDIS_MAXMEMORY=512mb`

## Diagnose

```bat
php artisan serik:redis:status
php artisan serik:redis:status --json
```

## Laravel notes

- `QUEUE_CONNECTION=database` — jobs are **not** stored in Redis.
- Redis is for **cache / session / locks**.
- Boot timezone/locale uses **file** cache so Artisan does not hang if Redis is briefly down.
- Do **not** switch production `CACHE_STORE` to `file` to hide outages.

## Host capacity (honest remaining risk)

An 8 GB Windows host running Cursor/Chrome + Meilisearch + MySQL + PHP workers will keep hitting Event 2004. That can still disrupt Redis even after hardening. Mitigations outside Redis:

- Cap Meilisearch memory / avoid leaving huge Chrome sessions on the app server
- Prefer ≥16 GB RAM on the production IIS box
- Keep Redis bound to localhost and memory-capped as above
