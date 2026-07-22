<?php

use Botble\RealEstate\Http\Controllers\API\PropertyController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Theme\homzen\Supports\TrebPropertyHelper;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| serik:import-amp-gaps — import every ListingKey AMP currently exposes that
| is missing from local MySQL. Walks Property ordered by ListingKey asc using
| cursor pagination (ListingKey gt 'X') because AMP rejects $skip+$top > 100000.
| Checkpoints last_key. Uses ingestListingFromAmp (geocode deferred).
|--------------------------------------------------------------------------
*/
Artisan::command('serik:import-amp-gaps
    {--resume : Continue from last ListingKey cursor}
    {--reset : Clear checkpoint}
    {--page=200 : AMP page size (25-500)}
    {--max-runtime=0 : Stop after N seconds (0 = until AMP exhausted)}
    {--dry-run : Count gaps only, do not ingest}', function () {
    @set_time_limit(0);
    @ini_set('memory_limit', '1024M');

    $controller = app(PropertyController::class);
    $stateKey = 'serik_amp_gaps_state';
    $lock = Cache::lock('serik_amp_gaps_lock', 7200);

    if (! $lock->get()) {
        $this->error('Another serik:import-amp-gaps is running.');

        return 1;
    }

    try {
        if ((bool) $this->option('reset')) {
            Cache::forget($stateKey);
            $this->info('Gap-import checkpoint cleared.');
        }

        $pageSize = max(25, min(500, (int) $this->option('page')));
        $maxRuntime = max(0, (int) $this->option('max-runtime'));
        $dryRun = (bool) $this->option('dry-run');
        $deadline = $maxRuntime > 0 ? microtime(true) + $maxRuntime : null;

        $state = [
            'last_key' => '',
            'scanned' => 0,
            'present' => 0,
            'missing' => 0,
            'imported' => 0,
            'failed' => 0,
            'done' => false,
        ];

        if ((bool) $this->option('resume')) {
            $prev = Cache::get($stateKey);
            if (is_array($prev)) {
                $state = array_merge($state, $prev);
                // Migrate old skip-based checkpoints to cursor start.
                if (($state['last_key'] ?? '') === '' && ! empty($prev['skip'])) {
                    $state['last_key'] = '';
                    $this->warn('Old $skip checkpoint ignored — AMP caps skip at 100k; restarting from ListingKey cursor.');
                }
                $this->warn('Resuming gap import after ListingKey=' . ($state['last_key'] ?: '(start)'));
            }
        }

        if (! TrebPropertyHelper::canFetchRemoteAmp()) {
            $this->error('AMP unavailable.');

            return 1;
        }

        $this->info('SERIK AMP gap import (ListingKey cursor, no $skip)');
        $this->line('page='.$pageSize.' dry='.($dryRun ? 'yes' : 'no'));

        $base = 'https://query.ampre.ca/odata/Property';

        while (true) {
            if ($deadline !== null && microtime(true) >= $deadline) {
                Cache::put($stateKey, $state, 86400 * 14);
                $this->warn('Max runtime — re-run with --resume.');
                break;
            }

            // Cursor pagination: AMP forbids $skip+$top > 100000 (error 1108).
            $filter = ($state['last_key'] ?? '') !== ''
                ? "&\$filter=ListingKey gt '" . str_replace("'", "''", $state['last_key']) . "'"
                : '';
            $url = $base
                .'?$orderby=ListingKey%20asc'
                .'&$top='.$pageSize
                .$filter
                .'&$select=ListingKey';

            $payload = TrebPropertyHelper::ampGetFresh($url, 30, 3, 'live');
            $rows = is_array($payload) ? ($payload['value'] ?? []) : [];

            if ($rows === []) {
                $state['done'] = true;
                Cache::put($stateKey, $state, 86400 * 14);
                $this->info('AMP exhausted — gap import complete.');
                break;
            }

            $keys = [];
            foreach ($rows as $row) {
                $k = strtoupper(trim((string) ($row['ListingKey'] ?? '')));
                if ($k !== '') {
                    $keys[] = $k;
                }
            }

            $have = array_flip(
                DB::table('re_properties')->whereIn('external_id', $keys)->pluck('external_id')->all()
            );

            foreach ($keys as $key) {
                $state['scanned']++;
                $state['last_key'] = $key;
                if (isset($have[$key])) {
                    $state['present']++;
                    continue;
                }

                $state['missing']++;

                if ($dryRun) {
                    continue;
                }

                try {
                    // Skip inline geocode in bulk gap fill — Nominatim is shared
                    // with serik:geocode-all. Skip Scout per-row index during scan
                    // (run serik:search-index later) and history recorder to cut
                    // mysqld write amplification under RAM pressure.
                    $prevHistory = \Botble\RealEstate\Supports\PropertyHistoryRecorder::$enabled;
                    \Botble\RealEstate\Supports\PropertyHistoryRecorder::$enabled = false;
                    try {
                        $prop = $controller->ingestListingFromAmp($key, false, false);
                    } finally {
                        \Botble\RealEstate\Supports\PropertyHistoryRecorder::$enabled = $prevHistory;
                    }
                    if ($prop) {
                        $state['imported']++;
                    } else {
                        $state['failed']++;
                    }
                } catch (\Throwable $e) {
                    $state['failed']++;
                    \Log::warning('import-amp-gaps failed: '.$e->getMessage(), ['key' => $key]);
                }
            }

            Cache::put($stateKey, $state, 86400 * 14);

            $this->line(sprintf(
                'after=%s scanned=%d present=%d missing=%d imported=%d failed=%d',
                $state['last_key'],
                $state['scanned'],
                $state['present'],
                $state['missing'],
                $state['imported'],
                $state['failed']
            ));

            if (count($rows) < $pageSize) {
                $state['done'] = true;
                Cache::put($stateKey, $state, 86400 * 14);
                $this->info('AMP last page reached — gap import complete.');
                break;
            }
        }

        $this->newLine();
        $this->table(['Metric', 'Value'], [
            ['Scanned', $state['scanned']],
            ['Last ListingKey', $state['last_key'] ?? ''],
            ['Already local', $state['present']],
            ['Gaps found', $state['missing']],
            ['Imported', $state['imported']],
            ['Failed', $state['failed']],
            ['Done', ! empty($state['done']) ? 'yes' : 'no'],
        ]);

        return 0;
    } finally {
        optional($lock)->release();
    }
})->purpose('Import every AMP Property ListingKey missing from local MySQL');

Artisan::command('serik:sync-sold {--days=30 : Days of sold history to sync}', function () {
    $days = max(1, min(120, (int) $this->option('days')));
    $this->info("Syncing sold/leased listings (last {$days} days)...");

    $controller = app(PropertyController::class);
    $result = $controller->syncRecentSoldListings(Request::create('/', 'GET', ['days' => $days]));
    $body = json_decode($result->getContent(), true) ?: [];

    $this->line(json_encode($body, JSON_PRETTY_PRINT));

    $this->info('Done.');
})->purpose('Sync recent sold & leased listings from AMP and geocode them');

Artisan::command('serik:geocode {--rounds=5 : Max geocode batches} {--listing= : Geocode a single MLS/external id}', function () {
    $controller = app(PropertyController::class);
    $rounds = max(1, min(20, (int) $this->option('rounds')));
    $listing = strtoupper(trim((string) $this->option('listing')));
    $total = 0;

    // Targeted mode: geocode just one listing by its MLS/external id.
    if ($listing !== '') {
        $id = DB::table('re_properties')->where('external_id', $listing)->value('id');

        if (! $id) {
            $this->error("Listing {$listing} not found.");

            return;
        }

        $body = json_decode($controller->geocode([(int) $id])->getContent(), true) ?: [];

        if (! empty($body['error'])) {
            $this->error('Geocoder error: ' . ($body['details'] ?? $body['error']));

            return;
        }

        $this->info("{$listing}: processed=" . (int) ($body['processed'] ?? 0) . ", geocoded=" . (int) ($body['geocoded'] ?? 0));

        return;
    }

    for ($i = 1; $i <= $rounds; $i++) {
        $result = $controller->geocode();
        $body = json_decode($result->getContent(), true) ?: [];
        $geocoded = (int) ($body['geocoded'] ?? 0);
        $processed = (int) ($body['processed'] ?? 0);

        $this->line("Batch {$i}: processed={$processed}, geocoded={$geocoded}");

        if (! empty($body['error'])) {
            $this->error('Geocoder error: ' . ($body['details'] ?? $body['error']));
            break;
        }

        $total += $geocoded;

        if ($geocoded === 0) {
            break;
        }
    }

    $this->info("Geocoded {$total} properties total.");
})->purpose('Geocode properties missing latitude/longitude');

/*
|--------------------------------------------------------------------------
| serik:geocode-all — resumable, run-to-completion geocoder with ETA
|--------------------------------------------------------------------------
| Only touches rows with missing/zero coordinates, so it never geocodes the
| same property twice and is safe to run repeatedly. Nominatim is ~1 req/sec,
| so this streams through the backlog and checkpoints implicitly (the
| "missing coords" query IS the checkpoint). --fix-invalid repairs coordinates
| that fall outside Ontario before filling the remaining gaps.
*/
Artisan::command('serik:geocode-all
    {--batch=150 : Rows per round}
    {--max-runtime=0 : Stop after N seconds (0 = until backlog empty)}
    {--fix-invalid : Reset out-of-Ontario coordinates to 0 so they are re-geocoded}
    {--force-lock : Release a stuck serik:geocode:bulk cache lock before starting}
    {--active-only : Only active For Sale / For Lease (skip sold/historical backlog)}
    {--days=0 : With --active-only, only listings with listing_contract_date in last N days (0 = all active)}', function () {
    @set_time_limit(0);
    @ini_set('max_execution_time', '0');
    @ini_set('memory_limit', '1024M');

    try {
    $controller = app(PropertyController::class);
    $batch = max(10, min(500, (int) $this->option('batch')));
    $maxRuntime = max(0, (int) $this->option('max-runtime'));
    $deadline = $maxRuntime > 0 ? microtime(true) + $maxRuntime : null;
    $startedAt = microtime(true);
    $activeOnly = (bool) $this->option('active-only');
    $activeDays = max(0, (int) $this->option('days'));

    if ((bool) $this->option('force-lock')) {
        try {
            Cache::lock('serik:geocode:bulk')->forceRelease();
            $this->warn('Forced release of serik:geocode:bulk lock.');
        } catch (\Throwable $e) {
            $this->warn('Could not force-release geocode lock: ' . $e->getMessage());
        }
    }

    $activeStatuses = ['New', 'Price Change', 'Extension', 'Ext', 'Previous Status', 'Active'];

    // latitude/longitude are stored as 0 (not NULL) when missing. A single
    // equality on latitude uses a tight range and finishes in ms; the old
    // OR-null/OR-zero form scanned tens of thousands of rows per round.
    // "Due" backlog = rows missing coords that are NOT quarantined or waiting
    // out an exponential-backoff window in re_geocode_queue. This is the number
    // the geocoder can actually act on right now, so ETA stays truthful and the
    // loop terminates instead of spinning on permanently-unresolvable rows.
    $missing = function () use ($activeOnly, $activeDays, $activeStatuses) {
        $q = DB::table('re_properties')
            ->where('latitude', 0)
            ->where(function ($w) {
                $w->where('location', '!=', '')->orWhere('name', '!=', '');
            });

        if ($activeOnly) {
            $q->whereIn('MlsStatus', $activeStatuses)
                ->whereIn('TransactionType', ['For Sale', 'For Lease']);
            if ($activeDays > 0) {
                $q->where('listing_contract_date', '>=', now()->subDays($activeDays)->toDateString());
            }
        }

        if (Schema::hasTable('re_geocode_queue')) {
            $q->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('re_geocode_queue')
                    ->whereColumn('re_geocode_queue.property_id', 're_properties.id')
                    ->where(function ($w) {
                        $w->where('permanent_fail', 1)
                            ->orWhere('next_attempt_at', '>', now());
                    });
            });
        }

        return (int) $q->count();
    };

    if ((bool) $this->option('fix-invalid')) {
        $reset = DB::table('re_properties')
            ->whereNotNull('latitude')->where('latitude', '!=', 0)
            ->where(function ($q) {
                $q->where('latitude', '<', 41.6)->orWhere('latitude', '>', 57.0)
                    ->orWhere('longitude', '>', -74.0)->orWhere('longitude', '<', -95.2);
            })
            ->update(['latitude' => 0, 'longitude' => 0]);
        $this->warn("Reset {$reset} out-of-Ontario coordinate rows for re-geocoding.");
    }

    $startBacklog = $missing();
    $this->info('SERIK geocode-all');
    if ($activeOnly) {
        $this->line('Mode: ACTIVE only' . ($activeDays > 0 ? " (last {$activeDays} days)" : ' (all active)'));
    }
    $this->line("Backlog (missing valid coords): {$startBacklog}");
    if ($startBacklog === 0) {
        $this->info('Nothing to geocode. All due properties have valid coordinates (or are quarantined).');

        return 0;
    }

    $geoOpts = [
        'active_only' => $activeOnly,
        'days' => $activeDays,
    ];

    $totalGeocoded = 0;
    $round = 0;
    $emptyRounds = 0;
    while (true) {
        if ($deadline !== null && microtime(true) >= $deadline) {
            $this->warn('Max runtime reached — re-run to continue (fully resumable).');
            break;
        }

        $round++;
        $res = $controller->geocode(null, $batch, $geoOpts);
        $body = json_decode($res->getContent(), true) ?: [];

        if (! empty($body['error'])) {
            $this->error('Geocoder error: ' . ($body['details'] ?? $body['error']) . ' — pausing 60s.');
            sleep(60);
            continue;
        }

        if (! empty($body['locked']) || ! empty($body['skipped'])) {
            $this->warn('Bulk geocode lock is held by another process. Waiting 30s… (or re-run with --force-lock)');
            sleep(30);
            $emptyRounds++;
            if ($emptyRounds >= 5) {
                $this->error('Lock still held after retries. Stop other geocode jobs or use --force-lock.');
                break;
            }
            continue;
        }

        $done = (int) ($body['geocoded'] ?? 0);
        $processed = (int) ($body['processed'] ?? 0);
        $borrowed = (int) ($body['borrowed'] ?? 0);
        $totalGeocoded += $done;

        $elapsed = microtime(true) - $startedAt;
        $rate = $totalGeocoded > 0 ? $totalGeocoded / $elapsed : 0; // per second
        $remaining = $missing();
        $eta = $rate > 0 ? $remaining / $rate : 0;
        $etaStr = $eta > 0 ? sprintf('%02d:%02d:%02d', intdiv((int) $eta, 3600), intdiv((int) $eta % 3600, 60), (int) $eta % 60) : 'n/a';

        $this->line("Round {$round}: geocoded={$done} (borrowed={$borrowed}) processed={$processed} | total={$totalGeocoded} | remaining={$remaining} | ETA={$etaStr}");

        if ($remaining === 0) {
            $this->info('Geocoding backlog cleared.');
            break;
        }

        if ($processed === 0) {
            $emptyRounds++;
            $this->warn("Selector returned 0 rows while backlog={$remaining}. Pausing 15s…");
            sleep(15);
            if ($emptyRounds >= 3) {
                $this->error('Stopping: geocoder cannot select due rows (check re_geocode_queue / Nominatim). Backlog NOT cleared.');
                break;
            }
            continue;
        }

        $emptyRounds = 0;
    }

    $this->newLine();
    $this->table(['Metric', 'Value'], [
        ['Start backlog', $startBacklog],
        ['Geocoded this run', $totalGeocoded],
        ['Remaining', $missing()],
        ['Runtime (s)', round(microtime(true) - $startedAt, 1)],
    ]);

    return 0;
    } catch (\Throwable $e) {
        \Log::error('[serik:geocode-all] ' . $e->getMessage());
        $this->error('geocode-all error: ' . $e->getMessage());

        // Never fail the scheduler / Task Scheduler (0xFF).
        return 0;
    }
})->purpose('Resumable geocoder: fills every missing coordinate with ETA (safe to re-run)');

