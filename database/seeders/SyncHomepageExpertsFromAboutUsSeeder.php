<?php

namespace Database\Seeders;

use App\Support\HomepageFragmentCache;
use App\Support\HomepageResponseCache;
use Botble\Page\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Copy About Us “Meet Our Experts” account_ids onto the homepage agents shortcode.
 *
 * php artisan db:seed --class="Database\\Seeders\\SyncHomepageExpertsFromAboutUsSeeder" --force
 */
class SyncHomepageExpertsFromAboutUsSeeder extends Seeder
{
    public function run(): void
    {
        $about = Page::query()->where('name', 'About Us')->first();
        if (! $about) {
            $this->command?->error('About Us page not found.');

            return;
        }

        $idsCsv = $this->extractMeetOurExpertsAccountIds((string) $about->content);
        if ($idsCsv === null || $idsCsv === '') {
            $this->command?->error('Could not find Meet Our Experts account_ids on About Us.');

            return;
        }

        $homepageId = (int) theme_option('homepage_id');
        $pages = Page::query()
            ->when($homepageId > 0, fn ($q) => $q->where('id', $homepageId))
            ->orWhere(function ($q) {
                $q->where('name', 'like', 'Homepage%')
                    ->where('content', 'like', '%Meet Our Experts%');
            })
            ->get();

        if ($pages->isEmpty()) {
            $this->command?->error('Homepage page not found.');

            return;
        }

        $updated = 0;
        foreach ($pages as $page) {
            $content = (string) $page->content;
            if ($content === '' || ! str_contains($content, '[agents')) {
                continue;
            }

            $next = preg_replace(
                '/(\[agents\b[^\]]*?\btitle="Meet Our Experts"[^\]]*?\saccount_ids=")[^"]*(")/s',
                '$1' . $idsCsv . '$2',
                $content,
                1,
                $count
            );

            if (! is_string($next) || $count < 1) {
                $next = preg_replace(
                    '/(\[agents\b[^\]]*?\saccount_ids=")[^"]*(")/s',
                    '$1' . $idsCsv . '$2',
                    $content,
                    1,
                    $count2
                );
                if (! is_string($next) || $count2 < 1) {
                    continue;
                }
            }

            if ($next === $content) {
                $this->command?->info("Page #{$page->id} ({$page->name}) already synced → {$idsCsv}");
                continue;
            }

            $page->content = $next;
            $page->save();
            $updated++;
            $this->command?->info("Updated page #{$page->id} ({$page->name}) account_ids → {$idsCsv}");
        }

        if ($updated < 1) {
            $this->command?->warn('No homepage agents shortcode needed changes (already in sync or not found).');
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

    private function extractMeetOurExpertsAccountIds(string $content): ?string
    {
        if (preg_match(
            '/\[agents\b[^\]]*?\btitle="Meet Our Experts"[^\]]*?\saccount_ids="([^"]+)"/s',
            $content,
            $m
        )) {
            return trim($m[1]);
        }

        if (preg_match('/\[agents\b[^\]]*?\saccount_ids="([^"]+)"/s', $content, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}
