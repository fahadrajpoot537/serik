<?php

/**
 * Make Sadaqat Sheikh public, set "President and Brokerage", list first on About + Home.
 *
 *   php scripts/fix-sadaqat-experts.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\HomepageFragmentCache;
use App\Support\HomepageResponseCache;
use Botble\Page\Models\Page;
use Botble\RealEstate\Models\Account;
use Illuminate\Support\Facades\DB;

$account = Account::query()
    ->where(function ($q) {
        $q->whereIn('username', ['sadaqat', 'sadaquat', 'sadaqatsheikh', 'sadaqat-sheikh'])
            ->orWhereRaw("LOWER(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) LIKE ?", ['%sadaqat%'])
            ->orWhereRaw("LOWER(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) LIKE ?", ['%sadaquat%']);
    })
    ->orderBy('id')
    ->first();

if (! $account) {
    fwrite(STDERR, "ERROR: Sadaqat Sheikh not found in re_accounts.\n");
    fwrite(STDERR, "Admin → Real Estate → Accounts → create him, then re-run this script.\n");
    exit(1);
}

$wantedTitle = 'President and Broker';
$account->is_public_profile = true;
$account->blocked_at = null;
$account->blocked_reason = null;
$account->professional_title = $wantedTitle;
$account->save();

echo "Updated account #{$account->id} {$account->name}\n";
echo "  professional_title={$wantedTitle}\n";
echo "  is_public_profile=1\n";
echo "  username=" . ($account->username ?? '') . "\n";

$sadaqatId = (int) $account->id;
$fallback = [26, 25, 4, 24, 14, 18, 19, 15, 20, 22, 21, 23, 17, 16];
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

foreach ($pages as $page) {
    $content = (string) DB::table('pages')->where('id', $page->id)->value('content');
    if ($content === '') {
        continue;
    }

    $ids = [];
    if (preg_match('/\[agents\b[^\]]*?\saccount_ids="([^"]+)"/i', $content, $m)) {
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $m[1])))));
    }
    if ($ids === []) {
        $ids = $fallback;
        echo "WARN page #{$page->id}: used fallback ids\n";
    }

    $ids = array_values(array_filter($ids, static fn (int $id) => $id !== $sadaqatId));
    array_unshift($ids, $sadaqatId);

    $isAbout = stripos((string) $page->name, 'about') !== false;
    $subtitle = $isAbout ? 'Our Team' : 'Our Teams';
    $lazy = $isAbout ? 'no' : 'yes';
    $inner = sprintf(
        '[agents style="1" title="Meet Our Experts" subtitle="%s" account_ids="%s" items_per_row="4" background_color="transparent" enable_lazy_loading="%s"][/agents]',
        $subtitle,
        implode(',', $ids),
        $lazy
    );
    $wrapped = '<shortcode>' . $inner . '</shortcode>';

    $patterns = [
        '/<shortcode>\s*\[agents\b[\s\S]*?\[\/agents\]\s*<\/shortcode>/i',
        '/\[agents\b[\s\S]*?\[\/agents\]/i',
    ];
    $next = null;
    foreach ($patterns as $pattern) {
        $try = preg_replace($pattern, $wrapped, $content, 1, $count);
        if (is_string($try) && $count > 0) {
            $next = $try;
            break;
        }
    }
    if ($next === null) {
        echo "WARN page #{$page->id} ({$page->name}): agents block not found\n";
        continue;
    }

    DB::table('pages')->where('id', $page->id)->update([
        'content' => $next,
        'updated_at' => now(),
    ]);
    echo "Updated page #{$page->id} ({$page->name}) account_ids=" . implode(',', $ids) . "\n";
}

if (class_exists(HomepageFragmentCache::class)) {
    HomepageFragmentCache::bump('shortcode:agents');
    HomepageFragmentCache::bumpAll();
}
if (class_exists(HomepageResponseCache::class)) {
    HomepageResponseCache::bump();
}

echo "Caches bumped. Hard-refresh About Us + homepage.\n";
echo "Profile URL should be: /agents/" . ($account->username ?: 'sadaqat') . "\n";
