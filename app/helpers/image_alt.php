<?php

use App\Support\ImageAlt;

if (! function_exists('img_alt')) {
    /**
     * Resolve dynamic image alt text (see ImageAlt for priority rules).
     */
    function img_alt(
        ?string $explicit = null,
        ?string $mediaPathOrUrl = null,
        string|array|object|null $context = null,
        bool $decorative = false
    ): string {
        return ImageAlt::resolve($explicit, $mediaPathOrUrl, $context, $decorative);
    }
}
