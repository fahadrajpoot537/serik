<?php

namespace Database\Seeders;

use App\Support\HomepageFragmentCache;
use App\Support\HomepageResponseCache;
use Botble\Page\Models\Page;
use Botble\RealEstate\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ensure Sadaqat Sheikh is public and listed in Meet Our Experts (About Us + Homepage).
 *
 * php artisan db:seed --class="Database\\Seeders\\EnsureSadaqatInExpertsSeeder" --force
 */
class EnsureSadaqatInExpertsSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::query()
            ->where(function ($q) {
                $q->whereIn('username', ['sadaqat', 'sadaquat', 'sadaqatsheikh', 'sadaqat-sheikh'])
                    ->orWhereRaw("LOWER(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) LIKE ?", ['%sadaqat%'])
                    ->orWhereRaw("LOWER(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) LIKE ?", ['%sadaquat%']);
            })
            ->orderBy('id')
            ->first();

        if (! $account) {
            $this->command?->error('Sadaqat Sheikh account not found in re_accounts. Create/publish the agent in admin first.');

            return;
        }

        $changed = false;
        if (! $account->is_public_profile) {
            $account->is_public_profile = true;
            $changed = true;
        }
        if ($account->blocked_at !== null) {
            $account->blocked_at = null;
            $account->blocked_reason = null;
            $changed = true;
        }
        if ($changed) {
            $account->save();
            $this->command?->info("Made account #{$account->id} ({$account->name}) public / unblocked.");
        } else {
            $this->command?->info("Account #{$account->id} ({$account->name}) already public.");
        }

        $id = (int) $account->id;
        $pages = Page::query()
            ->whereIn('name', ['About Us', 'Homepage 2'])
            ->orWhere('id', (int) theme_option('homepage_id'))
            ->get()
            ->unique('id');

        $updatedPages = 0;
        foreach ($pages as $page) {
            $content = (string) DB::table('pages')->where('id', $page->id)->value('content');
            if ($content === '' || ! str_contains($content, '[agents')) {
                continue;
            }

            if (! preg_match('/\[agents\b[^\]]*?\saccount_ids="([^"]+)"/i', $content, $m)) {
                continue;
            }

            $ids = array_values(array_filter(array_map('intval', explode(',', $m[1]))));
            if (in_array($id, $ids, true)) {
                $this->command?->info("Page #{$page->id} ({$page->name}) already includes #{$id}.");
                continue;
            }

            $ids[] = $id;
            $idsCsv = implode(',', $ids);
            $next = preg_replace(
                '/(\[agents\b[^\]]*?\saccount_ids=")[^"]*(")/i',
                '$1' . $idsCsv . '$2',
                $content,
                1,
                $count
            );

            if (! is_string($next) || $count < 1) {
                continue;
            }

            DB::table('pages')->where('id', $page->id)->update([
                'content' => $next,
                'updated_at' => now(),
            ]);
            $updatedPages++;
            $this->command?->info("Added #{$id} to page #{$page->id} ({$page->name}) → {$idsCsv}");
        }

        if ($updatedPages < 1) {
            $this->command?->warn('No page shortcodes needed ID append (already present or agents block missing).');
        }

        if (class_exists(HomepageFragmentCache::class)) {
            HomepageFragmentCache::bump('shortcode:agents');
            HomepageFragmentCache::bumpAll();
        }
        if (class_exists(HomepageResponseCache::class)) {
            HomepageResponseCache::bump();
        }

        $this->command?->info('Caches bumped. Hard-refresh About Us + homepage.');
    }
}
