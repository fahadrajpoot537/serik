<?php

namespace App\Support;

use Botble\Base\Facades\BaseHelper;
use Botble\Page\Models\Page;
use Botble\Slug\Facades\SlugHelper;

final class SerikHomepage
{
    public static function isHomepageRequest(): bool
    {
        if (! app()->bound('request')) {
            return false;
        }

        $request = request();

        if ($request->is('/') || $request->path() === '') {
            return true;
        }

        try {
            $homepageId = (int) BaseHelper::getHomepageId();

            if ($homepageId <= 0) {
                return false;
            }

            $slug = SlugHelper::getSlug(null, SlugHelper::getPrefix(Page::class), Page::class);

            if ($slug && (int) $slug->reference_id === $homepageId) {
                return trim($request->path(), '/') === trim($slug->key, '/');
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    /**
     * @param  object|array<string, mixed>  $shortcode
     */
    public static function shouldServerRenderShortcode(string $name, object|array $shortcode): bool
    {
        if (! self::isHomepageRequest() || $name !== 'properties') {
            return false;
        }

        $style = is_array($shortcode)
            ? ($shortcode['style'] ?? null)
            : ($shortcode->style ?? null);

        return (string) $style === '5';
    }
}
