<?php

namespace App\Support;

/**
 * Normalize Botble menu node URLs for front-end navigation.
 *
 * Menu items are often stored as path-relative values (e.g. "properties") which
 * break on nested routes such as /on/.../map/... .
 */
final class MenuUrl
{
    public static function resolve(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '' || $url === '#') {
            return $url;
        }

        if (
            str_starts_with($url, 'javascript:')
            || str_starts_with($url, 'mailto:')
            || str_starts_with($url, 'tel:')
        ) {
            return $url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return CanonicalUrl::normalize($url);
        }

        return CanonicalUrl::normalize(url('/' . ltrim($url, '/')));
    }
}
