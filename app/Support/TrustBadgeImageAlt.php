<?php

namespace App\Support;

/**
 * Decorative trust-badge icons on the Free Home Evaluation page.
 * Each image is paired with a visible heading and description in the markup.
 */
final class TrustBadgeImageAlt
{
    private const DECORATIVE_FILES = [
        'spam.png',
        'no-obligations.png',
        'safe-info.png',
    ];

    public static function apply(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        foreach (self::DECORATIVE_FILES as $file) {
            $html = preg_replace_callback(
                '/<img\b([^>]*' . preg_quote($file, '/') . '[^>]*)>/i',
                static function (array $matches): string {
                    $attrs = $matches[1];

                    if (preg_match('/\salt\s*=/i', $attrs)) {
                        return '<img' . $attrs . '>';
                    }

                    return '<img' . $attrs . ' alt="">';
                },
                $html
            ) ?? $html;
        }

        return $html;
    }

    public static function applyToEncodedPageContent(string $content): string
    {
        if ($content === '') {
            return $content;
        }

        foreach (self::DECORATIVE_FILES as $file) {
            $search = [
                '&lt;img src="https://serik.ca/storage/' . $file . '" style="width:100px;"&gt;',
                '&lt;img src="/storage/' . $file . '" style="width:100px;"&gt;',
                '&lt;img src="' . $file . '" style="width:100px;"&gt;',
            ];

            $replace = [
                '&lt;img src="https://serik.ca/storage/' . $file . '" style="width:100px;" alt=""&gt;',
                '&lt;img src="/storage/' . $file . '" style="width:100px;" alt=""&gt;',
                '&lt;img src="' . $file . '" style="width:100px;" alt=""&gt;',
            ];

            $content = str_replace($search, $replace, $content);
        }

        return $content;
    }
}
