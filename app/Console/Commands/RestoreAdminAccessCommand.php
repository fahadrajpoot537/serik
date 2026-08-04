<?php

namespace App\Console\Commands;

use Botble\ACL\Models\Role;
use Botble\ACL\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RestoreAdminAccessCommand extends Command
{
    protected $signature = 'serik:restore-admin-access
        {--dry-run : Show what would change without writing}';

    protected $description = 'Restore full admin sidebar menus by syncing Admin role permissions to Main Admin users and ensuring plugins are activated';

    /** @var list<string> */
    protected array $expectedPlugins = [
        'language',
        'language-advanced',
        'ads',
        'analytics',
        'announcement',
        'audit-log',
        'backup',
        'blog',
        'captcha',
        'career',
        'contact',
        'cookie-consent',
        'faq',
        'location',
        'newsletter',
        'payment',
        'real-estate',
        'rss-feed',
        'social-login',
        'testimonial',
        'translation',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('DB=' . DB::connection()->getDatabaseName() . ' @ ' . (string) DB::connection()->getConfig('host'));

        $adminRole = Role::query()->where('name', 'Admin')->orderBy('id')->first()
            ?: Role::query()->orderBy('id')->first();

        if (! $adminRole) {
            $this->error('No Admin role found.');

            return self::FAILURE;
        }

        $fullPerms = $adminRole->permissions;
        if (is_string($fullPerms)) {
            $fullPerms = json_decode($fullPerms, true) ?: [];
        }

        if (! is_array($fullPerms) || count($fullPerms) < 20) {
            $this->error('Admin role permissions look empty/invalid. Aborting.');

            return self::FAILURE;
        }

        $this->line('Source role: #' . $adminRole->id . ' ' . $adminRole->name . ' flags=' . count($fullPerms));

        $mainRole = Role::query()->where('name', 'Main Admin')->first();
        if ($mainRole) {
            $before = is_array($mainRole->permissions) ? count($mainRole->permissions) : 0;
            $this->line("Main Admin permissions: {$before} -> " . count($fullPerms));
            if (! $dryRun) {
                $mainRole->permissions = $fullPerms;
                $mainRole->save();
            }
        } else {
            $this->warn('Main Admin role not found (skipped role update).');
        }

        $updatedUsers = 0;
        User::query()->where('super_user', 0)->orderBy('id')->each(function (User $user) use ($fullPerms, $dryRun, &$updatedUsers): void {
            $perms = $fullPerms;
            $perms[ACL_ROLE_SUPER_USER] = false;
            $perms[ACL_ROLE_MANAGE_SUPERS] = false;

            $this->line(($dryRun ? '[dry-run] ' : '') . "Sync user #{$user->id} {$user->email}");
            if (! $dryRun) {
                $user->permissions = $perms;
                $user->save();
            }
            $updatedUsers++;
        });

        $this->info('Users synced: ' . $updatedUsers);

        $row = DB::table('settings')->where('key', 'activated_plugins')->first();
        $current = [];
        if ($row && ! empty($row->value)) {
            $decoded = json_decode((string) $row->value, true);
            if (is_array($decoded)) {
                $current = $decoded;
            }
        }

        $merged = array_values(array_unique(array_merge($current, $this->expectedPlugins)));
        sort($merged);

        $this->line('activated_plugins: ' . count($current) . ' -> ' . count($merged));
        if (! $dryRun) {
            if ($row) {
                DB::table('settings')->where('key', 'activated_plugins')->update([
                    'value' => json_encode($merged),
                ]);
            } else {
                DB::table('settings')->insert([
                    'key' => 'activated_plugins',
                    'value' => json_encode($merged),
                ]);
            }

            try {
                Cache::flush();
            } catch (\Throwable $e) {
                $this->warn('Cache flush: ' . $e->getMessage());
            }

            foreach (['optimize:clear', 'cache:clear', 'view:clear', 'config:clear', 'route:clear'] as $cmd) {
                try {
                    Artisan::call($cmd);
                } catch (\Throwable $e) {
                    $this->warn("Skip {$cmd}: " . $e->getMessage());
                }
            }
        }

        $this->info($dryRun
            ? 'Dry run complete — no changes written.'
            : 'Done. Log out and log back into admin to refresh the sidebar.');

        return self::SUCCESS;
    }
}
