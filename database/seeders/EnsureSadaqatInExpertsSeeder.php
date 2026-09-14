<?php

namespace Database\Seeders;

use App\Support\HomepageFragmentCache;
use App\Support\HomepageResponseCache;
use Botble\Page\Models\Page;
use Botble\RealEstate\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Make Sadaqat public, repair corrupted [agents] shortcodes, and list him on About/Home.
 *
 * Also fixes the PHP preg_replace pitfall where '$1' . '26...' becomes backref $126
 * and strips the shortcode opening (leaving raw "6,25,..."][/agents]" on the page).
 *
 * php artisan db:seed --class="Database\\Seeders\\EnsureSadaqatInExpertsSeeder" --force
 */
class EnsureSadaqatInExpertsSeeder extends Seeder
{
    /** Fallback lineup if shortcode is too damaged to parse (live About/Home order). */
    private const FALLBACK_IDS = [26, 25, 4, 24, 14, 18, 19, 15, 20, 22, 21, 23, 17, 16];

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

        $sadaqatId = (int) $account->id;
        $homepageId = (int) theme_option('homepage_id');

        $pages = Page::query()
            ->where(function ($q) use ($homepageId) {
                $q->whereIn('name', ['About Us', 'Homepage 2']);
                if ($homepageId > 0) {
                    $q->orWhere('id', $homepageId);
                }
            })
            ->get()
            ->unique('id');

        $updatedPages = 0;
        foreach ($pages as $page) {
            $content = (string) DB::table('pages')->where('id', $page->id)->value('content');
            if ($content === '') {
                continue;
            }

            $ids = $this->extractAccountIds($content);
            if ($ids === []) {
                $ids = self::FALLBACK_IDS;
                $this->command?->warn("Page #{$page->id}: using fallback account_ids (shortcode was damaged).");
            }

            if (! in_array($sadaqatId, $ids, true)) {
                $ids[] = $sadaqatId;
            }

            $isAbout = stripos((string) $page->name, 'about') !== false;
            $subtitle = $isAbout ? 'Our Team' : 'Our Teams';
            // About Us must render eagerly so Swiper + circular cards match homepage
            // (homepage-premium.css is home-only; About relies on site-chrome + inline init).
            $lazy = $isAbout ? 'no' : 'yes';
            $inner = sprintf(
                '[agents style="1" title="Meet Our Experts" subtitle="%s" account_ids="%s" items_per_row="4" background_color="transparent" enable_lazy_loading="%s"][/agents]',
                $subtitle,
                implode(',', $ids),
                $lazy
            );
            $wrapped = '<shortcode>' . $inner . '</shortcode>';

            $next = $this->replaceAgentsBlock($content, $wrapped);
            if ($next === null || $next === $content) {
                $this->command?->warn("Page #{$page->id} ({$page->name}): could not locate agents block to repair.");
                continue;
            }

            DB::table('pages')->where('id', $page->id)->update([
                'content' => $next,
                'updated_at' => now(),
            ]);
            $updatedPages++;
            $this->command?->info(
                "Repaired page #{$page->id} ({$page->name}) account_ids → " . implode(',', $ids)
            );
        }

        if ($updatedPages < 1) {
            $this->command?->warn('No pages updated.');
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

    /**
     * @return list<int>
     */
    private function extractAccountIds(string $content): array
    {
        if (preg_match('/\[agents\b[^\]]*?\saccount_ids="([^"]+)"/i', $content, $m)) {
            return $this->parseIdsCsv($m[1]);
        }

        // Corrupted remnant: 6,25,...31" items_per_row=... (leading digit(s) of first id eaten)
        if (preg_match('/(?:^|[>\s])(\d[\d,\s]*)"\s*items_per_row="/i', $content, $m)) {
            $ids = $this->parseIdsCsv($m[1]);
            // Known corruption from $126 backref: first id 26 became "6"
            if ($ids !== [] && $ids[0] === 6) {
                $ids[0] = 26;
            }

            return $ids;
        }

        return [];
    }

    /**
     * @return list<int>
     */
    private function parseIdsCsv(string $csv): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', explode(',', $csv)),
            static fn (int $id) => $id > 0
        )));
    }

    private function replaceAgentsBlock(string $content, string $replacementWrapped): ?string
    {
        $patterns = [
            // Intact Botble wrapper
            '/<shortcode>\s*\[agents\b[\s\S]*?\[\/agents\]\s*<\/shortcode>/i',
            // Bare shortcode
            '/\[agents\b[\s\S]*?\[\/agents\]/i',
            // Corrupted remnant inside <shortcode>…[/agents]</shortcode>
            '/<shortcode>\s*\d[\d,\s]*"\s*items_per_row="[^"]*"\s*background_color="[^"]*"\s*enable_lazy_loading="[^"]*"\]\s*\[\/agents\]\s*<\/shortcode>/i',
            '/<shortcode>\s*[^<\[]*?items_per_row="[^"]*"[\s\S]*?\[\/agents\]\s*<\/shortcode>/i',
            // Bare corrupted remnant (no opening [agents)
            '/\d[\d,\s]*"\s*items_per_row="[^"]*"\s*background_color="[^"]*"\s*enable_lazy_loading="[^"]*"\]\s*\[\/agents\]/i',
        ];

        foreach ($patterns as $pattern) {
            $next = preg_replace($pattern, $replacementWrapped, $content, 1, $count);
            if (is_string($next) && $count > 0) {
                return $next;
            }
        }

        return null;
    }
}
