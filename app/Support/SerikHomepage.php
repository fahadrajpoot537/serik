<?php

namespace App\Support;

use Botble\Base\Facades\BaseHelper;
use Botble\Page\Models\Page;
use Botble\Slug\Facades\SlugHelper;

final class SerikHomepage
{
    /**
     * Homepage shortcodes rendered server-side (skip AJAX lazy placeholders).
     *
     * @var array<string, list<string>>
     */
    private const SERVER_RENDER_STYLES = [
        'properties' => ['5'],
        'location' => ['2'],
        'services' => ['3'],
    ];

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
        if (! self::isHomepageRequest()) {
            return false;
        }

        $allowedStyles = self::SERVER_RENDER_STYLES[$name] ?? null;

        if ($allowedStyles === null) {
            return false;
        }

        $style = is_array($shortcode)
            ? (string) ($shortcode['style'] ?? '')
            : (string) ($shortcode->style ?? '');

        return in_array($style, $allowedStyles, true);
    }
}
