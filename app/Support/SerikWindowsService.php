<?php

namespace App\Support;

/**
 * Probe Windows services used by Serik queue workers (NSSM).
 */
final class SerikWindowsService
{
    /** @var array<string, string> */
    public const QUEUE_SERVICES = [
        'high' => 'SerikQueueHigh',
        'images' => 'SerikQueueImages',
        'low' => 'SerikQueueLow',
    ];

    public static function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    public static function serviceName(string $queueLabel): ?string
    {
        return self::QUEUE_SERVICES[$queueLabel] ?? null;
    }

    /**
     * @return 'RUNNING'|'STOPPED'|'PAUSED'|'START_PENDING'|'STOP_PENDING'|'NOT_INSTALLED'|'UNKNOWN'
     */
    public static function state(string $serviceName): string
    {
        if (! self::isWindows()) {
            return 'UNKNOWN';
        }

        $serviceName = trim($serviceName);
        if ($serviceName === '') {
            return 'UNKNOWN';
        }

        $output = [];
        $exitCode = 1;
        @exec('sc query ' . escapeshellarg($serviceName) . ' 2>nul', $output, $exitCode);

        if ($exitCode !== 0 || $output === []) {
            return 'NOT_INSTALLED';
        }

        $text = implode("\n", $output);

        if (preg_match('/STATE\s*:\s*\d+\s+(\w+)/i', $text, $matches)) {
            return strtoupper($matches[1]);
        }

        return 'UNKNOWN';
    }

    public static function isRunning(string $serviceName): bool
    {
        return self::state($serviceName) === 'RUNNING';
    }

    /**
     * @return array<string, string> label => state
     */
    public static function queueServiceStates(): array
    {
        $states = [];
        foreach (self::QUEUE_SERVICES as $label => $serviceName) {
            $states[$label] = self::state($serviceName);
        }

        return $states;
    }

    public static function imagesWorkerCount(): int
    {
        return self::isRunning(self::QUEUE_SERVICES['images']) ? 1 : 0;
    }
}
