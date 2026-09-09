<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Safe account-guard access for public AJAX (wishlist, map bundle, search).
 *
 * Real-estate registers the guard at runtime; config/auth.php also defines it
 * so config:cache and early requests never throw InvalidArgumentException.
 */
final class SerikAccountAuth
{
    public static function guardAvailable(): bool
    {
        return array_key_exists('account', (array) config('auth.guards', []));
    }

    public static function check(): bool
    {
        if (! self::guardAvailable()) {
            return false;
        }

        try {
            return Auth::guard('account')->check();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function id(): ?int
    {
        if (! self::guardAvailable()) {
            return null;
        }

        try {
            $id = Auth::guard('account')->id();

            return $id ? (int) $id : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function isAuthenticatedVisitor(): bool
    {
        return self::check() || Auth::check();
    }
}
