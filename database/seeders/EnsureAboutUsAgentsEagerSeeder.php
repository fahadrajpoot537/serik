<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * About Us "Meet Our Experts" should render eagerly (same markup path as homepage),
 * not via shortcode-lazy-loading placeholder.
 */
class EnsureAboutUsAgentsEagerSeeder extends Seeder
{
    public function run(): void
    {
        $page = DB::table('pages')
            ->where('name', 'About Us')
            ->orWhere('id', 10)
            ->orderBy('id')
            ->first();

        if (! $page || ! is_string($page->content) || $page->content === '') {
            return;
        }

        $content = $page->content;
        $updated = preg_replace(
            '/(\[agents\b[^\]]*?\benable_lazy_loading=")yes(")/i',
            '$1no$2',
            $content,
            1,
            $count
        );

        if ($count < 1) {
            $updated = preg_replace(
                '/(\[agents\b[^\]]*?)(\])/i',
                '$1 enable_lazy_loading="no"$2',
                $content,
                1,
                $count
            );
        }

        if (! is_string($updated) || $updated === $content || $count < 1) {
            return;
        }

        DB::table('pages')->where('id', $page->id)->update([
            'content' => $updated,
            'updated_at' => now(),
        ]);

        if (class_exists(\App\Support\HomepageFragmentCache::class)) {
            \App\Support\HomepageFragmentCache::bump('shortcode:agents');
        }
        if (class_exists(\App\Support\ShortcodeRenderCache::class)) {
            \App\Support\ShortcodeRenderCache::bump('agents');
        }
    }
}
