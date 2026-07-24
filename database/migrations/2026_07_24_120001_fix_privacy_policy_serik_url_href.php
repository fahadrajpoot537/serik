<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('pages')) {
            return;
        }

        $migration = require __DIR__ . '/2026_07_24_120000_link_privacy_policy_serik_url.php';

        DB::table('pages')
            ->select(['id', 'content'])
            ->where('content', 'like', '%serik.ca%')
            ->orderBy('id')
            ->each(function ($page) use ($migration): void {
                $content = (string) $page->content;
                $updated = $migration::normalizeSerikUrl($content);

                if ($updated !== $content) {
                    DB::table('pages')->where('id', $page->id)->update(['content' => $updated]);
                }
            });
    }

    public function down(): void
    {
        // Intentionally left blank.
    }
};