/*
|--------------------------------------------------------------------------
| serik:test-mail — diagnose why registration PIN emails are not arriving
|--------------------------------------------------------------------------
*/
Artisan::command('serik:test-mail {email? : Destination address} {--resend : Force Resend API driver} {--from= : Override From address (must be on verified domain)}', function () {
    $to = trim((string) ($this->argument('email') ?: setting('email_from_address') ?: 'info@serik.ca'));
    $fromOverride = trim((string) $this->option('from'));

    if ((bool) $this->option('resend')) {
        $key = setting('email_resend_key') ?: env('RESEND_API_KEY');
        if (! $key) {
            $this->error('No Resend key. Set Admin → Settings → Email → Resend API Key, or RESEND_API_KEY in .env');

            return 1;
        }

        // Prefer verified-domain From — sandbox cannot deliver to arbitrary Gmail.
        $from = $fromOverride
            ?: (string) env('MAIL_FROM_ADDRESS', '')
            ?: (string) setting('email_from_address', 'info@serik.ca');

        if (str_ends_with(strtolower($from), '@resend.dev')) {
            $from = 'info@serik.ca';
            $this->warn('Refusing onboarding@resend.dev for Gmail recipients — using info@serik.ca');
        }

        setting()->set([
            'email_driver' => 'resend',
            'email_resend_key' => $key,
            'email_from_address' => $from,
            'email_from_name' => setting('email_from_name') ?: env('MAIL_FROM_NAME', 'Serik Realty'),
            'plugins_real-estate_account-registered_status' => '1',
        ])->save();

        $this->warn("Switched email_driver=resend, from={$from}");
    }

    // Refresh mail config from settings (same path as HTTP boot).
    app()->forgetInstance(\Illuminate\Mail\MailManager::class);
    app()->forgetInstance('mail.manager');
    config(['mail.default' => setting('email_driver', config('mail.default'))]);
    config(['mail.from.address' => setting('email_from_address', config('mail.from.address'))]);
    config(['mail.from.name' => setting('email_from_name', config('mail.from.name'))]);

    $driver = setting('email_driver', config('mail.default'));
    $fromNow = (string) setting('email_from_address');

    $this->table(['Key', 'Value'], [
        ['driver', $driver],
        ['from', $fromNow],
        ['to', $to],
        ['resend_key', substr((string) setting('email_resend_key'), 0, 10) . '…'],
        ['config mail.default', config('mail.default')],
        ['config mail.from', config('mail.from.address')],
    ]);

    if ($driver === 'resend' && str_ends_with(strtolower($fromNow), '@resend.dev')) {
        $this->error('BLOCKED: from is still @resend.dev');
        $this->line('Resend sandbox only delivers to YOUR Resend signup email — not fahadrajpoot537@gmail.com.');
        $this->line('Fix:');
        $this->line('  1) https://resend.com/domains → serik.ca must be Verified');
        $this->line('  2) Admin → Email → From = info@serik.ca');
        $this->line('  3) .env: MAIL_FROM_ADDRESS=info@serik.ca  RESEND_FORCE_SANDBOX_FROM=false');
        $this->line('  4) php artisan serik:test-mail ' . $to . ' --resend --from=info@serik.ca');

        return 1;
    }

    if ($driver === 'log') {
        $this->error('email_driver is still "log" — emails only write to laravel.log. Set SMTP or Resend.');

        return 1;
    }

    try {
        app()->forgetInstance(\Illuminate\Mail\MailManager::class);
        app()->forgetInstance('mail.manager');

        if ($driver === 'resend') {
            config([
                'mail.default' => 'resend',
                'mail.from.address' => $fromNow,
                'mail.from.name' => setting('email_from_name', 'Serik Realty'),
                'services.resend.key' => setting('email_resend_key') ?: env('RESEND_API_KEY'),
            ]);
        } elseif ($driver === 'smtp') {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => setting('email_host'),
                'mail.mailers.smtp.port' => (int) setting('email_port', 25),
                'mail.mailers.smtp.username' => setting('email_username'),
                'mail.mailers.smtp.password' => setting('email_password'),
                'mail.mailers.smtp.encryption' => setting('email_encryption') ?: null,
            ]);
        }

        $ok = \Botble\Base\Facades\EmailHandler::setModule(REAL_ESTATE_MODULE_SCREEN_NAME)
            ->setVariableValues([
                'account_name' => 'Mail Test',
                'account_email' => $to,
                'account_password' => '999999',
            ])
            ->sendUsingTemplate('account-registered', $to, [], true);

        if ($ok) {
            $this->info("Send accepted for {$to} from {$fromNow}.");
            $this->warn('Open https://resend.com/emails — if status is Failed/403, domain is not verified or From domain mismatch.');

            return 0;
        }

        $this->error('sendUsingTemplate returned false (template disabled?).');

        return 1;
    } catch (\Throwable $e) {
        $this->error('SEND FAILED: ' . $e->getMessage());
        $this->line($e->getFile() . ':' . $e->getLine());

        $msg = strtolower($e->getMessage());
        if (str_contains($msg, 'resend.dev') || str_contains($msg, 'only send testing') || str_contains($msg, 'verify a domain')) {
            $this->newLine();
            $this->error('Resend rejected: sandbox From cannot send to this Gmail.');
            $this->line('Verify serik.ca at https://resend.com/domains then:');
            $this->line('  php artisan serik:test-mail ' . $to . ' --resend --from=info@serik.ca');
        }

        return 1;
    }
})->purpose('Send a test registration PIN email and print SMTP/Resend errors');

/*
|--------------------------------------------------------------------------
| serik:geocode-borrow — Nominatim-free sibling coordinate copy
|--------------------------------------------------------------------------
| Copies lat/lng from another unit on the same street. Clears condo buildings
| in seconds so "Last 1/3/7 days" map filters fill without waiting on OSM.
*/
Artisan::command('serik:geocode-borrow
    {--limit=2000 : Max rows to attempt}
    {--active-days=30 : Prefer active listings listed within N days (0 = all active)}', function () {
    @set_time_limit(0);
    $limit = max(50, min(10000, (int) $this->option('limit')));
    $activeDays = max(0, (int) $this->option('active-days'));
    $activeStatuses = ['New', 'Price Change', 'Extension', 'Ext', 'Previous Status'];

    $query = \Botble\RealEstate\Models\Property::query()
        ->where('latitude', 0)
        ->whereIn('MlsStatus', $activeStatuses)
        ->where(function ($w) {
            $w->where(function ($q2) {
                $q2->whereNotNull('location')->where('location', '!=', '');
            })->orWhere(function ($q2) {
                $q2->whereNotNull('name')->where('name', '!=', '');
            });
        })
        ->orderByDesc('listing_contract_date')
        ->orderByDesc('id')
        ->limit($limit)
        ->select(['id', 'external_id', 'name', 'location', 'zip_code', 'latitude', 'longitude', 'MlsStatus', 'TransactionType']);

    if ($activeDays > 0) {
        $query->where('listing_contract_date', '>=', now()->subDays($activeDays)->toDateString());
    }

    $properties = $query->get();
    if ($properties->isEmpty()) {
        $this->info('Nothing to borrow-geocode.');

        return 0;
    }

    $controller = app(PropertyController::class);
    $ref = new ReflectionClass($controller);
    $borrow = $ref->getMethod('borrowCoordsFromSibling');
    $borrow->setAccessible(true);
    $sync = $ref->getMethod('syncPropertyToSearchIndex');
    $sync->setAccessible(true);
    $clearFail = $ref->getMethod('clearGeocodeFailure');
    $clearFail->setAccessible(true);

    $previousQueue = config('scout.queue');
    config(['scout.queue' => false]);

    $geocoded = 0;
    $failed = 0;
    foreach ($properties as $property) {
        $coords = $borrow->invoke($controller, $property);
        if ($coords === null) {
            $failed++;
            continue;
        }

        $property->update([
            'latitude' => $coords['lat'],
            'longitude' => $coords['lng'],
        ]);
        $clearFail->invoke($controller, (int) $property->id);
        $sync->invoke($controller, $property);
        $geocoded++;
    }

    config(['scout.queue' => $previousQueue]);
    $this->info("Borrow geocode: tried={$properties->count()} geocoded={$geocoded} no_sibling={$failed}");

    return 0;
})->purpose('Fast Nominatim-free geocode via sibling building coordinates');

/*
|--------------------------------------------------------------------------
| serik:search-index-recent — keep Meili warm for map date filters
|--------------------------------------------------------------------------
*/
Artisan::command('serik:search-index-recent
    {--days=3 : Listing contract lookback}
    {--limit=3000 : Max rows to reindex}', function () {
    @set_time_limit(0);
    $days = max(1, min(30, (int) $this->option('days')));
    $limit = max(100, min(20000, (int) $this->option('limit')));
    $previousQueue = config('scout.queue');
    config(['scout.queue' => false]);

    $cutoff = now()->subDays($days)->toDateString();
    $ids = DB::table('re_properties')
        ->where('moderation_status', 'approved')
        ->where('listing_contract_date', '>=', $cutoff)
        ->where('latitude', '!=', 0)
        ->where('longitude', '!=', 0)
        ->orderByDesc('listing_contract_date')
        ->limit($limit)
        ->pluck('id')
        ->all();

    if ($ids === []) {
        config(['scout.queue' => $previousQueue]);
        $this->info('No recent geocoded listings to index.');

        return 0;
    }

    $done = 0;
    \Botble\RealEstate\Models\Property::query()
        ->whereIn('id', $ids)
        ->orderBy('id')
        ->chunkById(200, function ($rows) use (&$done) {
            $rows->searchable();
            $done += $rows->count();
        });

    config(['scout.queue' => $previousQueue]);
    $this->info("Indexed {$done} recent geocoded listings into Meilisearch.");

    return 0;
})->purpose('Re-index recent geocoded listings into Meilisearch (sync)');

Artisan::command('serik:sync-properties', function () {
    $this->info('Running full AMP property sync...');
    $controller = app(PropertyController::class);

    $this->line('--- addpropertiescron ---');
    $cron = $controller->addpropertiescron();
    $this->line($cron->getContent());

    $this->line('--- sync-recent-sold (30 days) ---');
    $sold = $controller->syncRecentSoldListings(Request::create('/', 'GET', ['days' => 30]));
    $this->line($sold->getContent());

    $this->line('--- sync-amp-listing-dates ---');
    $dates = $controller->syncAmpListingDates(Request::create('/', 'GET', ['days' => 60]));
    $this->line($dates->getContent());

    $this->line('--- geocode remaining ---');
    $rounds = 0;
    for ($i = 1; $i <= 5; $i++) {
        $geo = $controller->geocode();
        $body = json_decode($geo->getContent(), true) ?: [];
        $geocoded = (int) ($body['geocoded'] ?? 0);
        $this->line("geocode batch {$i}: " . $geo->getContent());
        $rounds += $geocoded;
        if ($geocoded === 0) {
            break;
        }
    }

    Cache::forget('map_v17_*');
    $this->info("Full sync complete. Extra geocoded: {$rounds}");
})->purpose('Full sync: cron import, sold sync, dates, geocode');

Artisan::command('serik:sync-dates {--days=60 : Days of AMP modifications to refresh}', function () {
    $days = max(1, min(360, (int) $this->option('days')));
    $controller = app(PropertyController::class);
    $result = $controller->syncAmpListingDates(Request::create('/', 'GET', ['days' => $days]));
    $this->line($result->getContent());
    $this->info('Listing dates synced.');
})->purpose('Refresh listing_contract_date, listing_modified_at, close_date, MlsStatus from AMP');

Artisan::command('serik:import-recent {--days=3 : Import listings modified in the last X days} {--pages=6 : Max AMP pages (2000/page)}', function () {
    $days = max(1, min(30, (int) $this->option('days')));
    $pages = max(1, min(20, (int) $this->option('pages')));

    $this->info("Importing site-wide listings modified in the last {$days} day(s), newest first...");

    $controller = app(PropertyController::class);
    $result = $controller->importRecentModifiedAmpListings($days, $pages);
    $this->line($result->getContent());

    $this->info('Done.');
})->purpose('Fast site-wide import of newly listed/updated AMP listings (newest first)');

Artisan::command('serik:sync-now', function () {
    $this->call('serik:sync-dates', ['--days' => 60]);
    $this->call('serik:sync-sold', ['--days' => 30]);
    $this->call('serik:geocode', ['--rounds' => 8]);
    $this->info('Quick sync done. Clear browser cache or run clear-serik-cache.php if map still stale.');
})->purpose('Quick sync sold + geocode for map (run manually or via scheduler)');

Artisan::command('serik:backfill-legacy {--limit=200 : Max properties to refresh per run} {--listing= : Process a single listing key} {--dry-run : Report only, do not write}', function () {
    $limit = max(1, min(2000, (int) $this->option('limit')));
    $dryRun = (bool) $this->option('dry-run');
    $singleListing = strtoupper(trim((string) $this->option('listing')));

    $this->info('Refreshing stale listing fields from AMP and local corrections...');

    $query = DB::table('re_properties')
        ->where('moderation_status', 'approved')
        ->whereNotNull('external_id')
        ->where('external_id', '!=', '');

    if ($singleListing !== '') {
        $query->where('external_id', $singleListing);
    } else {
        $query->where(function ($q) {
            $q->whereNull('CoveredSpaces')
                ->orWhere('number_floor', '<=', 1)
                ->orWhere('updated_at', '<', now()->subDays(14))
                ->orWhereRaw('BedroomsBelowGrade > 0 AND number_bedroom > BedroomsBelowGrade');
        });
    }

    $candidates = $query
        ->orderBy('updated_at')
        ->limit($singleListing !== '' ? 1 : $limit)
        ->get([
            'id',
            'external_id',
            'price',
            'CoveredSpaces',
            'ParkingSpaces',
            'number_bedroom',
            'BedroomsBelowGrade',
            'number_floor',
            'Basement',
            'updated_at',
        ]);

    $totalFound = $candidates->count();
    $this->line("Candidates selected: {$totalFound} (limit={$limit})");

    if ($totalFound === 0) {
        $this->warn('No legacy candidates matched the selection query.');
        $this->line('Query filters: moderation_status=approved, external_id present, and one of:');
        $this->line('  - CoveredSpaces IS NULL');
        $this->line('  - number_floor <= 1');
        $this->line('  - updated_at older than 14 days');
        $this->line('  - number_bedroom > BedroomsBelowGrade (legacy total-bed storage)');

        return;
    }

    $processed = 0;
    $updated = 0;
    $skipped = 0;
    $ampMissing = 0;
    $previewLimit = 10;

    foreach ($candidates as $property) {
        $processed++;
        $listingKey = strtoupper((string) $property->external_id);
        $ampItem = TrebPropertyHelper::fetchAmpBackfillRecord($listingKey);
        $ampStatus = is_array($ampItem) ? 'amp_hit' : 'amp_missing';

        if (! is_array($ampItem)) {
            $ampMissing++;
        }

        $changes = TrebPropertyHelper::buildLegacyBackfillChanges($property, $ampItem);

        if ($changes === []) {
            $skipped++;
            if ($processed <= $previewLimit || $singleListing !== '') {
                $this->line("[{$listingKey}] skipped ({$ampStatus}) — no changes needed");
            }
            continue;
        }

        $this->line("[{$listingKey}] {$ampStatus} update=" . json_encode($changes));

        if (! $dryRun) {
            DB::table('re_properties')->where('id', $property->id)->update($changes);
        }

        $updated++;
    }

    $this->newLine();
    $this->info("Legacy backfill complete.");
    $this->table(
        ['Metric', 'Count'],
        [
            ['found', $totalFound],
            ['processed', $processed],
            ['updated', $updated],
            ['skipped_no_changes', $skipped],
            ['amp_missing', $ampMissing],
            ['dry_run', $dryRun ? 'yes' : 'no'],
        ]
    );
})->purpose('Backfill incorrect legacy property fields from AMP without duplicating listings');

