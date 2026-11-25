<?php

namespace App\Support;

use Laravel\Octane\PosixExtension;

/**
 * Windows stand-in for Octane's PosixExtension (posix_kill is unavailable).
 * Signal 0 = process existence check; other signals attempt a graceful then forced stop.
 */
class WindowsPosixExtension extends PosixExtension
{
    public function kill(int $processId, int $signal): bool
    {
        if ($processId <= 0) {
            return false;
        }

        if ($signal === 0) {
            if (\function_exists('posix_kill')) {
                return @\posix_kill($processId, 0);
            }

            if (\PHP_OS_FAMILY !== 'Windows') {
                return false;
            }

            $out = [];
            $code = 0;
            @\exec('tasklist /FI "PID eq '.$processId.'" /NH 2>NUL', $out, $code);

            $joined = \implode("\n", $out);

            return $joined !== '' && \str_contains($joined, (string) $processId);
        }

        if (\function_exists('posix_kill')) {
            return @\posix_kill($processId, $signal);
        }

        if (\PHP_OS_FAMILY !== 'Windows') {
            return false;
        }

        $out = [];
        $code = 0;
        @\exec('taskkill /PID '.$processId.' /T 2>NUL', $out, $code);

        if ($code !== 0) {
            @\exec('taskkill /PID '.$processId.' /T /F 2>NUL', $out, $code);
        }

        return $code === 0;
    }
}
