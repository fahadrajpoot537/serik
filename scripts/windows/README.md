# Serik Windows Queue Workers

Reproducible NSSM deployment for Laravel `queue:work` services on Windows production.

## Services

| Service | Queue | Log files |
|---------|-------|-----------|
| `SerikQueueHigh` | `high` | `storage/logs/queue-high.log` |
| `SerikQueueImages` | `images` | `storage/logs/queue-images.log` |
| `SerikQueueLow` | `low` | `storage/logs/queue-low.log` |

`SerikMeilisearch` is separate — see `docs/SERIK_PRODUCTION.md`.

## Prerequisites

1. [NSSM](https://nssm.cc/download) installed (`nssm.exe` on PATH or at `C:\nssm\nssm.exe`)
2. PHP on PATH (or set `SERIK_PHP_EXE`)
3. Laravel app migrated and `.env` configured (`QUEUE_CONNECTION=database`, `SERIK_QUEUE_IMAGES=images`)

## Fresh server (all workers)

Run **as Administrator** from any directory:

```bat
cd C:\path\to\serik
scripts\windows\deploy-all-queue-workers.cmd
```

This installs or updates all three queue services, sets restart policy, starts them, runs `queue:restart`, and prints `serik:queue:status`.

## Images worker only

Required when code dispatches `PersistTrebImagesJob` to the `images` queue:

```bat
scripts\windows\install-serik-queue-images.cmd
```

Or:

```bat
php artisan serik:queue:install-images-worker
```

## Verify deployment

```bat
scripts\windows\verify-serik-queue-workers.cmd
```

Expected healthy state while images backlog exists:

- `sc query SerikQueueImages` → **RUNNING**
- `php artisan serik:queue:status` → **Image workers: 1**
- `images` queue → **Reserved > 0**, **Pending** decreasing

## Environment overrides

| Variable | Purpose |
|----------|---------|
| `SERIK_APP_ROOT` | Laravel project root (default: auto-detected from script location) |
| `SERIK_PHP_EXE` | Full path to `php.exe` |
| `SERIK_NSSM` | Full path to `nssm.exe` |

## After every deploy

```bat
php artisan queue:restart
scripts\windows\verify-serik-queue-workers.cmd
```

Workers finish the current job, exit, and NSSM restarts them with the new code.

## Troubleshooting

- **Image workers: 0** with pending images → run `install-serik-queue-images.cmd` as Administrator
- Check `storage/logs/queue-images-error.log`
- `php artisan serik:queue:status --json` for machine-readable state
