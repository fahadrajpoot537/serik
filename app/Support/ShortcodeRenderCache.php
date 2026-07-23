<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class ShortcodeRenderCache
{
    /**
     * Shortcodes whose rendered HTML depends on re_properties data.
     *
     * @var list<string>
     */
    private const PROPERTY_DEPENDENT = [
        'properties',
        'property-categories',
        'location',
        'agents',
    ];

    public static function version(string $shortcodeName): int
    {
        if (! self::isPropertyDependent($shortcodeName)) {
            return 1;
        }

        return (int) Cache::get(self::versionKey($shortcodeName), 1);
    }

    public static function bumpPropertyDependents(): void
    {
        foreach (self::PROPERTY_DEPENDENT as $name) {
            Cache::forever(self::versionKey($name), self::version($name) + 1);
        }
    }

    public static function isPropertyDependent(string $shortcodeName): bool
    {
        return in_array($shortcodeName, self::PROPERTY_DEPENDENT, true);
    }

    private static function versionKey(string $shortcodeName): string
    {
        return 'shortcode_render_version_' . $shortcodeName;
    }
}