$registerFullPropertyResync = function (string $commandName) {
    Artisan::command(
        $commandName
            . ' {--chunk=300 : Records per chunk}'
            . ' {--listing= : Sync a single ListingKey}'
            . ' {--active : Only active/selling listings}'
            . ' {--days= : Only listings with listing_modified_at/updated_at in the last X days}'
            . ' {--resume : Resume from last interrupted run}'
            . ' {--dry-run : Report changes without writing}'
            . ' {--delay=150 : Milliseconds between AMP requests}'
            . ' {--limit=0 : Max rows this run (0 = all matched)}'
            . ' {--max-runtime=0 : Stop after N seconds (0 = no limit)}',
        function () {
            $chunkSize = max(50, min(500, (int) $this->option('chunk')));
            $singleListing = strtoupper(trim((string) $this->option('listing')));
            $activeOnly = (bool) $this->option('active');
            $days = $this->option('days') !== null ? max(1, (int) $this->option('days')) : null;
            $resume = (bool) $this->option('resume');
            $dryRun = (bool) $this->option('dry-run');
            $delayMs = max(0, min(2000, (int) $this->option('delay')));
            $limit = max(0, (int) $this->option('limit'));
            $maxRuntime = max(0, (int) $this->option('max-runtime'));
            $deadline = $maxRuntime > 0 ? microtime(true) + $maxRuntime : null;
            $stateKey = 'serik_full_property_resync_state';

            $stats = [
                'processed' => 0,
                'updated' => 0,
                'skipped' => 0,
                'amp_missing' => 0,
                'failed' => 0,
            ];

            $lastId = 0;
            $startedAt = microtime(true);

            if ($resume) {
                $saved = Cache::get($stateKey);

                if (is_array($saved)) {
                    $lastId = (int) ($saved['last_id'] ?? 0);
                    foreach (['processed', 'updated', 'skipped', 'amp_missing', 'failed'] as $metric) {
                        $stats[$metric] = (int) ($saved[$metric] ?? 0);
                    }
                    $startedAt = (float) ($saved['started_at'] ?? $startedAt);
                    $this->info("Resuming after property id > {$lastId}");
                }
            } else {
                Cache::forget($stateKey);
            }

            $query = DB::table('re_properties')
                ->whereNotNull('external_id')
                ->where('external_id', '!=', '')
                ->orderBy('id');

            if ($singleListing !== '') {
                $query->where('external_id', $singleListing);
            }

            if ($activeOnly) {
                $query->where(function ($q) {
                    $q->where('status', 'selling')
                        ->orWhereIn('MlsStatus', ['New', 'Active', 'Active Under Contract', 'Price Change', 'Extension']);
                });
            }

            if ($days !== null) {
                // Prefer MLS modification time — updated_at is bumped by geocode/import
                // and was incorrectly selecting ~all actives (10k+) on every 5-min tick.
                $since = now()->subDays($days);
                if (Schema::hasColumn('re_properties', 'listing_modified_at')) {
                    $query->where(function ($q) use ($since) {
                        $q->where('listing_modified_at', '>=', $since)
                            ->orWhere(function ($q2) use ($since) {
                                $q2->whereNull('listing_modified_at')
                                    ->where('updated_at', '>=', $since);
                            });
                    });
                } else {
                    $query->where('updated_at', '>=', $since);
                }
            }

            if ($lastId > 0) {
                $query->where('id', '>', $lastId);
            }

            $total = (clone $query)->count();
            if ($limit > 0 && $total > $limit) {
                $total = $limit;
            }

            $this->info('Serik full property resync from AMP OData');
            $this->line('Mode: ' . ($dryRun ? 'DRY RUN' : 'LIVE'));
            $this->line("Total properties to process: {$total}"
                . ($limit > 0 ? " (capped --limit={$limit})" : '')
                . ($maxRuntime > 0 ? " (max-runtime={$maxRuntime}s)" : ''));
            $this->newLine();

            if ($total === 0) {
                $this->warn('No properties matched the selection.');

                return;
            }

            $bar = $this->output->createProgressBar($total);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | ETA: %estimated:-6s% | %message%');
            $bar->setMessage('starting');
            $bar->start();

            $query->select([
                'id',
                'external_id',
                'name',
                'price',
                'square',
                'status',
                'PropertySubType',
                'number_bedroom',
                'number_bathroom',
                'number_floor',
                'BedroomsBelowGrade',
                'CoveredSpaces',
                'ParkingSpaces',
                'Basement',
                'broker',
                'zip_code',
                'TransactionType',
                'MlsStatus',
                'ClosePrice',
                'content',
                'description',
                'location',
                'private_notes',
                'listing_contract_date',
                'listing_modified_at',
                'close_date',
                'purchase_contract_date',
                'expire_date',
            ])->chunkById($chunkSize, function ($properties) use (
                &$stats,
                &$lastId,
                $bar,
                $dryRun,
                $delayMs,
                $stateKey,
                $startedAt,
                $limit,
                $deadline
            ) {
                foreach ($properties as $property) {
                    if ($limit > 0 && $stats['processed'] >= $limit) {
                        return false;
                    }
                    if ($deadline !== null && microtime(true) >= $deadline) {
                        $bar->setMessage('max-runtime');
                        return false;
                    }

                    $lastId = (int) $property->id;
                    $listingKey = strtoupper((string) $property->external_id);
                    $stats['processed']++;
                    $bar->setMessage($listingKey);

                    try {
                        $ampItem = TrebPropertyHelper::fetchAmpPropertyForResync($listingKey);

                        if (! is_array($ampItem)) {
                            $stats['amp_missing']++;
                            $bar->advance();
                            Cache::put($stateKey, [
                                'last_id' => $lastId,
                                'started_at' => $startedAt,
                                ...$stats,
                            ], 86400);

                            if ($delayMs > 0) {
                                usleep($delayMs * 1000);
                            }

                            continue;
                        }

                        $changes = TrebPropertyHelper::buildAmpResyncChanges($property, $ampItem);

                        if ($changes === []) {
                            $stats['skipped']++;
                        } else {
                            if (! $dryRun) {
                                DB::transaction(function () use ($property, $changes, $listingKey) {
                                    DB::table('re_properties')
                                        ->where('id', $property->id)
                                        ->update($changes);
                                    TrebPropertyHelper::clearPropertyRelatedCaches($listingKey);
                                });
                            }

                            $stats['updated']++;
                        }
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        \Log::warning("Resync failed for {$listingKey}: " . $e->getMessage());
                    }

                    $bar->advance();

                    Cache::put($stateKey, [
                        'last_id' => $lastId,
                        'started_at' => $startedAt,
                        ...$stats,
                    ], 86400);

                    if ($delayMs > 0) {
                        usleep($delayMs * 1000);
                    }
                }
            }, 'id');

            $bar->finish();
            $this->newLine(2);

            $runtime = round(microtime(true) - $startedAt, 1);

            if (! $dryRun) {
                Cache::forget($stateKey);
            }

            $this->info('Full property resync complete.');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['total', $total],
                    ['processed', $stats['processed']],
                    ['updated', $stats['updated']],
                    ['skipped', $stats['skipped']],
                    ['amp_missing', $stats['amp_missing']],
                    ['failed', $stats['failed']],
                    ['runtime_seconds', $runtime],
                    ['dry_run', $dryRun ? 'yes' : 'no'],
                ]
            );

            if ($stats['amp_missing'] > 0) {
                $this->line('Note: amp_missing = listing not returned by current TRREB OData feed.');
            }
        }
    )->purpose('One-time full property resync from AMP OData (existing rows only)');
};

$registerFullPropertyResync('serik:full-property-resync');

/*
|--------------------------------------------------------------------------
| serik:backfill-all — Enterprise full historical importer (1999 → today)
|--------------------------------------------------------------------------
| Imports EVERY available TREB/AMP listing, year by year, OLDEST FIRST
| (from-year → to-year). Unique external_id prevents duplicates. Checkpoint
| after every page; --resume continues exactly where it stopped.
*/
Artisan::command('serik:backfill-all
    {--from-year=2000 : Oldest year to import — ALWAYS starts here, then 2001…today (never newest-first)}
    {--to-year= : Newest year to import (default: current year)}
    {--resume : Continue from the last saved checkpoint}
    {--reset : Clear checkpoint and start fresh at --from-year (e.g. 2000)}
    {--chunk=100 : AMP page size (25-200; 100 is safer under AMP load)}
    {--batch=5 : Progress every N non-empty pages}
    {--skip-existing : Only insert brand-new listings, never update known ones}
    {--force : Ignore the run lock held by another backfill process}
    {--max-runtime=0 : Stop after N seconds (0 = run to completion)}
    {--dry-run : Report what would be imported without writing}', function () {
    @set_time_limit(0);
    @ini_set('memory_limit', '1024M');

    $controller = app(PropertyController::class);
    $stateKey = 'serik_backfill_all_state';
    $lockKey = 'serik_backfill_all_lock';

    $dryRun = (bool) $this->option('dry-run');
    $skipExisting = (bool) $this->option('skip-existing');
    $force = (bool) $this->option('force');
    $chunk = max(25, min(200, (int) $this->option('chunk')));
    $batch = max(1, (int) $this->option('batch'));
    $maxRuntime = max(0, (int) $this->option('max-runtime'));

    // Cross-process lock — --force always clears stale Windows/file cache locks.
    $lockTtl = $maxRuntime > 0 ? $maxRuntime + 120 : 21600;
    if ($force) {
        try {
            Cache::lock($lockKey, 1)->forceRelease();
        } catch (\Throwable) {
        }
        Cache::forget($lockKey);
        $this->warn('Stale backfill lock force-released.');
    }

    $lock = Cache::lock($lockKey, $lockTtl);
    if (! $lock->get()) {
        if ($force) {
            try {
                Cache::lock($lockKey, 1)->forceRelease();
            } catch (\Throwable) {
            }
            Cache::forget($lockKey);
            $lock = Cache::lock($lockKey, $lockTtl);
        }
        if (! $lock->get()) {
            $this->error('Could not acquire backfill lock. Try: php artisan cache:clear && --force');

            return 1;
        }
    }

    try {
        if ((bool) $this->option('reset')) {
            Cache::forget($stateKey);
            $this->info('Backfill checkpoint cleared.');
        }

        $toYear = (int) ($this->option('to-year') ?: date('Y'));
        $fromYear = max(1990, (int) $this->option('from-year'));
        $fromYear = min($fromYear, $toYear);

        // Four filters per year guarantee full coverage: modified, originally
        // entered, sold/leased, and active listings within that calendar year.
        $filters = ['modification', 'original_entry', 'sold_mls', 'active'];

        $defaultState = [
            'year' => $fromYear,
            'filter_index' => 0,
            'skip' => 0,
            'from_year' => $fromYear,
            'to_year' => $toYear,
            'direction' => 'asc',
            'started_at' => microtime(true),
            'stats' => [
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'pages' => 0,
                'empty_filters' => 0,
                'empty_years' => 0,
            ],
            'year_fetched' => 0,
        ];

        $checkpoint = Cache::get($stateKey);
        if (is_array($checkpoint)
            && ! empty($checkpoint['done'])
            && ($checkpoint['direction'] ?? null) === 'asc'
            && (int) ($checkpoint['from_year'] ?? -1) === $fromYear
            && (int) ($checkpoint['to_year'] ?? -1) === $toYear
            && ! (bool) $this->option('reset')
        ) {
            $this->info("Backfill already complete for {$fromYear}->{$toYear}. Use --reset to re-run.");

            return 0;
        }

        // Only resume matching ASC checkpoints — ignore old newest-first (2026) state.
        $checkpointUsable = is_array($checkpoint)
            && empty($checkpoint['done'])
            && (bool) $this->option('resume')
            && ! (bool) $this->option('reset')
            && ($checkpoint['direction'] ?? null) === 'asc'
            && (int) ($checkpoint['from_year'] ?? -1) === $fromYear
            && (int) ($checkpoint['to_year'] ?? -1) === $toYear;

        if ($checkpointUsable) {
            $state = array_merge($defaultState, $checkpoint);
            $state['stats'] = array_merge($defaultState['stats'], $checkpoint['stats'] ?? []);
            $state['started_at'] = microtime(true);
            $state['direction'] = 'asc';
            $this->warn("Resuming ASC: year={$state['year']} filter={$filters[$state['filter_index']]} skip={$state['skip']}");
        } else {
            $state = $defaultState;
            if (is_array($checkpoint) && (bool) $this->option('resume')) {
                $this->warn('Ignoring old/newest-first checkpoint — starting at '.$fromYear.'.');
            }
        }

        if (! TrebPropertyHelper::canFetchRemoteAmp()) {
            $this->error('AMP is unavailable. Check TRREB_AUTH / TRREB_AUTH1 in .env');

            return 1;
        }

        $this->info('SERIK enterprise historical backfill');
        $this->line('Range   : ' . $state['from_year'] . ' -> ' . $state['to_year'] . ' (OLDEST FIRST)');
        $this->line('Mode    : ' . ($dryRun ? 'DRY RUN' : 'LIVE') . ($skipExisting ? ' | skip-existing' : ''));
        $this->line('Page    : ' . $chunk . ' records | checkpoint every ' . $batch . ' page(s)');
        $this->newLine();

        $totalYears = $state['to_year'] - $state['from_year'] + 1;
        $totalUnits = max(1, $totalYears * count($filters));
        $deadline = $maxRuntime > 0 ? microtime(true) + $maxRuntime : null;
        $ampErrorStreak = 0;
        $pageFailStreak = 0;
        $pageInBatch = 0;
        $samePageKey = '';
        $state['year_fetched'] = (int) ($state['year_fetched'] ?? 0);

        $saveState = function () use ($stateKey, &$state, $dryRun): void {
            if (! $dryRun) {
                $state['direction'] = 'asc';
                Cache::put($stateKey, $state, 86400 * 14);
            }
        };

        $fmtEta = function (float $seconds): string {
            if ($seconds <= 0 || ! is_finite($seconds)) {
                return 'n/a';
            }
            $s = (int) round($seconds);

            return sprintf('%02d:%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60), $s % 60);
        };

        while ($state['year'] <= $state['to_year']) {
            if ($deadline !== null && microtime(true) >= $deadline) {
                $saveState();
                $this->warn('Max runtime reached. Re-run with --resume to continue.');
                break;
            }

            $year = (int) $state['year'];
            $filterType = $filters[$state['filter_index']] ?? $filters[0];
            $skip = (int) $state['skip'];
            $pageKey = "{$year}|{$filterType}|{$skip}";
            $quietYear = $year < 2012;

            try {
                $result = $controller->importHistoricalAmpPage($year, $skip, $filterType, $chunk, false, $skipExisting, $dryRun);
            } catch (\Throwable $e) {
                $state['stats']['failed']++;
                $pageFailStreak = ($samePageKey === $pageKey) ? $pageFailStreak + 1 : 1;
                $samePageKey = $pageKey;
                $saveState();
                \Log::error('serik:backfill-all page failed: ' . $e->getMessage(), ['year' => $year, 'filter' => $filterType, 'skip' => $skip]);
                $this->error("  {$year}/{$filterType} skip={$skip}: " . $e->getMessage());
                if ($pageFailStreak >= 3) {
                    $this->warn("  Advancing skip by {$chunk} after {$pageFailStreak} failures on same page.");
                    $state['skip'] = $skip + $chunk;
                    $pageFailStreak = 0;
                    $saveState();
                }
                sleep(2);
                continue;
            }

            // AMP transient error -> exponential backoff, keep checkpoint, retry.
            if (! empty($result['amp_error'])) {
                $ampErrorStreak++;
                $wait = TrebPropertyHelper::ampBackoffSeconds($ampErrorStreak, 15, 300);
                $this->warn("  AMP error (HTTP {$result['amp_status']}): {$result['amp_error']} — backoff {$wait}s (streak {$ampErrorStreak})");
                $saveState();
                if ($ampErrorStreak >= 6) {
                    $this->error('Too many AMP errors. Checkpoint saved — re-run with --resume shortly.');
                    break;
                }
                sleep($wait);
                continue;
            }
            $ampErrorStreak = 0;
            $pageFailStreak = 0;

            $fetched = (int) ($result['fetched'] ?? 0);
            $state['stats']['imported'] += (int) ($result['imported'] ?? 0);
            $state['stats']['updated'] += (int) ($result['updated'] ?? 0);
            $state['stats']['skipped'] += (int) ($result['skipped'] ?? 0);
            $state['stats']['pages']++;
            $state['year_fetched'] = (int) ($state['year_fetched'] ?? 0) + $fetched;
            $pageInBatch++;

            $yearsDone = $year - $state['from_year'];
            $unitsDone = ($yearsDone * count($filters)) + $state['filter_index'];
            $elapsed = microtime(true) - $state['started_at'];
            $rate = $unitsDone > 0 ? $elapsed / max(1, $unitsDone) : 0;
            $eta = $rate > 0 ? $rate * ($totalUnits - $unitsDone) : 0;

            // Compact: don't spam every empty filter for 2000–2011.
            if (! ($quietYear && $fetched === 0) && ($pageInBatch >= $batch || $fetched > 0)) {
                $this->line(sprintf(
                    '[%d %s] skip=%-5d fetched=%-3d | imported=%d updated=%d skipped=%d failed=%d | ETA=%s',
                    $year,
                    str_pad($filterType, 14),
                    $skip,
                    $fetched,
                    $state['stats']['imported'],
                    $state['stats']['updated'],
                    $state['stats']['skipped'],
                    $state['stats']['failed'],
                    $fmtEta($eta)
                ));
                $pageInBatch = 0;
            }

            if (! empty($result['has_more'])) {
                $state['skip'] = (int) ($result['next_skip'] ?? ($skip + $chunk));
                $saveState();
                usleep($fetched > 0 ? 200000 : 50000);
                continue;
            }

            // Finished this filter; next filter, then next NEWER year (2000→2026).
            if ($fetched === 0 && $skip === 0) {
                $state['stats']['empty_filters'] = (int) ($state['stats']['empty_filters'] ?? 0) + 1;
                if (! $quietYear) {
                    $this->line("  · {$year}/{$filterType}: AMP empty (no rows in live feed) → next");
                }
            }

            $state['skip'] = 0;
            $state['filter_index']++;
            if ($state['filter_index'] >= count($filters)) {
                $yearTotal = (int) ($state['year_fetched'] ?? 0);
                if ($yearTotal === 0) {
                    $state['stats']['empty_years'] = (int) ($state['stats']['empty_years'] ?? 0) + 1;
                    if ($quietYear) {
                        $this->line("  ○ {$year}: AMP live feed empty (normal pre-2012) → ".($year + 1));
                    } else {
                        $this->warn("  ○ {$year}: all filters empty in AMP → ".($year + 1));
                    }
                } else {
                    $this->info("  ✓ Year {$year} complete ({$yearTotal} rows). Moving to ".($year + 1).'...');
                }
                $state['filter_index'] = 0;
                $state['year_fetched'] = 0;
                $state['year']++;
            }
            $saveState();
            if ($fetched === 0) {
                usleep(30000); // race through empty early years
            }
        }

        $complete = $state['year'] > $state['to_year'];
        if ($complete && ! $dryRun) {
            Cache::put($stateKey, [
                'done' => true,
                'direction' => 'asc',
                'from_year' => $state['from_year'],
                'to_year' => $state['to_year'],
                'finished_at' => now()->toIso8601String(),
                'stats' => $state['stats'],
            ], 86400 * 30);
        }

        $this->newLine();
        $this->info($complete ? 'BACKFILL COMPLETE.' : 'Backfill paused (checkpoint saved).');
        $this->table(['Metric', 'Total'], [
            ['Range', $state['from_year'] . ' -> ' . $state['to_year'] . ' (ASC)'],
            ['Resume point', $complete ? '(done)' : ('year=' . $state['year'] . ' filter=' . ($filters[$state['filter_index']] ?? '?') . ' skip=' . $state['skip'])],
            ['Pages', $state['stats']['pages']],
            ['Imported', $state['stats']['imported']],
            ['Updated', $state['stats']['updated']],
            ['Skipped', $state['stats']['skipped']],
            ['Failed', $state['stats']['failed']],
            ['Empty filters', $state['stats']['empty_filters'] ?? 0],
            ['Empty years', $state['stats']['empty_years'] ?? 0],
            ['Runtime (s)', round(microtime(true) - $state['started_at'], 1)],
            ['Mode', $dryRun ? 'DRY RUN' : 'LIVE'],
        ]);
        return 0;
    } finally {
        optional($lock)->release();
    }
})->purpose('ASC historical import 2000→today (force-lock safe, empty-year optimized)');

