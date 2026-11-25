# Local Octane smoke notes (dev machine)

**Not production benchmarks.** Local XAMPP (PHP 8.2 ZTS, ~8 GB RAM, 2c/4t) differs from AWS Windows EC2 + IIS + PHP 8.5.

## Server under test

```text
php artisan serik:octane-start --host=127.0.0.1 --port=8001 --workers=2 --max-requests=100
```

RoadRunner `rr.exe` 3.0.1 · Laravel Octane v2.23.0

## Measured (after MySQL was started)

| Check | Result |
|-------|--------|
| `GET /up` | **200** (~100 ms warm) |
| `GET /` (homepage) | **200** (cold ~1.4 s; warm avg ~693 ms over 5 runs; min 418 / max 1390) |
| `GET /admin/login` | **200** (~457 ms) |
| `octane:status` | Server running |
| `GET /properties` | 500 (CMS `public.single` — local content/route issue, not worker crash) |
| City map listing URL from homepage | 500 on this DB snapshot (investigate separately; workers stayed up) |

No valid claim that Octane is faster than IIS FastCGI on production — **not compared on EC2**.

## Fixes required for Windows local boot

1. `serik:octane-start` (pcntl / Symfony signals)
2. `WindowsPosixExtension` for status/stop
3. `extension=sockets` enabled
4. Worker PHP: `-d opcache.enable_cli=0`
