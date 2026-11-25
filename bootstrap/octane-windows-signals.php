<?php

/**
 * Laravel Octane's SignalableCommandInterface references SIGINT/SIGTERM/SIGHUP.
 * Those constants exist only when the pcntl extension is loaded (not on Windows).
 * Define POSIX-compatible numeric values so `octane:start` can boot under Windows.
 * Actual Ctrl+C handling on Windows remains limited; use `octane:stop` / NSSM.
 */
if (\PHP_OS_FAMILY === 'Windows') {
    if (! \defined('SIGINT')) {
        \define('SIGINT', 2);
    }
    if (! \defined('SIGTERM')) {
        \define('SIGTERM', 15);
    }
    if (! \defined('SIGHUP')) {
        \define('SIGHUP', 1);
    }
}