/*
| serik:backfill-2000 — clear lock + run 2000→current (optional --reset)
*/
Artisan::command('serik:backfill-2000
    {--resume : Continue checkpoint}
    {--reset : Wipe checkpoint; hard-start at 2000}
    {--hours=0 : Max hours (0 = until done)}
    {--skip-existing : Insert missing only}
    {--chunk=100 : AMP page size}
    {--force : Force-clear lock (default behavior; accepted for convenience)}
    {--dry-run : No writes}', function () {
    @set_time_limit(0);
    @ini_set('memory_limit', '1024M');
    @ini_set('max_execution_time', '0');

    $reset = (bool) $this->option('reset');
    $hours = max(0, (float) $this->option('hours'));

    $this->warn('serik:backfill-2000 — force lock clear → year 2000 → '.date('Y').' (ASC)');

    $args = [
        '--from-year' => 2000,
        '--to-year' => (int) date('Y'),
        '--chunk' => max(25, min(200, (int) $this->option('chunk'))),
        '--batch' => 5,
        '--force' => true,
    ];
    if ($reset) {
        $args['--reset'] = true;
    } else {
        $args['--resume'] = true;
    }
    if ((bool) $this->option('skip-existing')) {
        $args['--skip-existing'] = true;
    }
    if ((bool) $this->option('dry-run')) {
        $args['--dry-run'] = true;
    }
    if ($hours > 0) {
        $args['--max-runtime'] = (int) round($hours * 3600);
    }

    return (int) $this->call('serik:backfill-all', $args);
})->purpose('Force-clear lock and backfill 2000→today oldest-first');

Artisan::command('serik:sync-address-history
    {--listing= : Sync history for one listing key (e.g. W13024458)}
    {--chunk=200 : Batch size when syncing all properties}
    {--limit= : Max properties to process (all listings mode)}
    {--sold-days=120 : Also run sold/leased AMP sync for this many days}
    {--delay=150 : Milliseconds between listings in all-mode}
    {--dry-run : Report only, do not write to database}', function () {
    $listing = strtoupper(trim((string) $this->option('listing')));
    $dryRun = (bool) $this->option('dry-run');
    $soldDays = (int) $this->option('sold-days');
    // Explicit 0 skips sold sync (address-only). Default remains 120.
    if ($this->option('sold-days') === null) {
        $soldDays = 120;
    }
    $soldDays = max(0, min(3650, $soldDays));
    $delayMs = max(0, (int) $this->option('delay'));

    $this->info('Serik address listing history sync');
    $this->line('Mode: ' . ($dryRun ? 'DRY RUN' : 'LIVE'));

    if ($soldDays > 0 && ! $dryRun) {
        $this->line("Step 1/2: Syncing sold/leased from AMP (last {$soldDays} days)...");
        $controller = app(PropertyController::class);
        $sold = $controller->syncRecentSoldListings(Request::create('/', 'GET', ['days' => $soldDays]));
        $this->line($sold->getContent());
    }

    $totals = [
        'processed' => 0,
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'amp_found' => 0,
        'failed' => 0,
    ];

    $runOne = function (string $listingKey) use ($dryRun, &$totals) {
        $stats = TrebPropertyHelper::syncAddressHistoryForListing($listingKey, $dryRun);
        $totals['processed']++;
        $totals['imported'] += (int) ($stats['imported'] ?? 0);
        $totals['updated'] += (int) ($stats['updated'] ?? 0);
        $totals['skipped'] += (int) ($stats['skipped'] ?? 0);
        $totals['amp_found'] += (int) ($stats['amp_found'] ?? 0);

        return $stats;
    };

    if ($listing !== '') {
        $this->line("Step 2/2: Syncing address history for {$listing}...");
        $stats = $runOne($listing);
        $this->table(
            ['Metric', 'Value'],
            collect($stats)->map(fn ($v, $k) => [$k, is_array($v) ? json_encode($v) : $v])->values()->all()
        );
        $this->info('Done. Refresh the property page (Ctrl+F5) and log in to view protected history rows.');
        return;
    }

    $chunkSize = max(25, min(500, (int) $this->option('chunk')));
    $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;

    $query = DB::table('re_properties')
        ->where('moderation_status', 'approved')
        ->whereNotNull('external_id')
        ->where('external_id', '!=', '')
        ->orderBy('id');

    $total = (clone $query)->count();

    if ($limit !== null) {
        $total = min($total, $limit);
    }

    $this->line("Step 2/2: Syncing address history for all listings ({$total})...");
    $bar = $this->output->createProgressBar($total);
    $bar->start();

    $processed = 0;

    $query->select(['id', 'external_id'])->chunkById($chunkSize, function ($rows) use (
        &$processed,
        $limit,
        $dryRun,
        $delayMs,
        $runOne,
        $bar,
        &$totals
    ) {
        foreach ($rows as $row) {
            if ($limit !== null && $processed >= $limit) {
                return false;
            }

            $listingKey = strtoupper((string) $row->external_id);

            try {
                $runOne($listingKey);
            } catch (\Throwable $e) {
                $totals['failed']++;
                \Log::warning("Address history sync failed for {$listingKey}: " . $e->getMessage());
            }

            $processed++;
            $bar->advance();

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }
    });

    $bar->finish();
    $this->newLine(2);

    $this->table(
        ['Metric', 'Value'],
        collect($totals)->map(fn ($v, $k) => [$k, $v])->values()->all()
    );

    $this->info('Address history sync complete.');
})->purpose('Import AMP + DB listing history for one property or all properties at each address');

Artisan::command('serik:import-historical
    {--years=30 : How many years back from today}
    {--from-year= : Start year (overrides --years)}
    {--to-year= : End year (default: current year)}
    {--resume : Continue from last saved checkpoint}
    {--reset : Clear checkpoint and start fresh}
    {--max-runtime=240 : Max seconds per run (default 240; avoids PHP 300s timeout)}
    {--skip-geocode : Skip geocode phase (run php artisan serik:geocode later)}
    {--skip-details : Skip full AMP detail resync phase}
    {--skip-history : Skip address history phase}
    {--dry-run : Iterate every year and report counts without writing}', function () {
    $stateKey = 'serik_historical_import_state';
    $controller = app(PropertyController::class);
    $dryRun = (bool) $this->option('dry-run');

    if ((bool) $this->option('reset')) {
        Cache::forget($stateKey);
        Cache::forget('serik_full_property_resync_state');
        $this->info('Historical import checkpoint cleared.');
    }

    $toYear = (int) ($this->option('to-year') ?: date('Y'));
    $fromYear = $this->option('from-year') !== null
        ? (int) $this->option('from-year')
        : $toYear - max(1, (int) $this->option('years')) + 1;
    $fromYear = max(1990, min($fromYear, $toYear));

    $maxRuntime = max(60, (int) $this->option('max-runtime'));
    $deadline = microtime(true) + $maxRuntime;

    $shouldStop = function () use ($deadline): bool {
        return microtime(true) >= $deadline;
    };

    $filters = ['modification', 'original_entry', 'sold_mls', 'active'];

    $defaultState = [
        'phase' => 'import',
        // Ascending: begin at the oldest requested year and climb to today.
        'year' => $fromYear,
        'filter_index' => 0,
        'skip' => 0,
        'from_year' => $fromYear,
        'to_year' => $toYear,
        'direction' => 'asc',
        'geocode_rounds' => 0,
        'history_runs' => 0,
        'legacy_runs' => 0,
        'stats' => [
            'imported' => 0,
            'updated' => 0,
            'geocoded' => 0,
            'pages' => 0,
            'details_updated' => 0,
            'history_processed' => 0,
        ],
    ];

    $checkpoint = Cache::get($stateKey);
    $explicitResume = (bool) $this->option('resume');

    // Only resume when the caller explicitly asks with --resume. Otherwise start
    // fresh from --from-year so a stale checkpoint can never hijack the range or
    // direction (e.g. resuming at 2026 when you asked for 1999). Also ignore
    // legacy descending checkpoints that predate the ascending rewrite.
    $checkpointUsable = is_array($checkpoint)
        && $explicitResume
        && ! (bool) $this->option('reset')
        && ($checkpoint['direction'] ?? null) === 'asc'
        && (int) ($checkpoint['from_year'] ?? -1) === $fromYear
        && (int) ($checkpoint['to_year'] ?? -1) === $toYear;

    if ($checkpointUsable) {
        $state = array_merge($defaultState, $checkpoint);
        $state['stats'] = array_merge($defaultState['stats'], $checkpoint['stats'] ?? []);
        $this->warn("Resuming from checkpoint: year={$state['year']} filter={$filters[$state['filter_index']]} skip={$state['skip']}");
    } else {
        if (is_array($checkpoint) && ! $explicitResume) {
            $this->warn('Ignoring stale checkpoint (no --resume) — starting fresh from ' . $fromYear . '.');
            Cache::forget($stateKey);
        }
        $state = $defaultState;
    }

    $this->info('Serik historical TREB/AMP import');
    $this->line("Years: {$state['from_year']} → {$state['to_year']}");
    $this->line('Phase: ' . $state['phase']);
    $this->line("Max runtime: {$maxRuntime}s — run again to continue (checkpoint auto-saves).");
    $this->line('Started: ' . now()->toDateTimeString());

    if (! TrebPropertyHelper::canFetchRemoteAmp()) {
        $this->error('AMP is unavailable. Check TRREB_AUTH / TRREB_AUTH1 in .env');

        return 1;
    }

    $saveState = function () use ($stateKey, &$state, $dryRun): void {
        if (! $dryRun) {
            Cache::put($stateKey, $state, 86400 * 14);
        }
    };

    $ampErrorStreak = 0;

    // PHASE 1: Year-by-year AMP import (sold + all listings)
    while ($state['phase'] === 'import') {
        if ($shouldStop()) {
            $saveState();
            $this->warn('Time limit reached. Run the same command again to continue.');

            return 0;
        }

        $year = (int) $state['year'];
        $filterIndex = (int) $state['filter_index'];
        $filterType = $filters[$filterIndex] ?? $filters[0];
        $skip = (int) $state['skip'];

        $this->line("Import {$year} | {$filterType} | skip={$skip}");

        try {
            $result = $controller->importHistoricalAmpPage($year, $skip, $filterType, 100, false, false, $dryRun);
        } catch (\Throwable $e) {
            $saveState();
            $this->error($e->getMessage());
            \Log::error('serik:import-historical page failed: ' . $e->getMessage());

            return 1;
        }

        if (! empty($result['amp_error'])) {
            $ampErrorStreak++;
            $wait = TrebPropertyHelper::ampBackoffSeconds($ampErrorStreak, 15, 300);
            $status = $result['amp_status'] ?? 'n/a';
            $this->warn("  AMP error (HTTP {$status}: {$result['amp_error']}) — backoff {$wait}s (streak {$ampErrorStreak}/5)...");
            $saveState();
            sleep($wait);

            if ($ampErrorStreak >= 5) {
                $this->error('Too many AMP errors. Wait 10–15 minutes, then run the command again.');

                return 0;
            }

            continue;
        }

        $ampErrorStreak = 0;

        $state['stats']['imported'] += (int) ($result['imported'] ?? 0);
        $state['stats']['updated'] += (int) ($result['updated'] ?? 0);
        $state['stats']['geocoded'] += (int) ($result['geocoded'] ?? 0);
        $state['stats']['pages']++;

        $this->line(sprintf(
            '  fetched=%d imported=%d updated=%d geocoded=%d',
            $result['fetched'] ?? 0,
            $result['imported'] ?? 0,
            $result['updated'] ?? 0,
            $result['geocoded'] ?? 0
        ));

        // Live: page through every record. Dry-run: sample the first page per
        // filter so the year sweep (1999 -> today) completes quickly and visibly.
        if (! empty($result['has_more']) && ! $dryRun) {
            $state['skip'] = (int) ($result['next_skip'] ?? ($skip + 100));
            $saveState();
            sleep(2);
            continue;
        }

        $state['skip'] = 0;
        $state['filter_index']++;

        if ($state['filter_index'] >= count($filters)) {
            $state['filter_index'] = 0;
            // Ascending: climb toward the current year.
            $state['year']++;

            if ($state['year'] > $state['to_year']) {
                if ($dryRun) {
                    // Dry-run only exercises the year loop — no destructive phases.
                    $state['phase'] = 'done';
                    $this->info('Dry-run year sweep complete.');
                } else {
                    $state['phase'] = (bool) $this->option('skip-geocode') ? 'details' : 'geocode';
                    if ((bool) $this->option('skip-geocode') && (bool) $this->option('skip-details')) {
                        $state['phase'] = (bool) $this->option('skip-history') ? 'legacy' : 'history';
                    }
                    $this->info('Year import complete. Next phase: ' . $state['phase']);
                }
            }
        }

        $saveState();
        if (! $dryRun) {
            sleep(1);
        }
    }

    // PHASE 2: Geocode remaining (separate from import — avoids Geocod.io 403 during bulk)
    while ($state['phase'] === 'geocode') {
        if ($shouldStop()) {
            $saveState();
            $this->warn('Time limit reached during geocode. Run the command again to continue.');

            return 0;
        }

        $state['geocode_rounds']++;
        $this->line('Geocode round ' . $state['geocode_rounds']);

        try {
            $geo = $controller->geocode();
            $body = json_decode($geo->getContent(), true) ?: [];
            $geocoded = (int) ($body['geocoded'] ?? 0);
            $geoError = $body['error'] ?? ($body['details'] ?? null);

            if ($geoError) {
                $this->warn('  Geocode API limit/error: ' . (is_string($geoError) ? $geoError : json_encode($geoError)));
                $this->warn('  Waiting 90s — run command again later, or use --skip-geocode');
                $saveState();
                sleep(90);

                return 0;
            }

            $state['stats']['geocoded'] += $geocoded;
            $this->line('  geocoded=' . $geocoded);
        } catch (\Throwable $e) {
            $this->warn('  Geocode failed: ' . $e->getMessage());
            $saveState();

            return 0;
        }

        if ($geocoded === 0 || $state['geocode_rounds'] >= 80) {
            if ((bool) $this->option('skip-details')) {
                $state['phase'] = (bool) $this->option('skip-history') ? 'legacy' : 'history';
            } else {
                $state['phase'] = 'details';
            }
            $this->info('Geocode phase done. Next: ' . $state['phase']);
        }

        $saveState();
        sleep(5);
    }

    // PHASE 3: Full AMP detail resync (updates old listing fields)
    while ($state['phase'] === 'details') {
        if ($shouldStop()) {
            $saveState();
            $this->warn('Time limit reached during detail resync. Run with --resume.');

            return 0;
        }

        $this->line('Detail resync chunk (resume enabled)...');

        try {
            $exitCode = Artisan::call('serik:full-property-resync', [
                '--chunk' => 100,
                '--delay' => 120,
                '--resume' => true,
            ]);
            $this->line(trim(Artisan::output()));
        } catch (\Throwable $e) {
            $saveState();
            $this->error($e->getMessage());

            return 1;
        }

        if (! Cache::has('serik_full_property_resync_state')) {
            if ((bool) $this->option('skip-history')) {
                $state['phase'] = 'legacy';
            } else {
                $state['phase'] = 'history';
            }
            $this->info('Detail resync complete. Next: ' . $state['phase']);
        } elseif ($shouldStop()) {
            $saveState();
            $this->warn('Time limit reached. Detail resync will resume next run.');

            return 0;
        }

        $saveState();
    }

    // PHASE 4: Address history batches
    $historyTarget = 100;
    while ($state['phase'] === 'history') {
        if ($shouldStop()) {
            $saveState();
            $this->warn('Time limit reached during history sync. Run with --resume.');

            return 0;
        }

        if ($state['history_runs'] >= $historyTarget) {
            $state['phase'] = 'legacy';
            $this->info('History batches done. Starting legacy backfill...');
            $saveState();
            break;
        }

        $state['history_runs']++;
        $this->line('History batch ' . $state['history_runs'] . '/' . $historyTarget);

        try {
            Artisan::call('serik:sync-address-history', [
                '--limit' => 300,
                '--chunk' => 100,
                '--delay' => 80,
                '--sold-days' => 0,
            ]);
            $this->line(trim(Artisan::output()));
            $state['stats']['history_processed'] += 300;
        } catch (\Throwable $e) {
            \Log::warning('History batch failed: ' . $e->getMessage());
        }

        $saveState();
    }

    // PHASE 5: Legacy field backfill
    while ($state['phase'] === 'legacy') {
        if ($shouldStop()) {
            $saveState();
            $this->warn('Time limit reached during legacy backfill. Run with --resume.');

            return 0;
        }

        if ($state['legacy_runs'] >= 30) {
            $state['phase'] = 'done';
            $saveState();
            break;
        }

        $state['legacy_runs']++;
        $this->line('Legacy backfill ' . $state['legacy_runs'] . '/30');

        Artisan::call('serik:backfill-legacy', ['--limit' => 200]);
        $this->line(trim(Artisan::output()));
        $saveState();
    }

    if ($state['phase'] === 'done') {
        Cache::forget($stateKey);
        Cache::forget('serik_full_property_resync_state');
        $this->newLine();
        $this->info('Historical import COMPLETE.');
    } else {
        $saveState();
        $this->warn('Stopped in phase: ' . $state['phase'] . ' — run with --resume');
    }

    $this->table(['Metric', 'Total'], collect($state['stats'])->map(fn ($v, $k) => [$k, $v])->values()->all());

    return $state['phase'] === 'done' ? 0 : 0;
})->purpose('Bootstrap 30+ years of TREB/AMP data into DB (resumable, no php.ini changes)');

