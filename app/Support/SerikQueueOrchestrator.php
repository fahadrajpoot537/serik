<?php

namespace App\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Safe dispatch helpers. Never routes imports onto user-facing lanes.
 */
final class SerikQueueOrchestrator
{
    /**
     * @return list<string>
     */
    public static function protectedFromImports(): array
    {
        return SerikQueue::userFacing();
    }

    /**
     * Dispatch a job onto a lane with Memurai lock dedupe (schedule fan-out).
     *
     * @param  class-string  $jobClass
     * @param  array<int, mixed>  $constructorArgs
     */
    public static function dispatchOnce(
        string $dedupeKey,
        string $jobClass,
        string $queue,
        array $constructorArgs = [],
        int $cooldownSeconds = 55,
    ): bool {
        if ($queue !== SerikQueue::imports() && $queue === SerikQueue::imports()) {
            return false;
        }

        // Refuse accidental dispatch of import work onto a user-facing queue name.
        if (
            str_contains(strtolower($jobClass), 'import')
            && $queue !== SerikQueue::imports()
            && in_array($queue, self::protectedFromImports(), true)
        ) {
            Log::error('SerikQueueOrchestrator refused import-like job on user-facing lane', [
                'job' => $jobClass,
                'queue' => $queue,
            ]);

            return false;
        }

        $result = SerikQueueLock::dispatchGuard($dedupeKey, function () use ($jobClass, $queue, $constructorArgs) {
            /** @var ShouldQueue $job */
            $job = new $jobClass(...$constructorArgs);
            dispatch($job)->onQueue($queue);

            return true;
        }, $cooldownSeconds);

        return $result === true;
    }

    public static function importsDepth(): int
    {
        return SerikScheduler::queueDepthPublic(SerikQueue::imports());
    }

    public static function shouldDispatchImports(int $maxPending): bool
    {
        return self::importsDepth() < max(1, $maxPending);
    }
}
