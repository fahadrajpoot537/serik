<?php

namespace App\Support;

/**
 * Explicit queue names. Never returns Laravel's "default" for HIGH/LOW lanes.
 */
final class SerikQueue
{
    public static function high(): string
    {
        $q = config('serik.queues.high');

        return (is_string($q) && $q !== '' && $q !== 'default') ? $q : 'high';
    }

    public static function low(): string
    {
        $q = config('serik.queues.low');

        return (is_string($q) && $q !== '' && $q !== 'default') ? $q : 'low';
    }

    public static function images(): string
    {
        $q = config('serik.queues.images');

        return (is_string($q) && $q !== '') ? $q : 'images';
    }

    public static function default(): string
    {
        $q = config('serik.queues.default');

        return (is_string($q) && $q !== '') ? $q : 'default';
    }

    public static function search(): string
    {
        $q = config('serik.queues.search');

        return (is_string($q) && $q !== '') ? $q : self::low();
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_unique([
            self::high(),
            self::default(),
            self::images(),
            self::low(),
        ]));
    }
}
