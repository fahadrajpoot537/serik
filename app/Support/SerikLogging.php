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

        // Remember a successful open so IIS/PHP does not touch the log on every hit.
        // Appending a line here locked laravel.log and flooded production with probes.
        $stamp = storage_path('framework'.DIRECTORY_SEPARATOR.'serik-log-writable.stamp');
        if (is_file($stamp) && (time() - (int) @filemtime($stamp)) < 600) {
            self::$writable = true;

            return;
        }

        try {
            $logDir = storage_path('logs');
            if (! is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }

            // Open the real Monolog targets without writing. On Windows, a
            // deleted-but-still-open laravel-YYYY-MM-DD.log can block recreate
            // while an unrelated probe file still succeeds.
            $targets = [
                $logDir.DIRECTORY_SEPARATOR.'laravel.log',
                $logDir.DIRECTORY_SEPARATOR.'laravel-'.date('Y-m-d').'.log',
            ];

            $anyTargetOk = false;
            foreach ($targets as $target) {
                $handle = @fopen($target, 'ab');
                if ($handle === false) {
                    continue;
                }
                @fclose($handle);
                $anyTargetOk = true;
                break;
            }

            if ($anyTargetOk) {
                self::$writable = true;
                $stampDir = dirname($stamp);
                if (! is_dir($stampDir)) {
                    @mkdir($stampDir, 0775, true);
                }
                @touch($stamp);

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
