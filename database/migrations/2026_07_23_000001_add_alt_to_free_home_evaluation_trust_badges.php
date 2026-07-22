<?php

use App\Support\TrustBadgeImageAlt;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('pages')) {
            return;
        }

        DB::table('pages')
            ->select(['id', 'content'])
            ->where(function ($query): void {
                $query->where('content', 'like', '%spam.png%')
                    ->orWhere('content', 'like', '%no-obligations.png%')
                    ->orWhere('content', 'like', '%safe-info.png%');
            })
            ->orderBy('id')
            ->each(function ($page): void {
                $updated = TrustBadgeImageAlt::applyToEncodedPageContent((string) $page->content);

                if ($updated !== $page->content) {
                    DB::table('pages')->where('id', $page->id)->update(['content' => $updated]);
                }
            });
    }

    public function down(): void
    {
        // Intentionally left blank — restoring missing alt attributes is not desirable.
    }
};
