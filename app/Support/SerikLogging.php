<?php

namespace App\Support;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;

final class SerikLogging
{
    private static ?bool $writable = null;

    public static function ensureWritableOrFallback(Application $app): void
    {
        if (self::$writable === true) {
            return;
        }

        try {
            $logDir = storage_path('logs');
            if (! is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }

            $probe = $logDir.DIRECTORY_SEPARATOR.'.write_probe';
            $ok = @file_put_contents($probe, (string) time()) !== false;
            if ($ok) {
                @unlink($probe);
                self::$writable = true;

                return;
            }
        } catch (\Throwable) {
            // fall through
        }

        self::$writable = false;
        config(['logging.default' => 'errorlog']);
        $app->forgetInstance('log');
        Log::clearResolvedInstances();
    }
}