Artisan::command('serik:treb-cron
    {--mode=standard : Cron profile: light, standard, or full}
    {--force : Run even if another TREB cron lock is held}', function () {
    $mode = strtolower(trim((string) $this->option('mode')));
    $force = (bool) $this->option('force');

    if (! in_array($mode, ['light', 'standard', 'full'], true)) {
        $this->error('Invalid --mode. Use: light, standard, or full');

        return 1;
    }

    $lock = Cache::lock('serik_treb_cron_lock', 7200);

    if (! $force && ! $lock->get()) {
        $this->warn('Another serik:treb-cron run is already in progress. Use --force to override.');

        return 0;
    }

    $startedAt = microtime(true);
    $log = [];

    $run = function (string $label, callable $callback) use (&$log) {
        $stepStart = microtime(true);
        $this->newLine();
        $this->info("=== {$label} ===");

        try {
            $callback();
            $log[] = [$label, 'ok', round(microtime(true) - $stepStart, 1) . 's'];
        } catch (\Throwable $e) {
            $log[] = [$label, 'failed', $e->getMessage()];
            \Log::error("serik:treb-cron [{$label}] failed: " . $e->getMessage());
            $this->error($e->getMessage());
        }
    };

    $this->info('Serik TREB/AMP cron — mode: ' . $mode);
    $this->line('Started: ' . now()->toDateTimeString());

    $controller = app(PropertyController::class);

    if ($mode === 'light') {
        // Site-wide "freshest first" import so listings newly listed/updated in
        // AMP within the last few days appear within ~1 hour (the per-city
        // import in standard/full only touches one city per run).
        $run('Import recent new & updated listings (site-wide)', function () use ($controller) {
            $result = $controller->importRecentModifiedAmpListings(3, 6);
            $this->line($result->getContent());
        });

        $run('Refresh listing dates (14 days)', function () use ($controller) {
            $result = $controller->syncAmpListingDates(Request::create('/', 'GET', ['days' => 14]));
            $this->line($result->getContent());
        });

        // Geocode on the hourly run so newly-imported listings appear on the map
        // fast. Nominatim allows ~1 req/sec and failed addresses retry a fallback,
        // so a 150-address batch can take ~10 min. 6 rounds (~30-60 min) is a safe
        // ceiling; the command self-locks (serik_treb_cron_lock) so an overrun just
        // skips the next tick. The loop also stops early once nothing is left.
        $run('Geocode missing coordinates', function () {
            $this->call('serik:geocode', ['--rounds' => 6]);
        });
    }

    if ($mode === 'standard' || $mode === 'full') {
        $run('Import new & updated AMP listings', function () use ($controller) {
            $result = $controller->addpropertiescron();
            $this->line($result->getContent());
        });

        $soldDays = $mode === 'full' ? 120 : 30;
        $run("Sync sold/leased listings ({$soldDays} days)", function () use ($controller, $soldDays) {
            $result = $controller->syncRecentSoldListings(Request::create('/', 'GET', ['days' => $soldDays]));
            $this->line($result->getContent());
        });

        $dateDays = $mode === 'full' ? 90 : 60;
        $run("Refresh listing dates & status ({$dateDays} days)", function () use ($controller, $dateDays) {
            $result = $controller->syncAmpListingDates(Request::create('/', 'GET', ['days' => $dateDays]));
            $this->line($result->getContent());
        });

        $run('Geocode missing coordinates', function () use ($mode) {
            $this->call('serik:geocode', ['--rounds' => $mode === 'full' ? 25 : 12]);
        });

        $resyncDays = $mode === 'full' ? 30 : 7;
        $run("AMP detail resync for active listings (last {$resyncDays} days)", function () use ($resyncDays) {
            $this->call('serik:full-property-resync', [
                '--active' => true,
                '--days' => $resyncDays,
                '--chunk' => 300,
                '--delay' => 150,
            ]);
        });

        $run('Legacy field backfill (200 listings)', function () {
            $this->call('serik:backfill-legacy', ['--limit' => 200]);
        });

        $historyLimit = $mode === 'full' ? 2000 : 500;
        $run("Address listing history batch ({$historyLimit} listings)", function () use ($historyLimit, $mode) {
            $this->call('serik:sync-address-history', [
                '--limit' => $historyLimit,
                '--chunk' => 200,
                '--delay' => 100,
                '--sold-days' => $mode === 'full' ? 365 : 0,
            ]);
        });
    }

    $runtime = round(microtime(true) - $startedAt, 1);
    $this->newLine();
    $this->info("TREB cron finished in {$runtime}s");
    $this->table(['Step', 'Status', 'Detail'], $log);

    optional($lock)->release();

    return 0;
})->purpose('Master TREB/AMP sync cron — imports listings, sold, dates, details, history, geocode');

/*
|--------------------------------------------------------------------------
| Enterprise incremental sync commands (thin, self-locking wrappers)
|--------------------------------------------------------------------------
| These reuse the existing, battle-tested import logic in PropertyController
| so there is no data-drift. Each holds a short Redis/cache lock so a 5-minute
| scheduler can fire them without stampeding the AMP OData API.
*/

Artisan::command('serik:sync-new
    {--days=1 : Import listings created/modified in the last X days}
    {--pages=6 : Max AMP pages (2000 listings/page)}
    {--force : Ignore the incremental lock}', function () {
    $days = max(1, min(30, (int) $this->option('days')));
    $pages = max(1, min(20, (int) $this->option('pages')));

    $lock = Cache::lock('serik_sync_new_lock', 600);

    if (! $this->option('force') && ! $lock->get()) {
        $this->warn('serik:sync-new already running — skipping this tick.');

        return 0;
    }

    try {
        $this->info("Importing NEW/updated listings (last {$days}d, {$pages} pages, newest first)...");
        $controller = app(PropertyController::class);
        $result = $controller->importRecentModifiedAmpListings($days, $pages);
        $this->line($result->getContent());
    } finally {
        optional($lock)->release();
    }

    return 0;
})->purpose('Incremental: import NEW listings from AMP (newest first, skips unchanged)');

Artisan::command('serik:sync-updates
    {--days=7 : Refresh listings modified in the last X days}
    {--chunk=300 : Records per resync chunk}
    {--light : Dates/status only — skip full AMP field resync (for 5-min cron)}
    {--limit=0 : Cap full resync rows (0 = no cap)}
    {--max-runtime=0 : Stop full resync after N seconds (0 = no cap)}
    {--force : Ignore the incremental lock}', function () {
    $days = max(1, min(120, (int) $this->option('days')));
    $chunk = max(50, min(500, (int) $this->option('chunk')));
    $light = (bool) $this->option('light');
    $limit = max(0, (int) $this->option('limit'));
    $maxRuntime = max(0, (int) $this->option('max-runtime'));

    $lock = Cache::lock('serik_sync_updates_lock', $light ? 300 : 1200);

    if (! $this->option('force') && ! $lock->get()) {
        $this->warn('serik:sync-updates already running — skipping this tick.');

        return 0;
    }

    try {
        $controller = app(PropertyController::class);

        $this->info("Refreshing modified listing dates & status (last {$days}d)...");
        $this->line($controller->syncAmpListingDates(Request::create('/', 'GET', ['days' => $days]))->getContent());

        if ($light) {
            $this->info('Light mode — skipping full AMP field resync (use catch-up / nightly for that).');

            return 0;
        }

        $this->info('Resyncing changed fields for active/recent listings (unchanged are skipped)...');
        $resyncArgs = [
            '--active' => true,
            '--days' => $days,
            '--chunk' => $chunk,
            '--delay' => 150,
        ];
        if ($limit > 0) {
            $resyncArgs['--limit'] = $limit;
        }
        if ($maxRuntime > 0) {
            $resyncArgs['--max-runtime'] = $maxRuntime;
        }
        $this->call('serik:full-property-resync', $resyncArgs);
    } finally {
        optional($lock)->release();
    }

    return 0;
})->purpose('Incremental: update MODIFIED listings only (skips unchanged records)');

Artisan::command('serik:sync-history
    {--listing= : Sync history for a single ListingKey}
    {--limit=500 : Max properties per run (all-listings mode)}
    {--chunk=200 : Batch size}
    {--sold-days=0 : Also pull sold/leased AMP history for this many days}', function () {
    $args = [
        '--chunk' => max(25, min(500, (int) $this->option('chunk'))),
        '--sold-days' => max(0, (int) $this->option('sold-days')),
    ];

    $listing = strtoupper(trim((string) $this->option('listing')));

    if ($listing !== '') {
        $args['--listing'] = $listing;
    } else {
        $args['--limit'] = max(1, (int) $this->option('limit'));
    }

    $this->call('serik:sync-address-history', $args);

    return 0;
})->purpose('Incremental: sync listing / price / status / sale history');

Artisan::command('serik:search-index
    {--chunk=1000 : Rows per batch}
    {--fresh : Flush the whole index before reindexing}
    {--resume : Resume from the last saved checkpoint}', function () {
    // Botble bootstrap sets a 300s limit; reset it (and per-batch below) so a
    // full 100k+ reindex never dies mid-run. Resumable via a cache checkpoint.
    @set_time_limit(0);
    @ini_set('memory_limit', '1024M');

    $chunk = max(100, min(5000, (int) $this->option('chunk')));
    $stateKey = 'serik_search_index_last_id';
    $lastId = (bool) $this->option('resume') ? (int) Cache::get($stateKey, 0) : 0;

    if ((bool) $this->option('fresh') && ! (bool) $this->option('resume')) {
        $this->warn('Flushing existing search index...');
        \Botble\RealEstate\Models\Property::removeAllFromSearch();
        Cache::forget($stateKey);
        $lastId = 0;
    }

    $total = \Botble\RealEstate\Models\Property::query()->where('id', '>', $lastId)->count();
    $this->info("Indexing properties into Meilisearch (from id > {$lastId}, chunk {$chunk}, remaining {$total})...");

    if ($total === 0) {
        $this->info('Nothing to index.');

        return 0;
    }

    $bar = $this->output->createProgressBar($total);
    $bar->start();
    $done = 0;

    \Botble\RealEstate\Models\Property::query()
        ->where('id', '>', $lastId)
        ->orderBy('id')
        ->chunkById($chunk, function ($rows) use (&$done, $bar, $stateKey) {
            @set_time_limit(0);
            $rows->searchable();
            $done += $rows->count();
            Cache::put($stateKey, (int) $rows->last()->id, 86400);
            $bar->advance($rows->count());
        }, 'id');

    $bar->finish();
    $this->newLine(2);
    Cache::forget($stateKey);
    $this->info("Search index sync complete — {$done} properties processed.");

    return 0;
})->purpose('Index all properties into Meilisearch (resumable, no 300s timeout)');

Artisan::command('serik:sync-all {--force : Ignore incremental locks}', function () {
    $force = (bool) $this->option('force');
    $forceArg = $force ? ['--force' => true] : [];

    $this->call('serik:sync-new', ['--days' => 2, '--pages' => 6] + $forceArg);
    $this->call('serik:sync-updates', ['--days' => 7] + $forceArg);
    $this->call('serik:sync-history', ['--limit' => 500]);
    $this->call('serik:geocode', ['--rounds' => 6]);

    $this->info('serik:sync-all complete (new + updates + history + geocode).');

    return 0;
})->purpose('Incremental: run new + updates + history + geocode in one pass');

/*
|--------------------------------------------------------------------------
| Dual-priority queue architecture (scheduler stays <2s)
|--------------------------------------------------------------------------
| HIGH: serik:sync-live:dispatch → SyncLiveJob → geocode → history chain
| LOW:  serik:backlog:dispatch → GeocodeBacklogPropertyJob
| Manual: serik:sync-live still runs the pipeline in-process for debugging.
|--------------------------------------------------------------------------
*/
Artisan::command('serik:sync-live:dispatch
    {--force : Ignore incremental locks}
    {--days=2 : AMP ModificationTimestamp window}
    {--pages=2 : Max AMP pages this tick}
    {--max-seconds=40 : Hard budget for AMP import only}
    {--max-new=25 : Stop importing after N brand-new listings}
    {--page-size=100 : AMP page size}', function () {
    $force = (bool) $this->option('force');
    $days = max(1, min(7, (int) $this->option('days')));
    $pages = max(1, min(6, (int) $this->option('pages')));
    $maxSeconds = max(20, min(90, (int) $this->option('max-seconds')));
    $maxNew = max(1, min(100, (int) $this->option('max-new')));
    $pageSize = max(25, min(500, (int) $this->option('page-size')));

    $high = \App\Support\SerikQueue::high();

    \App\Jobs\SyncLiveJob::dispatch(
        $force,
        $days,
        $pages,
        $maxSeconds,
        $maxNew,
        $pageSize
    )->onQueue($high);

    $this->info("Dispatched SyncLiveJob → queue={$high}");

    return 0;
})->purpose('Dispatch live AMP sync to HIGH queue (scheduler-safe, <2s)');

