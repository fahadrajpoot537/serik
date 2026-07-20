<?php

namespace App\Support;

/**
 * Explicit HIGH/LOW queue names. Never returns Laravel's "default".
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
}
