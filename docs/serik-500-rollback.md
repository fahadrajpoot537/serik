# Serik HTTP 500 hardening — rollback

Use this if production worsens after the 500-hardening deploy. Restore the pre-deploy backup first; do not improvise new config on a failing site.

## 1. Stop making further code changes

Do not run `composer update`, migrations, Meilisearch rebuilds, or queue flushes.

## 2. Restore backed-up PHP/config

If you used the backup folder from `docs/serik-500-deployment.md`:

```powershell
$bak = "C:\backup\serik-500-TIMESTAMP"   # use the actual stamp
$root = "C:\project\serik"
Copy-Item "$bak\logging.php" "$root\config\logging.php" -Force
Copy-Item "$bak\serik.php" "$root\config\serik.php" -Force
Copy-Item "$bak\scout.php" "$root\config\scout.php" -Force
Copy-Item "$bak\app.php" "$root\bootstrap\app.php" -Force
# If you zipped app/bootstrap/config:
Expand-Archive "$bak\code-app-bootstrap-config.zip" -DestinationPath "$bak\unzipped" -Force
# Then copy the previous app/ files you changed back over $root
```

If you deployed from git, check out the previous known-good commit **only** for the files listed in the deployment doc (do not reset unrelated production-only files).

## 3. Restore `.env` only if you changed it

```powershell
Copy-Item "$bak\.env" "C:\project\serik\.env" -Force
```

If you did **not** change `.env`, skip this. Never leave `APP_DEBUG=true` on production.

## 4. Clear compiled Laravel config

```bat
cd /d C:\project\serik
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

If `config:cache` fails, stay on `optimize:clear` so Laravel reads PHP config files directly.

## 5. Recycle IIS once

```powershell
Restart-WebAppPool -Name "DefaultAppPool"
```

Do not restart MySQL, Memurai, Meilisearch, or the whole server as a rollback step.

## 6. Queue workers

If you restarted NSSM workers during deploy, restart them again after the restored files are in place so they load the previous classes:

- `SerikQueueLow`, `SerikQueueHigh`, `SerikQueueImages`, `SerikQueueImports`, `SerikQueueGhl`, others as installed

Do not delete `jobs` or `failed_jobs` rows.

## 7. Smoke test

```text
https://serik.ca/up
https://serik.ca/
```

`/health/live` and `/health/ready` will 404 after rollback if those routes did not exist before. That is expected.

## 8. Confirm logger error

If you rolled back to a `logging.php` **without** `search_sync`, the August 2026 error `Log [search_sync] is not defined` can return. Prefer rolling back application code while **keeping** the `search_sync` channel definition in `config/logging.php` if that file was the only confirmed fix you want to retain.

## 9. Disable the optional health monitor task

```powershell
Unregister-ScheduledTask -TaskName "SerikHealthMonitor" -Confirm:$false
```

Skip if you never registered it.