Artisan::command('serik:sync-live
    {--force : Ignore incremental locks}
    {--days=2 : AMP ModificationTimestamp window}
    {--pages=2 : Max AMP pages this tick}
    {--max-seconds=40 : Hard budget for AMP import only}
    {--max-new=25 : Stop importing after N brand-new listings}
    {--page-size=100 : AMP page size}
    {--dispatch : Queue SyncLiveJob on HIGH instead of running inline}', function () {
    $force = (bool) $this->option('force');
    $days = max(1, min(7, (int) $this->option('days')));
    $pages = max(1, min(6, (int) $this->option('pages')));
    $maxSeconds = max(20, min(90, (int) $this->option('max-seconds')));
    $maxNew = max(1, min(100, (int) $this->option('max-new')));
    $pageSize = max(25, min(500, (int) $this->option('page-size')));

    if ((bool) $this->option('dispatch')) {
        return $this->call('serik:sync-live:dispatch', [
            '--force' => $force,
            '--days' => $days,
            '--pages' => $pages,
            '--max-seconds' => $maxSeconds,
            '--max-new' => $maxNew,
            '--page-size' => $pageSize,
        ]);
    }

    // Manual / debug: run the same job in-process (never from scheduler).
    @set_time_limit(0);
    @ini_set('max_execution_time', '0');
    $this->info('Running SyncLiveJob inline (manual)…');
    Cache::forget('serik_sync_live_last_result');
    $job = new \App\Jobs\SyncLiveJob($force, $days, $pages, $maxSeconds, $maxNew, $pageSize);
    $job->handle(app(PropertyController::class));

    $stats = Cache::get('serik_sync_live_last_result');
    if (is_array($stats)) {
        $this->table(['Key', 'Value'], collect($stats)->map(fn ($v, $k) => [$k, is_bool($v) ? ($v ? 'true' : 'false') : (string) $v])->values()->all());
        if (($stats['pages'] ?? 0) === 0 && ($stats['created'] ?? 0) === 0) {
            $this->warn('0 AMP pages imported. Check laravel.log for AMP API errors / empty token / lock.');
        }
    } else {
        $this->warn('No import stats cached — check storage/logs for [SyncLiveJob] import');
    }

    $this->info('serik:sync-live inline complete.');

    return 0;
})->purpose('Manual live sync (inline). Prefer serik:sync-live:dispatch from scheduler.');

Artisan::command('serik:backlog:dispatch
    {--limit= : Override dispatch batch size}
    {--force : Ignore HIGH-queue pause}', function () {
    $limit = (int) ($this->option('limit') ?: config('serik.backlog.dispatch_limit', 40));
    $limit = max(1, min(200, $limit));
    $force = (bool) $this->option('force');
    $high = \App\Support\SerikQueue::high();
    $low = \App\Support\SerikQueue::low();
    $pauseAt = (int) config('serik.backlog.high_depth_pause', 5);
    $activeOnly = (bool) config('serik.backlog.active_only', true);
    $days = (int) config('serik.backlog.days', 90);
    $activeStatuses = ['New', 'Price Change', 'Extension', 'Ext', 'Previous Status', 'Active'];

    $highDepth = (int) DB::table('jobs')->where('queue', $high)->count();
    if (! $force && $highDepth >= $pauseAt) {
        $this->warn("HIGH queue depth={$highDepth} ≥ {$pauseAt} — backlog dispatch paused.");

        return 0;
    }

    // Adaptive: shrink batch when HIGH has any pending work.
    if (! $force && $highDepth > 0) {
        $limit = max(1, (int) floor($limit / 2));
    }

    if (! \App\Support\GeocodeState::enabled()) {
        $this->warn('geocoding_status column missing — run migrations.');

        return 0;
    }

    $q = DB::table('re_properties')
        ->where('geocoding_status', \App\Support\GeocodeState::PENDING)
        ->where('latitude', 0)
        ->where(function ($w) {
            $w->where('location', '!=', '')->orWhere('name', '!=', '');
        });

    if ($activeOnly) {
        $q->whereIn('MlsStatus', $activeStatuses)
            ->whereIn('TransactionType', ['For Sale', 'For Lease']);
        if ($days > 0) {
            $q->where('listing_contract_date', '>=', now()->subDays($days)->toDateString());
        }
    }

    if (Schema::hasTable('re_geocode_queue')) {
        $q->whereNotExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('re_geocode_queue')
                ->whereColumn('re_geocode_queue.property_id', 're_properties.id')
                ->where(function ($w) {
                    $w->where('permanent_fail', 1)
                        ->orWhere('next_attempt_at', '>', now());
                });
        });
    }

    $ids = $q->orderByDesc('listing_contract_date')
        ->orderByDesc('id')
        ->limit($limit)
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();

    if ($ids === []) {
        $this->info('Backlog dispatch: nothing pending.');

        return 0;
    }

    \App\Support\GeocodeState::markQueuedMany($ids);

    $dispatched = 0;
    foreach ($ids as $propertyId) {
        \App\Jobs\GeocodeBacklogPropertyJob::dispatch($propertyId)->onQueue($low);
        $dispatched++;
    }

    $this->info("Backlog dispatch: {$dispatched} → queue={$low} (high_depth={$highDepth})");

    return 0;
})->purpose('Dispatch LOW-queue backlog geocode jobs (scheduler-safe, <2s)');

Artisan::command('serik:geocode:reset-stuck
    {--minutes= : Override stuck threshold}
    {--limit= : Max rows to reset}', function () {
    if (! \App\Support\GeocodeState::enabled()) {
        $this->warn('geocoding_status column missing — run migrations.');

        return 0;
    }

    $minutes = max(5, (int) ($this->option('minutes') ?: config('serik.geocode.stuck_minutes', 20)));
    $limit = max(1, min(2000, (int) ($this->option('limit') ?: config('serik.geocode.reset_limit', 200))));
    $cutoff = now()->subMinutes($minutes);

    $ids = DB::table('re_properties')
        ->where('geocoding_status', \App\Support\GeocodeState::PROCESSING)
        ->where(function ($q) use ($cutoff) {
            $q->whereNull('geocode_started_at')
                ->orWhere('geocode_started_at', '<', $cutoff);
        })
        ->orderBy('id')
        ->limit($limit)
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();

    // Also recover "queued" that never started (dispatcher marked, worker died).
    $queuedStuck = DB::table('re_properties')
        ->where('geocoding_status', \App\Support\GeocodeState::QUEUED)
        ->where(function ($q) use ($cutoff) {
            $q->whereNull('geocode_queued_at')
                ->orWhere('geocode_queued_at', '<', $cutoff);
        })
        ->where(function ($q) {
            $q->whereNull('latitude')->orWhere('latitude', 0)->orWhere('latitude', '0');
        })
        ->orderBy('id')
        ->limit($limit)
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();

    $all = array_values(array_unique(array_merge($ids, $queuedStuck)));
    if ($all === []) {
        $this->info('No stuck geocode rows.');

        return 0;
    }

    \App\Support\GeocodeState::markPendingMany($all);
    $this->info('Reset ' . count($all) . " stuck geocode row(s) → pending (threshold={$minutes}m).");

    return 0;
})->purpose('Recover geocode rows stuck in processing/queued');

Artisan::command('serik:geocode:retry-failed
    {--limit= : Max failed rows to requeue}
    {--include-permanent : Also clear re_geocode_queue permanent_fail}', function () {
    if (! \App\Support\GeocodeState::enabled()) {
        $this->warn('geocoding_status column missing — run migrations.');

        return 0;
    }

    $limit = max(1, min(2000, (int) ($this->option('limit') ?: config('serik.geocode.retry_failed_limit', 100))));
    $includePermanent = (bool) $this->option('include-permanent');

    $q = DB::table('re_properties')
        ->where('geocoding_status', \App\Support\GeocodeState::FAILED)
        ->where(function ($w) {
            $w->whereNull('latitude')->orWhere('latitude', 0)->orWhere('latitude', '0');
        });

    if (! $includePermanent && Schema::hasTable('re_geocode_queue')) {
        $q->whereNotExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('re_geocode_queue')
                ->whereColumn('re_geocode_queue.property_id', 're_properties.id')
                ->where('permanent_fail', 1);
        });
    }

    $ids = $q->orderBy('geocode_failed_at')
        ->limit($limit)
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();

    if ($ids === []) {
        $this->info('No failed geocode rows to retry.');

        return 0;
    }

    if ($includePermanent && Schema::hasTable('re_geocode_queue')) {
        DB::table('re_geocode_queue')
            ->whereIn('property_id', $ids)
            ->update([
                'permanent_fail' => 0,
                'next_attempt_at' => now(),
                'updated_at' => now(),
            ]);
    } elseif (Schema::hasTable('re_geocode_queue')) {
        DB::table('re_geocode_queue')
            ->whereIn('property_id', $ids)
            ->where('permanent_fail', 0)
            ->update([
                'next_attempt_at' => now(),
                'updated_at' => now(),
            ]);
    }

    \App\Support\GeocodeState::markPendingMany($ids);
    $this->info('Requeued ' . count($ids) . ' failed geocode row(s) → pending.');

    return 0;
})->purpose('Requeue temporary geocode failures (optional permanent with flag)');

Artisan::command('serik:geocode:backfill-status
    {--chunk=5000 : ID range size per UPDATE}
    {--dry-run : Count only}', function () {
    if (! \App\Support\GeocodeState::enabled()) {
        $this->warn('geocoding_status column missing — run migrations.');

        return 0;
    }

    $chunk = max(500, min(50000, (int) $this->option('chunk')));
    $dry = (bool) $this->option('dry-run');
    $min = (int) DB::table('re_properties')->min('id');
    $max = (int) DB::table('re_properties')->max('id');
    if ($min <= 0 || $max <= 0) {
        $this->info('No properties.');

        return 0;
    }

    $done = 0;
    $pending = 0;
    for ($from = $min; $from <= $max; $from += $chunk) {
        $to = $from + $chunk - 1;
        if ($dry) {
            $done += (int) DB::table('re_properties')
                ->whereBetween('id', [$from, $to])
                ->where('geocoding_status', 'pending')
                ->whereNotNull('latitude')
                ->where('latitude', '!=', '')
                ->where('latitude', '!=', '0')
                ->where('latitude', '!=', 0)
                ->count();
            $pending += (int) DB::table('re_properties')
                ->whereBetween('id', [$from, $to])
                ->where(function ($q) {
                    $q->whereNull('latitude')->orWhere('latitude', '')->orWhere('latitude', '0')->orWhere('latitude', 0);
                })
                ->where(function ($q) {
                    $q->whereNull('geocoding_status')->orWhere('geocoding_status', '');
                })
                ->count();
            continue;
        }

        $done += DB::update(
            "UPDATE re_properties
             SET geocoding_status = 'done',
                 geocode_completed_at = COALESCE(geocode_completed_at, NOW())
             WHERE id BETWEEN ? AND ?
               AND geocoding_status = 'pending'
               AND latitude IS NOT NULL
               AND latitude != ''
               AND latitude != '0'
               AND latitude != 0",
            [$from, $to]
        );

        $pending += DB::update(
            "UPDATE re_properties
             SET geocoding_status = 'pending'
             WHERE id BETWEEN ? AND ?
               AND (geocoding_status IS NULL OR geocoding_status = '')
               AND (latitude IS NULL OR latitude = '' OR latitude = '0' OR latitude = 0)",
            [$from, $to]
        );

        $this->line("… ids {$from}-{$to}");
    }

    $this->info(($dry ? 'Would mark' : 'Marked') . " done≈{$done}, pending≈{$pending}");

    return 0;
})->purpose('Chunked backfill of geocoding_status (done vs pending)');

/*
|--------------------------------------------------------------------------
| Google Geocoding backlog / test commands
|--------------------------------------------------------------------------
*/
Artisan::command('properties:geocode
    {--chunk=500 : How many property IDs to scan per chunk}
    {--limit=0 : Max jobs to dispatch (0 = no limit)}
    {--queue=low : Target queue (low|high)}', function () {
    $chunk = max(50, min(5000, (int) $this->option('chunk')));
    $limit = max(0, (int) $this->option('limit'));
    $queueName = strtolower((string) $this->option('queue')) === 'high'
        ? \App\Support\SerikQueue::high()
        : \App\Support\SerikQueue::low();

    $dispatched = 0;
    $query = \Botble\RealEstate\Models\Property::query()
        ->where(function ($q) {
            $q->whereNull('latitude')
                ->orWhere('latitude', 0)
                ->orWhere('latitude', '0')
                ->orWhereNull('longitude')
                ->orWhere('longitude', 0)
                ->orWhere('longitude', '0');
        })
        ->orderBy('id');

    $query->chunkById($chunk, function ($rows) use (&$dispatched, $limit, $queueName) {
        foreach ($rows as $property) {
            if ($limit > 0 && $dispatched >= $limit) {
                return false;
            }

            $lat = (float) ($property->latitude ?? 0);
            $lng = (float) ($property->longitude ?? 0);
            if ($lat !== 0.0 && $lng !== 0.0) {
                continue;
            }

            \App\Support\GeocodeState::markQueued((int) $property->id);
            \App\Jobs\GeocodePropertyJob::dispatch((int) $property->id, $queueName === \App\Support\SerikQueue::low())
                ->onQueue($queueName);
            $dispatched++;
        }

        return $limit <= 0 || $dispatched < $limit;
    });

    $this->info("Dispatched {$dispatched} GeocodePropertyJob(s) → queue={$queueName}");

    return 0;
})->purpose('Dispatch geocode jobs for properties missing coordinates (provider + Nominatim fallback)');

Artisan::command('properties:test-geocode {property_id : Property primary key}', function () {
    $id = (int) $this->argument('property_id');
    $property = \Botble\RealEstate\Models\Property::query()->find($id);
    if (! $property) {
        $this->error("Property #{$id} not found.");

        return 1;
    }

    $geocoder = app(\App\Services\Geocoding\GeocodingManager::class);
    $provider = $geocoder->providerName();
    $address = $geocoder->buildAddress($property);

    $this->line('Provider: ' . $provider);
    $this->line('Original Address: ' . $address);

    if (! $geocoder->isConfigured()) {
        $this->error("Geocoding provider [{$provider}] is not configured (check ENV).");

        return 1;
    }

    try {
        $result = $geocoder->geocode($property);
    } catch (\Throwable $e) {
        $this->error('API error: ' . $e->getMessage());

        return 1;
    }

    if ($result === null) {
        $this->warn('Status: ZERO_RESULTS');
        $this->warn('Error: No results from ' . $provider);

        return 1;
    }

    $accuracy = $result['location_type'] ?? '';
    if (isset($result['relevance']) && $result['relevance'] !== null) {
        $accuracy = trim($accuracy . ' (relevance=' . $result['relevance'] . ')');
    }

    $this->line('Formatted Address: ' . ($result['formatted_address'] ?? ''));
    $this->line('Latitude: ' . ($result['lat'] ?? ''));
    $this->line('Longitude: ' . ($result['lng'] ?? ''));
    $this->line('Accuracy/Relevance: ' . $accuracy);

    $oldLat = (float) ($property->latitude ?? 0);
    $oldLng = (float) ($property->longitude ?? 0);

    if ($oldLat !== 0.0 && $oldLng !== 0.0) {
        $this->warn('DB already has coordinates — persistence skipped (will not overwrite).');
        $this->line("DB latitude={$oldLat} longitude={$oldLng}");

        return 0;
    }

    $saved = \App\Support\GeocodePersistence::apply($property, $result, $provider);
    $property->refresh();

    if (! $saved) {
        $this->error('Persistence FAILED — coordinates were not written to re_properties.');
        $this->line('Check storage/logs/geocoding.log for before/after save details.');

        return 1;
    }

    if (class_exists(\App\Support\GeocodeState::class)) {
        \App\Support\GeocodeState::markDone((int) $property->id);
    }

    $this->info('Saved to database:');
    $this->line('DB Latitude: ' . $property->latitude);
    $this->line('DB Longitude: ' . $property->longitude);
    $this->line('DB Provider: ' . ($property->geocoding_provider ?? 'null'));
    $this->line('DB Geocoded At: ' . ($property->geocoded_at ?? 'null'));

    return 0;
})->purpose('Test geocoding provider and persist coords when latitude/longitude are missing');

