<?php

namespace App\Support;

/**
 * Explicit queue names. Never returns Laravel's "default" for HIGH/LOW lanes.
 * Import / search lanes stay isolated so sold history never blocks live traffic.
 */
final class SerikQueue
{
    public static function critical(): string
    {
        $q = config('serik.queues.critical');

        return (is_string($q) && $q !== '') ? $q : 'critical';
    }

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

    public static function imports(): string
    {
        $q = config('serik.queues.imports');

        return (is_string($q) && $q !== '' && $q !== 'default') ? $q : 'imports';
    }

    public static function default(): string
    {
        $q = config('serik.queues.default');

        return (is_string($q) && $q !== '') ? $q : 'default';
    }

    /**
     * Physical queue used by SearchBatchJob today.
     * Default remains `low` for backward compatibility unless SERIK_QUEUE_SEARCH is set.
     */
    public static function search(): string
    {
        $q = config('serik.queues.search');

        return (is_string($q) && $q !== '') ? $q : self::low();
    }

    /**
     * Dedicated search-index lane name (orchestration target).
     * Opt-in: set SERIK_QUEUE_SEARCH=search-index to move SearchBatchJob here.
     */
    public static function searchIndex(): string
    {
        $q = config('serik.queues.search_index');

        return (is_string($q) && $q !== '') ? $q : 'search-index';
    }

    public static function cacheRefresh(): string
    {
        $q = config('serik.queues.cache_refresh');

        return (is_string($q) && $q !== '') ? $q : 'cache-refresh';
    }

    public static function ghl(): string
    {
        $q = config('serik.queues.ghl');

        return (is_string($q) && $q !== '') ? $q : 'ghl';
    }

    public static function notifications(): string
    {
        $q = config('serik.queues.notifications');

        return (is_string($q) && $q !== '') ? $q : 'notifications';
    }

    public static function emails(): string
    {
        $q = config('serik.queues.emails');

        return (is_string($q) && $q !== '') ? $q : 'emails';
    }

    /**
     * Lanes that must never share a worker process with imports.
     *
     * @return list<string>
     */
    public static function userFacing(): array
    {
        return array_values(array_unique([
            self::critical(),
            self::high(),
            self::default(),
            self::emails(),
            self::notifications(),
            self::search(),
            self::searchIndex(),
            self::low(),
            self::images(),
            self::cacheRefresh(),
            self::ghl(),
        ]));
    }

    /**
     * Heavy / batch lanes isolated from request path.
     *
     * @return list<string>
     */
    public static function heavy(): array
    {
        return array_values(array_unique([
            self::imports(),
        ]));
    }

    /**
     * Canonical label => physical queue name map for monitoring.
     *
     * @return array<string, string>
     */
    public static function laneMap(): array
    {
        return [
            'critical' => self::critical(),
            'high' => self::high(),
            'default' => self::default(),
            'imports' => self::imports(),
            'search-index' => self::searchIndex(),
            'search' => self::search(),
            'ghl' => self::ghl(),
            'emails' => self::emails(),
            'notifications' => self::notifications(),
            'images' => self::images(),
            'cache-refresh' => self::cacheRefresh(),
            'low' => self::low(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_unique(array_values(self::laneMap())));
    }
}
