<?php

/**
 * Remove Himmat Brar from Meet Our Experts (About Us + Homepage shortcodes)
 * and soft-remove the account from the public team (block + hide profile).
 *
 * Does NOT hard-delete by default (keeps login/history). Pass --delete to
 * permanently delete the re_accounts row after stripping shortcodes.
 *
 *   php scripts/remove-himmat-experts.php
 *   php scripts/remove-himmat-experts.php --delete
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\HomepageFragmentCache;
use App\Support\HomepageResponseCache;
use Botble\Page\Models\Page;
use Botble\RealEstate\Models\Account;
use Illuminate\Support\Facades\DB;

$hardDelete = in_array('--delete', $argv ?? [], true);

$accounts = Account::query()
    ->where(function ($q) {
        $q->whereIn('username', ['himmat', 'himmatbrar', 'himmat-brar', 'himmat.brar'])
            ->orWhereRaw("LOWER(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) LIKE ?", ['%himmat%brar%'])
            ->orWhereRaw("LOWER(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) LIKE ?", ['%himmat%']);
    })
    ->orderBy('id')
    ->get();

if ($accounts->isEmpty()) {
    fwrite(STDERR, "Himmat Brar not found in re_accounts on this database.\n");
    fwrite(STDERR, "Still stripping any leftover himmat ids from agents shortcodes…\n");
}

$removeIds = $accounts->pluck('id')->map(static fn ($id) => (int) $id)->all();
echo 'Matched accounts: ' . ($removeIds === [] ? '(none)' : implode(',', $removeIds)) . PHP_EOL;
foreach ($accounts as $account) {
    echo "  #{$account->id} " . trim($account->first_name . ' ' . $account->last_name)
        . ' username=' . ($account->username ?? '') . PHP_EOL;
}

$homepageId = (int) theme_option('homepage_id');
$pages = Page::query()
    ->where(function ($q) use ($homepageId) {
        $q->where('content', 'like', '%[agents%')
            ->orWhere('content', 'like', '%Meet Our Experts%');
        if ($homepageId > 0) {
            $q->orWhere('id', $homepageId);
        }
        $q->orWhereIn('name', ['About Us', 'Homepage 1', 'Homepage 2', 'Homepage 3', 'Homepage 4', 'Homepage 5']);
    })
    ->get()
    ->unique('id');

$updatedPages = 0;
foreach ($pages as $page) {
    $content = (string) DB::table('pages')->where('id', $page->id)->value('content');
    if ($content === '' || ! preg_match('/\[agents\b/i', $content)) {
        continue;
    }

    $next = preg_replace_callback(
        '/(\[agents\b[^\]]*?\saccount_ids=")([^"]+)(")/i',
        static function (array $m) use ($removeIds): string {
            $ids = array_values(array_filter(array_map('intval', explode(',', $m[2]))));
            if ($removeIds !== []) {
                $ids = array_values(array_filter($ids, static fn (int $id) => ! in_array($id, $removeIds, true)));
            }
            return $m[1] . implode(',', $ids) . $m[3];
        },
        $content,
        -1,
        $count
    );

    if (! is_string($next) || $count < 1 || $next === $content) {
        continue;
    }

    DB::table('pages')->where('id', $page->id)->update([
        'content' => $next,
        'updated_at' => now(),
    ]);
    $updatedPages++;
    echo "Updated page #{$page->id} {$page->name}\n";
}

foreach ($accounts as $account) {
    if ($hardDelete) {
        $id = (int) $account->id;
        DB::table('slugs')->where('reference_type', Account::class)->where('reference_id', $id)->delete();
        $account->delete();
        echo "Deleted account #{$id}\n";
        continue;
    }

    $account->is_public_profile = false;
    if (\Illuminate\Support\Facades\Schema::hasColumn('re_accounts', 'is_featured')) {
        $account->is_featured = false;
    }
    $account->blocked_at = $account->blocked_at ?: now();
    $account->blocked_reason = $account->blocked_reason ?: 'Removed from Meet Our Experts team lineup';
    $account->save();
    echo "Hidden/blocked account #{$account->id} (use --delete to hard-delete)\n";
}

try {
    if (class_exists(HomepageFragmentCache::class)) {
        HomepageFragmentCache::bump('shortcode:agents');
    }
    if (class_exists(HomepageResponseCache::class)) {
        HomepageResponseCache::bump();
    }
} catch (Throwable) {
}

try {
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    echo "cache:clear OK\n";
} catch (Throwable $e) {
    echo 'cache:clear skipped: ' . $e->getMessage() . PHP_EOL;
}

echo "Done. Pages updated={$updatedPages}\n";
echo "Admin team members: /admin/accounts (Real Estate → Accounts)\n";
echo "About Us lineup: Admin → Pages → About Us → edit [agents] account_ids shortcode\n";
