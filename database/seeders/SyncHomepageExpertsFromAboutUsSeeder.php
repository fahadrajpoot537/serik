<?php

namespace Database\Seeders;

use App\Support\HomepageFragmentCache;
use App\Support\HomepageResponseCache;
use Botble\Page\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Copy About Us “Meet Our Experts” agents shortcode onto the homepage.
 *
 * Replaces the whole [agents]...[/agents] block (including optional &lt;shortcode&gt; wrapper)
 * so account_ids updates cannot corrupt the shortcode markup.
 *
 * php artisan db:seed --class="Database\\Seeders\\SyncHomepageExpertsFromAboutUsSeeder" --force
 */
class SyncHomepageExpertsFromAboutUsSeeder extends Seeder
{
    public function run(): void
    {
        $aboutContent = (string) DB::table('pages')->where('name', 'About Us')->value('content');
        if ($aboutContent === '') {
            $this->command?->error('About Us page not found.');

            return;
        }

        $aboutShortcode = $this->extractAgentsShortcodeInner($aboutContent);
        if ($aboutShortcode === null) {
            $this->command?->error('Could not find [agents] shortcode on About Us.');

            return;
        }

        // Keep homepage subtitle wording if present ("Our Teams"), otherwise About Us text.
        $homeShortcode = preg_replace(
            '/\bsubtitle="Our Team"/',
            'subtitle="Our Teams"',
            $aboutShortcode,
            1
        );
        if (! is_string($homeShortcode) || $homeShortcode === '') {
            $homeShortcode = $aboutShortcode;
        }

        $idsCsv = null;
        if (preg_match('/\baccount_ids="([^"]+)"/', $homeShortcode, $m)) {
            $idsCsv = $m[1];
        }

        $homepageId = (int) theme_option('homepage_id');
        if ($homepageId < 1) {
            $homepageId = (int) DB::table('pages')
                ->where('name', 'Homepage 2')
                ->value('id');
        }

        if ($homepageId < 1) {
            $this->command?->error('Homepage page id not found (theme_option homepage_id / Homepage 2).');

            return;
        }

        $pageIds = collect([$homepageId]);

        $updated = 0;
        foreach ($pageIds as $pageId) {
            $pageId = (int) $pageId;
            $content = (string) DB::table('pages')->where('id', $pageId)->value('content');
            if ($content === '') {
                continue;
            }

            $next = $this->replaceAgentsShortcode($content, $homeShortcode);
            if ($next === null || $next === $content) {
                // Repair corrupted remnant from older sync attempts.
                $repaired = $this->repairCorruptedAgentsBlock($content, $homeShortcode);
                if ($repaired === null || $repaired === $content) {
                    $this->command?->info("Page #{$pageId} already synced or no agents block.");
                    continue;
                }
                $next = $repaired;
            }

            DB::table('pages')->where('id', $pageId)->update([
                'content' => $next,
                'updated_at' => now(),
            ]);

            // Clear Eloquent model cache if Page was loaded elsewhere.
            Page::query()->where('id', $pageId)->get()->each->refresh();

            $updated++;
            $this->command?->info(
                "Updated page #{$pageId} Meet Our Experts"
                . ($idsCsv ? " account_ids → {$idsCsv}" : '')
            );
        }

        if ($updated < 1) {
            $this->command?->warn('No homepage agents shortcode changes applied.');
        }

        if (class_exists(HomepageFragmentCache::class)) {
            HomepageFragmentCache::bump('shortcode:agents');
            HomepageFragmentCache::bumpAll();
        }
        if (class_exists(HomepageResponseCache::class)) {
            HomepageResponseCache::bump();
        }

        $this->command?->info('Homepage/agent fragment caches bumped.');
    }

    private function extractAgentsShortcodeInner(string $content): ?string
    {
        if (preg_match('/\[agents\b[^\]]*\](?:\[\/agents\])?/i', $content, $m)) {
            $inner = $m[0];
            if (! str_ends_with(strtolower($inner), '[/agents]')) {
                $inner .= '[/agents]';
            }

            return $inner;
        }

        return null;
    }

    private function replaceAgentsShortcode(string $content, string $replacementInner): ?string
    {
        $patterns = [
            // Wrapped Botble storage form.
            '/<shortcode>\s*\[agents\b[\s\S]*?\[\/agents\]\s*<\/shortcode>/i',
            // Bare shortcode form.
            '/\[agents\b[\s\S]*?\[\/agents\]/i',
            // Self-closing rare form.
            '/<shortcode>\s*\[agents\b[^\]]*\]\s*<\/shortcode>/i',
        ];

        foreach ($patterns as $pattern) {
            $next = preg_replace(
                $pattern,
                '<shortcode>' . $replacementInner . '</shortcode>',
                $content,
                1,
                $count
            );
            if (is_string($next) && $count > 0) {
                return $next;
            }
        }

        return null;
    }

    /**
     * Fixes a previously corrupted block like:
     * <shortcode>6,25,..."][/agents]</shortcode>
     */
    private function repairCorruptedAgentsBlock(string $content, string $replacementInner): ?string
    {
        $patterns = [
            '/<shortcode>\s*\d[\d,\s]*"\s*items_per_row="[^"]*"\s*background_color="[^"]*"\s*enable_lazy_loading="[^"]*"\]\[\/agents\]\s*<\/shortcode>/i',
            '/<shortcode>\s*[^<\[]*?account_ids="[^"]*"[\s\S]*?\[\/agents\]\s*<\/shortcode>/i',
            '/<shortcode>\s*[^<\[]*?items_per_row="[^"]*"[\s\S]*?\[\/agents\]\s*<\/shortcode>/i',
        ];

        foreach ($patterns as $pattern) {
            $next = preg_replace(
                $pattern,
                '<shortcode>' . $replacementInner . '</shortcode>',
                $content,
                1,
                $count
            );
            if (is_string($next) && $count > 0) {
                return $next;
            }
        }

        return null;
    }
}
