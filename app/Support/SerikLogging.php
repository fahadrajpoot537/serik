<?php

namespace App\Support;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;

/**
 * IIS + Windows queue workers often lock storage/logs/laravel*.log.
 * A successful write to an unrelated probe file is NOT enough — Monolog must
 * be able to append to the configured daily/single log path, or Log:: calls
 * throw UnexpectedValueException and become HTTP 500 (especially admin).
 */
final class SerikLogging
{
    private static ?bool $writable = null;

    public static function ensureWritableOrFallback(Application $app): void
    {
        if (self::$writable === true) {
            return;
        }

        if (self::$writable === false) {
            self::forceErrorlog($app);

            return;
        }

        try {
            $logDir = storage_path('logs');
            if (! is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }

            $probe = $logDir.DIRECTORY_SEPARATOR.'.write_probe';
            $probeOk = @file_put_contents($probe, (string) time()) !== false;
            if ($probeOk) {
                @unlink($probe);
            }

            // Probe the real Monolog targets. On Windows, a deleted-but-still-open
            // laravel-YYYY-MM-DD.log can block recreate while .write_probe works.
            $targets = [
                $logDir.DIRECTORY_SEPARATOR.'laravel.log',
                $logDir.DIRECTORY_SEPARATOR.'laravel-'.date('Y-m-d').'.log',
            ];

            $anyTargetOk = false;
            foreach ($targets as $target) {
                $ok = @file_put_contents(
                    $target,
                    '['.date('Y-m-d H:i:s').'] production.DEBUG: serik log writability probe'.PHP_EOL,
                    FILE_APPEND
                ) !== false;
                if ($ok) {
                    $anyTargetOk = true;
                }
            }

            if ($probeOk && $anyTargetOk) {
                self::$writable = true;

                return;
            }
        } catch (\Throwable) {
            // fall through
        }

        self::$writable = false;
        self::forceErrorlog($app);
    }

    private static function forceErrorlog(Application $app): void
    {
        config([
            'logging.default' => 'errorlog',
            'logging.channels.stack.channels' => ['errorlog'],
            'logging.channels.stack.ignore_exceptions' => true,
        ]);

        try {
            $app->forgetInstance('log');
            Log::clearResolvedInstances();
        } catch (\Throwable) {
            // ignore
        }
    }
}