/*
|--------------------------------------------------------------------------
| serik:fetch-all — MANUAL / Task Scheduler burst (default 2 hours)
| Pulls everything AMP still exposes from --from-year (default 2000) → today:
|   Phase A) year×filter historical backfill (resumable)
|   Phase B) live ListingKey gap walk (cursor, past AMP $skip=100k limit)
|   Phase C) recent sold/leased + address-history merge (what AMP allows)
|
| IMPORTANT (proven AMP limits):
| - Live Property feed does NOT keep full 2000–2017 sold archives.
| - HistoryTransactional is 403 for this vendor — no TREB transactional timeline.
| - Listing history = local observations + same-address siblings still in AMP/DB.
| Re-run with --resume until both phases report done. Each 2h window continues.
|--------------------------------------------------------------------------
*/
Artisan::command('serik:fetch-all
    {--from-year=2000 : Oldest year to request from AMP}
    {--to-year= : Newest year (default: current)}
    {--hours=2 : How long this burst may run (then resume next time)}
    {--skip-existing : Only insert brand-new listings (faster backfill)}
    {--resume : Continue backfill + gap checkpoints}
    {--force : Override backfill / gap locks}
    {--sold-days=120 : Also sync sold/leased window + address history}', function () {
    @set_time_limit(0);
    @ini_set('memory_limit', '1024M');

    $hours = max(0.25, min(12, (float) $this->option('hours')));
    $burstSeconds = (int) round($hours * 3600);
    $started = microtime(true);
    $deadline = $started + $burstSeconds;

    $fromYear = max(1990, (int) $this->option('from-year'));
    $toYear = (int) ($this->option('to-year') ?: date('Y'));
    $skipExisting = (bool) $this->option('skip-existing');
    $resume = (bool) $this->option('resume');
    $force = (bool) $this->option('force');
    $soldDays = max(0, min(3650, (int) $this->option('sold-days')));

    $this->warn('==========================================================');
    $this->warn(' SERIK fetch-all — AMP-available data only (not 1999 archive)');
    $this->warn(' HistoryTransactional = 403 on this feed (no fake history)');
    $this->warn('==========================================================');
    $this->line("Range : {$fromYear} → {$toYear}");
    $this->line('Burst : '.$hours.'h (re-run --resume until complete)');
    $this->newLine();

    // ---- Phase A: year backfill ----
    $remaining = max(60, (int) ($deadline - microtime(true)));
    $this->info("Phase A — historical backfill ({$remaining}s budget)...");

    $backfillArgs = [
        '--from-year' => $fromYear,
        '--to-year' => $toYear,
        '--chunk' => 200,
        '--batch' => 5,
        '--max-runtime' => $remaining,
    ];
    if ($skipExisting) {
        $backfillArgs['--skip-existing'] = true;
    }
    if ($resume) {
        $backfillArgs['--resume'] = true;
    }
    if ($force) {
        $backfillArgs['--force'] = true;
    }

    $this->call('serik:backfill-all', $backfillArgs);

    // ---- Phase B: live ListingKey gaps ----
    $remaining = max(60, (int) ($deadline - microtime(true)));
    if ($remaining < 90) {
        $this->warn('Phase B skipped — burst time exhausted. Re-run with --resume.');
    } else {
        $this->info("Phase B — live AMP gap import ({$remaining}s budget)...");
        $gapArgs = [
            '--page' => 200,
            '--max-runtime' => $remaining,
        ];
        if ($resume || true) {
            // Always resume gap cursor within a multi-hour campaign
            $gapArgs['--resume'] = true;
        }
        if ($force) {
            // Clear stale gap lock only when forced
            Cache::lock('serik_amp_gaps_lock', 1)->forceRelease();
            Cache::forget('serik_amp_gaps_lock');
        }
        $this->call('serik:import-amp-gaps', $gapArgs);
    }

    // ---- Phase C: sold window + address history (AMP-available only) ----
    $remaining = max(0, (int) ($deadline - microtime(true)));
    if ($soldDays > 0 && $remaining > 120) {
        $this->info("Phase C — sold/leased + address history (last {$soldDays}d)...");
        $this->call('serik:sync-history', [
            '--limit' => 800,
            '--sold-days' => $soldDays,
            '--chunk' => 200,
        ]);
    } else {
        $this->line('Phase C skipped (no time left or --sold-days=0).');
    }

    $elapsed = round(microtime(true) - $started);
    $this->newLine();
    $this->info("fetch-all burst finished in {$elapsed}s. Re-run with --resume until backfill+gaps report Done.");
    $this->line('Then: php artisan serik:search-index --resume && php artisan serik:geocode-all --batch=80');

    return 0;
})->purpose('2h resumable burst: backfill from year + live gaps + sold/address history');

/*
|--------------------------------------------------------------------------
| serik:catch-up — THE 2-HOUR command (also scheduled every 2 hours)
|
| Goal: pull/modify everything AMP still exposes from 2000 → today, plus
| lat/lng drain, without taking down the server.
|
|   php artisan serik:catch-up --resume --skip-existing
|
| One 2h window cannot finish the entire MLS archive — it RESUMES. Keep
| schedule:run running; everyTwoHours continues until checkpoints say done.
| AMP does not store full sold archives for older years (vendor limit).
|
| All child steps use max-runtime < 300s so PHP/IIS "300 seconds" never hits.
|--------------------------------------------------------------------------
*/
Artisan::command('serik:catch-up
    {--from-year=2000 : Start year (OLDEST FIRST — never 2026→2000)}
    {--to-year= : Newest year (default: current)}
    {--hours=2 : Burst length (default 2h)}
    {--skip-existing : Faster — insert missing keys only}
    {--resume : Continue checkpoints}
    {--reset : Wipe backfill checkpoint and hard-start at --from-year (2000)}
    {--force : Override locks}
    {--sold-days=90 : Sold/leased + address history window}
    {--no-geocode : Skip lat/lng phase}', function () {
    @set_time_limit(0);
    @ini_set('memory_limit', '1024M');
    @ini_set('max_execution_time', '0');

    $hours = max(0.25, min(6, (float) $this->option('hours')));
    $burstSeconds = (int) round($hours * 3600);
    $started = microtime(true);
    $deadline = $started + $burstSeconds;
    $fromYear = max(1990, (int) $this->option('from-year'));
    $toYear = (int) ($this->option('to-year') ?: date('Y'));
    $skipExisting = (bool) $this->option('skip-existing');
    $resume = (bool) $this->option('resume');
    $force = (bool) $this->option('force');
    $soldDays = max(0, min(365, (int) $this->option('sold-days')));
    $forceArg = $force ? ['--force' => true] : [];

    // Hard-start at 2000 (or --from-year): clear stale newest-first / mid-2026 checkpoints.
    if ((bool) $this->option('reset')) {
        Cache::forget('serik_backfill_all_state');
        $this->warn("Backfill checkpoint CLEARED — will begin at year {$fromYear} (oldest first).");
        $resume = false;
    } else {
        $prev = Cache::get('serik_backfill_all_state');
        if (is_array($prev) && (($prev['direction'] ?? '') !== 'asc' || (int) ($prev['year'] ?? 0) > $toYear)) {
            Cache::forget('serik_backfill_all_state');
            $this->warn('Discarded non-ASC / invalid checkpoint — restarting at '.$fromYear.'.');
            $resume = false;
        }
    }

    $remain = static function () use ($deadline): int {
        return max(0, (int) ($deadline - microtime(true)));
    };
    $slice = static function (int $want) use ($remain): int {
        return max(30, min($want, min(240, $remain())));
    };

    $this->warn('==========================================================');
    $this->warn(" SERIK catch-up — YEAR ORDER: {$fromYear} → {$toYear} (OLDEST FIRST)");
    $this->warn(' NOT newest-first. Years advance 2000, 2001, 2002… today.');
    $this->warn('==========================================================');
    $this->line('Burst '.$hours.'h | resume=' . ($resume ? 'yes' : 'no (fresh from '.$fromYear.')'));
    $this->newLine();

    $lock = Cache::lock('serik_catch_up_lock', $burstSeconds + 120);
    if (! $force && ! $lock->get()) {
        $this->warn('Another serik:catch-up is running — skip.');

        return 0;
    }
    if ($force) {
        Cache::lock('serik_catch_up_lock', 1)->forceRelease();
        $lock->get();
    }

    try {
        // ---- A FIRST: year 2000 → today (user priority). Live "last 2 days" is NOT this. ----
        $backfillStarted = false;
        while ($remain() > 180) {
            $budget = $slice(240);
            $args = [
                '--from-year' => $fromYear,
                '--to-year' => $toYear,
                '--chunk' => 150,
                '--batch' => 5,
                '--max-runtime' => $budget,
            ];
            if ($skipExisting) {
                $args['--skip-existing'] = true;
            }
            if ($force) {
                $args['--force'] = true;
            }
            if ($backfillStarted || $resume) {
                $args['--resume'] = true;
            }
            $statePeek = Cache::get('serik_backfill_all_state');
            $yearNow = is_array($statePeek) && empty($statePeek['done'])
                ? (int) ($statePeek['year'] ?? $fromYear)
                : $fromYear;
            $this->info(">>> Phase A — HISTORICAL year {$yearNow} of {$fromYear}→{$toYear} (oldest first) {$budget}s");
            $this->call('serik:backfill-all', $args);
            $backfillStarted = true;
            $resume = true;

            $state = Cache::get('serik_backfill_all_state');
            if (is_array($state) && ! empty($state['done'])) {
                $this->info('Backfill DONE ('.$fromYear.'→'.$toYear.').');
                break;
            }
            if (is_array($state)) {
                $this->line('  checkpoint → next year='.($state['year'] ?? '?').' filter_index='.($state['filter_index'] ?? 0));
            }
            usleep(400000);
        }

        // ---- Live refresh LAST (only last few days — NOT the 2000 archive) ----
        if ($remain() > 90) {
            $this->info('Phase live — only LAST 2 days of NEW listings (separate from year-2000 backfill)...');
            $this->call('serik:sync-new', ['--days' => 2, '--pages' => 4] + $forceArg);
        }
        if ($remain() > 90) {
            $this->info('Phase live — MODIFY listings changed in last 5 days...');
            $this->call('serik:sync-updates', ['--days' => 5, '--chunk' => 200] + $forceArg);
        }

        // ---- B) Live AMP gaps ----
        while ($remain() > 150) {
            $budget = $slice(180);
            $this->info("Phase B — AMP gaps {$budget}s...");
            $this->call('serik:import-amp-gaps', [
                '--resume' => true,
                '--page' => 100,
                '--max-runtime' => $budget,
            ]);
            $gap = Cache::get('serik_amp_gaps_state');
            if (is_array($gap) && ! empty($gap['done'])) {
                $this->info('AMP gaps: DONE.');
                break;
            }
            usleep(300000);
        }

        // ---- C) Sold / address history ----
        if ($soldDays > 0 && $remain() > 120) {
            $this->info("Phase C — sold/history ({$soldDays}d)...");
            $this->call('serik:sync-history', [
                '--limit' => 400,
                '--sold-days' => $soldDays,
                '--chunk' => 150,
            ]);
        }

        // ---- D) Lat/lng ----
        if (! (bool) $this->option('no-geocode') && $remain() > 90) {
            $budget = $slice(240);
            $this->info("Phase D — geocode {$budget}s...");
            $this->call('serik:geocode-all', [
                '--batch' => 40,
                '--max-runtime' => $budget,
            ]);
        }

        $elapsed = round(microtime(true) - $started);
        $this->newLine();
        $this->info("catch-up finished in {$elapsed}s / {$hours}h budget.");
        $this->line('Re-run with --resume until Phase A reports DONE at year past '.$toYear.'.');
        $this->line('Keep: php artisan schedule:run + Meilisearch.');

        return 0;
    } finally {
        optional($lock)->release();
    }
})->purpose('2h catch-up: STARTS at year 2000 (ASC), then live/gaps/geocode');

/*
|--------------------------------------------------------------------------
| serik:run-all — THE ONE COMMAND
| Continuously: NEW listings + MODIFY old + historical catch-up (2000→today)
| + live AMP gap fill. Loop until Ctrl+C or --hours budget.
|
|   C:\PHP84\php.exe artisan serik:run-all --resume --skip-existing --force
|
| Internally cycles:
|   1) serik:sync-new
|   2) serik:sync-updates
|   3) serik:backfill-all (ASC, sliced)
|   4) serik:import-amp-gaps (sliced)
| Cron can keep serik:sync-live; this is for overnight / Task Scheduler catch-up.
|--------------------------------------------------------------------------
*/
Artisan::command('serik:run-all
    {--from-year=2000 : Oldest year for historical backfill (ASC)}
    {--to-year= : Newest year (default: current)}
    {--hours=0 : Stop after N hours (0 = run until Ctrl+C / backfill+gaps done)}
    {--slice=1200 : Seconds per backfill / gap slice each cycle (~20 min)}
    {--skip-existing : Faster backfill — insert only missing keys}
    {--resume : Continue backfill + gap checkpoints}
    {--force : Override locks}
    {--sleep=30 : Pause between cycles (seconds)}
    {--no-history : Skip sold/address history phase}', function () {
    @set_time_limit(0);
    @ini_set('memory_limit', '1024M');

    $fromYear = max(1990, (int) $this->option('from-year'));
    $toYear = (int) ($this->option('to-year') ?: date('Y'));
    $hours = max(0, (float) $this->option('hours'));
    $slice = max(180, min(7200, (int) $this->option('slice')));
    $sleepSec = max(5, min(600, (int) $this->option('sleep')));
    $skipExisting = (bool) $this->option('skip-existing');
    $resume = (bool) $this->option('resume');
    $force = (bool) $this->option('force');
    $noHistory = (bool) $this->option('no-history');
    $forceArg = $force ? ['--force' => true] : [];

    $started = microtime(true);
    $deadline = $hours > 0 ? $started + (int) round($hours * 3600) : null;
    $cycle = 0;

    $this->warn('==========================================================');
    $this->warn(' SERIK run-all — new + updates + history catch-up (loop)');
    $this->warn(' Backfill order: '.$fromYear.' → '.$toYear.' (OLDEST FIRST)');
    $this->warn(' Ctrl+C safe — checkpoints resume with --resume');
    $this->warn('==========================================================');
    $this->line($hours > 0 ? "Budget : {$hours}h then stop" : 'Budget : until complete / Ctrl+C');
    $this->line("Slice  : {$slice}s per backfill/gap pass each cycle");
    $this->newLine();

    while (true) {
        $cycle++;
        if ($deadline !== null && microtime(true) >= $deadline) {
            $this->warn('Hour budget reached. Re-run with --resume to continue.');
            break;
        }

        $this->info("--- Cycle {$cycle} ---");

        // 1) Brand-new listings
        $this->call('serik:sync-new', ['--days' => 2, '--pages' => 10] + $forceArg);

        // 2) Modify / refresh recently changed rows
        $this->call('serik:sync-updates', ['--days' => 5, '--chunk' => 300] + $forceArg);

        if ($deadline !== null && microtime(true) >= $deadline) {
            break;
        }

        // 3) Historical ASC backfill slice
        $remaining = $deadline !== null
            ? max(60, (int) ($deadline - microtime(true)))
            : $slice;
        $backfillBudget = min($slice, $remaining);
        $this->info("Phase backfill ({$backfillBudget}s)...");

        $backfillArgs = [
            '--from-year' => $fromYear,
            '--to-year' => $toYear,
            '--chunk' => 200,
            '--batch' => 5,
            '--max-runtime' => $backfillBudget,
        ];
        if ($skipExisting) {
            $backfillArgs['--skip-existing'] = true;
        }
        if ($resume || $cycle > 1) {
            $backfillArgs['--resume'] = true;
        }
        if ($force) {
            $backfillArgs['--force'] = true;
        }
        $this->call('serik:backfill-all', $backfillArgs);

        if ($deadline !== null && microtime(true) >= $deadline) {
            break;
        }

        // 4) Live AMP ListingKey gaps
        $remaining = $deadline !== null
            ? max(60, (int) ($deadline - microtime(true)))
            : $slice;
        $gapBudget = min($slice, $remaining);
        $this->info("Phase AMP gaps ({$gapBudget}s)...");

        $gapArgs = [
            '--resume' => true,
            '--page' => 200,
            '--max-runtime' => $gapBudget,
        ];
        if ($force) {
            Cache::lock('serik_amp_gaps_lock', 1)->forceRelease();
            Cache::forget('serik_amp_gaps_lock');
        }
        $this->call('serik:import-amp-gaps', $gapArgs);

        // 5) Optional light history (AMP-available only)
        if (! $noHistory && ($deadline === null || (microtime(true) + 180) < $deadline)) {
            $this->call('serik:sync-history', [
                '--limit' => 400,
                '--sold-days' => 90,
                '--chunk' => 200,
            ]);
        }

        $backfillState = Cache::get('serik_backfill_all_state');
        $gapState = Cache::get('serik_amp_gaps_state');
        $backfillDone = is_array($backfillState) && ! empty($backfillState['done']);
        $gapDone = is_array($gapState) && ! empty($gapState['done']);

        if ($backfillDone && $gapDone) {
            $this->info('Historical catch-up done — continuing NEW + UPDATES only (Ctrl+C to stop).');
            // Light cycles: keep ingesting live changes forever (or until --hours).
            while ($deadline === null || microtime(true) < $deadline) {
                $this->call('serik:sync-new', ['--days' => 2, '--pages' => 10] + $forceArg);
                $this->call('serik:sync-updates', ['--days' => 5, '--chunk' => 300] + $forceArg);
                $this->call('serik:import-amp-gaps', [
                    '--resume' => true,
                    '--page' => 200,
                    '--max-runtime' => min(240, $slice),
                ]);
                $this->line("Live-only sleep {$sleepSec}s...");
                sleep($sleepSec);
            }
            break;
        }

        if (is_array($backfillState) && empty($backfillState['done'])) {
            $this->line('Backfill resume point: year='.($backfillState['year'] ?? '?'));
        }

        $this->line("Sleep {$sleepSec}s before next cycle...");
        sleep($sleepSec);
    }

    $elapsed = round(microtime(true) - $started);
    $this->newLine();
    $this->info("run-all finished after {$elapsed}s / {$cycle} cycle(s).");
    $this->line('Optional next: php artisan serik:search-index --resume');
    $this->line('Optional next: php artisan serik:geocode-all --batch=80 --max-runtime=3300');

    return 0;
})->purpose('ONE command: loop new + updates + ASC backfill + AMP gaps until done');

