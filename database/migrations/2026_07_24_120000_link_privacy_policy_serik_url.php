<?php

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
            ->where('content', 'like', '%serik.ca%')
            ->orderBy('id')
            ->each(function ($page): void {
                $content = (string) $page->content;
                $updated = self::normalizeSerikUrl($content);

                if ($updated !== $content) {
                    DB::table('pages')->where('id', $page->id)->update(['content' => $updated]);
                }
            });
    }

    public static function normalizeSerikUrl(string $content): string
    {
        $replacements = [
            'href="http://www.serik.ca/"' => 'href="https://serik.ca"',
            "href='http://www.serik.ca/'" => "href='https://serik.ca'",
            'href="http://www.serik.ca"' => 'href="https://serik.ca"',
            'href="https://www.serik.ca/"' => 'href="https://serik.ca"',
            'href="https://www.serik.ca"' => 'href="https://serik.ca"',
            'http://www.serik.ca/' => 'https://serik.ca/',
            'http://www.serik.ca' => 'https://serik.ca',
            'https://www.serik.ca/' => 'https://serik.ca/',
            'https://www.serik.ca' => 'https://serik.ca',
        ];

        $updated = str_replace(array_keys($replacements), array_values($replacements), $content);

        return preg_replace(
            '/(?<!https:\/\/)(?<!http:\/\/)(?<![\'">])\bwww\.serik\.ca\b(?!["\'])/i',
            '<a href="https://serik.ca" rel="noopener noreferrer">www.serik.ca</a>',
            $updated
        ) ?? $updated;
    }

    public function down(): void
    {
        // Intentionally left blank — reverting CMS link markup is not desirable.
    }
};