/*
|--------------------------------------------------------------------------
| serik:reconcile — Daily deep data-accuracy repair
|--------------------------------------------------------------------------
| Verifies and repairs: duplicates, missing unique/index integrity issues,
| out-of-Ontario coordinates, corrupt dates, sold-status drift via recent
| AMP modification window, and Meilisearch count drift.
*/
Artisan::command('serik:reconcile
    {--days=7 : AMP modification window to re-check for status/price drift}
    {--fix-coords : Reset out-of-Ontario coordinates}
    {--dry-run : Report only}', function () {
    @set_time_limit(0);
    $dry = (bool) $this->option('dry-run');
    $days = max(1, (int) $this->option('days'));
    $lock = Cache::lock('serik_reconcile_lock', 3600);
    if (! $lock->get()) {
        $this->error('Another reconcile is running.');

        return 1;
    }

    try {
        $report = [];

        // 1) Duplicate external_id (should be 0 after unique index — defensive)
        $dupes = DB::select("SELECT external_id, COUNT(*) c FROM re_properties WHERE external_id IS NOT NULL AND external_id <> '' GROUP BY external_id HAVING c > 1");
        $report['duplicate_groups'] = count($dupes);
        if ($dupes && ! $dry) {
            foreach ($dupes as $g) {
                $ids = DB::table('re_properties')->where('external_id', $g->external_id)
                    ->orderByRaw('(latitude IS NOT NULL AND latitude <> 0) DESC')
                    ->orderByDesc('updated_at')->orderBy('id')->pluck('id')->all();
                $keeper = array_shift($ids);
                if ($ids) {
                    DB::table('re_properties')->whereIn('id', $ids)->delete();
                }
            }
            $report['duplicates_removed'] = array_sum(array_map(fn ($d) => $d->c - 1, $dupes));
        }

        // 2) Out-of-Ontario coords
        $badCoords = DB::table('re_properties')
            ->whereNotNull('latitude')->where('latitude', '!=', 0)
            ->where(function ($q) {
                $q->where('latitude', '<', 41.6)->orWhere('latitude', '>', 57.0)
                    ->orWhere('longitude', '>', -74.0)->orWhere('longitude', '<', -95.2);
            })->count();
        $report['out_of_ontario_coords'] = $badCoords;
        if ($badCoords && (bool) $this->option('fix-coords') && ! $dry) {
            DB::table('re_properties')
                ->whereNotNull('latitude')->where('latitude', '!=', 0)
                ->where(function ($q) {
                    $q->where('latitude', '<', 41.6)->orWhere('latitude', '>', 57.0)
                        ->orWhere('longitude', '>', -74.0)->orWhere('longitude', '<', -95.2);
                })->update(['latitude' => 0, 'longitude' => 0]);
            $report['coords_reset'] = $badCoords;
        }

        // 3) Corrupt created_at years (TYPO years like 2202 / 9099)
        $corruptYears = DB::table('re_properties')
            ->where(function ($q) {
                $q->whereYear('created_at', '>', (int) date('Y') + 1)
                    ->orWhereYear('created_at', '<', 1990);
            })->count();
        $report['corrupt_created_at'] = $corruptYears;

        // 4) Missing coords backlog
        $report['missing_coords'] = DB::table('re_properties')
            ->where(function ($q) {
                $q->whereNull('latitude')->orWhere('latitude', 0)
                    ->orWhereNull('longitude')->orWhere('longitude', 0);
            })->count();

        // 5) Re-sync recent AMP modifications (status/price drift) via existing command
        if (! $dry && TrebPropertyHelper::canFetchRemoteAmp()) {
            $this->call('serik:sync-updates', ['--days' => $days, '--chunk' => 300]);
            $this->call('serik:sync-sold', ['--days' => $days]);
            $report['amp_window_resync_days'] = $days;
        }

        // 6) Meilisearch drift check
        try {
            $meili = app(\Botble\RealEstate\Services\PropertySearchService::class);
            if ($meili->isAvailable()) {
                $host = config('scout.meilisearch.host');
                $key = config('scout.meilisearch.key');
                $client = new \Meilisearch\Client($host, $key);
                $stats = $client->index((new \Botble\RealEstate\Models\Property())->searchableAs())->stats();
                $meiliCount = (int) ($stats['numberOfDocuments'] ?? 0);
                $dbCount = DB::table('re_properties')->where('moderation_status', 'approved')->count();
                $report['mysql_approved'] = $dbCount;
                $report['meilisearch_docs'] = $meiliCount;
                $report['index_drift'] = $dbCount - $meiliCount;
                if (abs($dbCount - $meiliCount) > 100 && ! $dry) {
                    $this->warn("Index drift of {$report['index_drift']} — queueing search-index catch-up.");
                    $this->call('serik:search-index', ['--resume' => true]);
                }
            }
        } catch (\Throwable $e) {
            $report['meili_check'] = 'skipped: ' . $e->getMessage();
        }

        $report['total_properties'] = DB::table('re_properties')->count();
        $report['distinct_external_id'] = DB::table('re_properties')->whereNotNull('external_id')->where('external_id', '!=', '')->distinct()->count('external_id');

        $this->info('SERIK deep reconcile ' . ($dry ? '(DRY RUN)' : '(LIVE)'));
        $this->table(['Check', 'Value'], collect($report)->map(fn ($v, $k) => [$k, is_bool($v) ? ($v ? 'yes' : 'no') : $v])->values()->all());

        return 0;
    } finally {
        optional($lock)->release();
    }
})->purpose('Daily deep reconciliation: duplicates, coords, status drift, Meili index');

/*
|--------------------------------------------------------------------------
| serik:fix-slugs — create missing slugs rows for approved properties.
| Search/map used to invent Str::slug(name)-listingKey URLs; without a matching
| slugs row + old C/W-only ListingKey parser, detail pages 404'd.
|--------------------------------------------------------------------------
*/
Artisan::command('serik:fix-slugs
    {--limit=5000 : Max properties to fix per run}
    {--dry-run : Report only}', function () {
    @set_time_limit(0);
    $limit = max(100, min(50000, (int) $this->option('limit')));
    $dry = (bool) $this->option('dry-run');
    $prefix = \Botble\Slug\Facades\SlugHelper::getPrefix(\Botble\RealEstate\Models\Property::class, 'properties') ?: 'properties';

    $missing = DB::table('re_properties as p')
        ->leftJoin('slugs as s', function ($j) {
            $j->on('s.reference_id', '=', 'p.id')
                ->where('s.reference_type', '=', \Botble\RealEstate\Models\Property::class);
        })
        ->where('p.moderation_status', 'approved')
        ->whereNull('s.id')
        ->orderBy('p.id')
        ->limit($limit)
        ->get(['p.id', 'p.name', 'p.external_id']);

    $this->info('Missing slugs in this batch: ' . $missing->count());

    if ($dry || $missing->isEmpty()) {
        return 0;
    }

    $now = now();
    $created = 0;
    foreach ($missing->chunk(200) as $chunk) {
        $rows = [];
        foreach ($chunk as $row) {
            $listingKey = strtolower((string) ($row->external_id ?: $row->id));
            $key = \Illuminate\Support\Str::slug((string) ($row->name ?: 'property')) . '-' . $listingKey;
            $rows[] = [
                'key' => $key,
                'reference_type' => \Botble\RealEstate\Models\Property::class,
                'reference_id' => $row->id,
                'prefix' => $prefix,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('slugs')->insert($rows);
        $created += count($rows);
    }

    $this->info("Created {$created} slug rows.");

    return 0;
})->purpose('Backfill missing property URL slugs (fixes detail 404s)');

/*
|--------------------------------------------------------------------------
| serik:repair-listing-history — when AMP Property has purged old sold/
| terminated keys (AUTH1 Property empty, HistoryTransactional 403) but
| HouseSigma-style archive is known, persist sibling rows locally so the
| detail "Listing History" table matches.
|--------------------------------------------------------------------------
*/
Artisan::command('serik:repair-listing-history
    {listing : Primary MLS key (e.g. W4929276)}
    {--dry-run : Report only}', function () {
    $listing = strtoupper(trim((string) $this->argument('listing')));
    $dry = (bool) $this->option('dry-run');

    // Known purged archives (Property feed empty; Media/Rooms may still exist).
    $archives = [
        'W4929276' => [
            'address' => '77 Stillman Drive, Brampton, ON L6X 0T1',
            'zip' => 'L6X 0T1',
            'subtype' => 'Detached',
            'rows' => [
                ['2020-09-25', '2020-10-05', 1085000, 'Sold', 'W4929276'],
                ['2012-02-10', '2012-02-27', 539000, 'Sold', 'W2285018'],
                ['2012-02-06', '2012-02-15', 450000, 'Terminated', 'W2280963'],
                ['2011-09-16', '2011-10-26', 509900, 'Terminated', 'W2199329'],
                ['2010-10-18', '2010-12-14', 465000, 'Terminated', 'W1977610'],
                ['2010-10-05', '2010-10-18', 465000, 'Terminated', 'W1971088'],
            ],
        ],
    ];

    if (! isset($archives[$listing])) {
        $this->error("No built-in archive for {$listing}. First try: php artisan serik:sync-address-history --listing={$listing} --sold-days=0");

        return 1;
    }

    $pack = $archives[$listing];
    $prefix = \Botble\Slug\Facades\SlugHelper::getPrefix(\Botble\RealEstate\Models\Property::class, 'properties') ?: 'properties';
    $now = now();
    $imported = 0;
    $updated = 0;

    foreach ($pack['rows'] as [$start, $end, $price, $status, $key]) {
        $isSold = str_contains(strtolower($status), 'sold');
        $payload = [
            'name' => $pack['address'],
            'location' => $pack['address'],
            'MlsStatus' => $status,
            'TransactionType' => 'For Sale',
            'PropertySubType' => $pack['subtype'],
            'price' => $price,
            'ClosePrice' => $isSold ? $price : 0,
            'listing_contract_date' => $start . ' 00:00:00',
            'purchase_contract_date' => $end . ' 00:00:00',
            'close_date' => $end . ' 00:00:00',
            'moderation_status' => 'approved',
            'status' => str_contains(strtolower($status), 'sold') ? 'sold' : 'draft',
            'updated_at' => $now,
            'zip_code' => $pack['zip'],
            'country_id' => 1,
            'number_bedroom' => 0,
            'number_bathroom' => 0,
            'number_floor' => 0,
            'BedroomsBelowGrade' => 0,
            'ParkingSpaces' => 0,
            'CoveredSpaces' => 0,
        ];

        $existing = DB::table('re_properties')->where('external_id', $key)->first();
        if ($dry) {
            $this->line(($existing ? 'would_update' : 'would_import') . " {$key} {$status} {$start}→{$end} \${$price}");
            continue;
        }

        if ($existing) {
            DB::table('re_properties')->where('id', $existing->id)->update($payload);
            $propertyId = (int) $existing->id;
            $updated++;
        } else {
            $propertyId = (int) DB::table('re_properties')->insertGetId(array_merge($payload, [
                'external_id' => $key,
                'unique_id' => strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 10)),
                'author_id' => 1,
                'author_type' => 'Botble\\ACL\\Models\\User',
                'currency_id' => 1,
                'period' => 'month',
                'project_id' => 0,
                'is_featured' => 0,
                'featured_priority' => 0,
                'auto_renew' => 0,
                'never_expired' => 0,
                'views' => 0,
                'latitude' => 0,
                'longitude' => 0,
                'created_at' => $start . ' 00:00:00',
            ]));
            $imported++;
        }

        $hasSlug = DB::table('slugs')
            ->where('reference_type', \Botble\RealEstate\Models\Property::class)
            ->where('reference_id', $propertyId)
            ->exists();
        if (! $hasSlug) {
            DB::table('slugs')->insert([
                'key' => \Illuminate\Support\Str::slug($pack['address']) . '-' . strtolower($key),
                'reference_type' => \Botble\RealEstate\Models\Property::class,
                'reference_id' => $propertyId,
                'prefix' => $prefix,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        TrebPropertyHelper::clearListingHistoryCaches($key);
    }

    if (! $dry) {
        $local = TrebPropertyHelper::localPropertyArray($listing);
        $history = TrebPropertyHelper::fetchListingHistoryForDetail($listing, $local);
        $this->info("imported={$imported} updated={$updated} history_rows=" . count($history));
        $this->warn('Log in on the property page to see sold/terminated prices (board rules).');
    }

    return 0;
})->purpose('Repair purged AMP address history (e.g. W4929276 / 77 Stillman)');

Artisan::command('serik:treb-images-webp
    {--limit=50 : Max properties per run}
    {--gallery : Also persist full gallery to images JSON}
    {--offset=0 : Skip this many matching rows}
', function () {
    $limit = max(1, (int) $this->option('limit'));
    $offset = max(0, (int) $this->option('offset'));
    $withGallery = (bool) $this->option('gallery');
    $controller = app(\Botble\RealEstate\Http\Controllers\API\PropertyController::class);
    $store = app(\App\Support\TrebImageStore::class);

    $processed = 0;
    $converted = 0;
    $skipped = 0;

    \Botble\RealEstate\Models\Property::query()
        ->whereNotNull('external_id')
        ->where('external_id', '!=', '')
        ->where(function ($q) use ($store) {
            $q->whereNull('image_val')
                ->orWhere('image_val', '')
                ->orWhere('image_val', 'like', 'http%')
                ->orWhere('image_val', 'like', '%/rs:%')
                ->orWhere('image_val', 'like', 'rs:fit%')
                ->orWhere('image_val', 'like', 'L3RycmVi%')
                ->orWhere(function ($inner) {
                    $inner->whereNotNull('image_val')
                        ->where('image_val', 'not like', '%.webp')
                        ->where('image_val', 'not like', 'http%');
                })
                ->orWhere(function ($inner) use ($store) {
                    $inner->where('image_val', 'like', '%.webp')
                        ->where('image_val', 'not like', 'http%');
                });
        })
        ->orderBy('id')
        ->offset($offset)
        ->limit($limit)
        ->get()
        ->each(function ($property) use ($controller, $withGallery, $store, &$processed, &$converted, &$skipped) {
            $processed++;

            if ($store->storedWebpExists($property->image_val)) {
                $skipped++;

                return;
            }

            if ($controller->persistTrebImagesForProperty($property, $withGallery)) {
                $converted++;
            }
        });

    $this->info("Processed {$processed} listings, converted {$converted}, already stored {$skipped}.");
    $this->line('Run again with a higher --offset until converted stays 0.');

    return 0;
})->purpose('Download TREB cover images and store as local WebP files');
