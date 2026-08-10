<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Lightweight helpers so schedule:run stays fast and heavy work stays on queue workers.
 */
final class SerikScheduler
{
    public static function highQueueDepth(): int
    {
        return self::queueDepth(SerikQueue::high());
    }

    public static function lowQueueDepth(): int
    {
        return self::queueDepth(SerikQueue::low());
    }

    public static function imagesQueueDepth(): int
    {
        return self::queueDepth(SerikQueue::images());
    }

    public static function defaultQueueDepth(): int
    {
        return self::queueDepth(SerikQueue::default());
    }

    public static function importsQueueDepth(): int
    {
        return self::queueDepth(SerikQueue::imports());
    }

    public static function ghlQueueDepth(): int
    {
        return self::queueDepth(SerikQueue::ghl());
    }

    /**
     * Public depth helper for orchestrator / monitoring.
     */
    public static function queueDepthPublic(string $queue): int
    {
        return self::queueDepth($queue);
    }

    /**
     * Skip dispatching another long LOW maintenance job when the lane is already busy.
     */
    public static function shouldDispatchHeavyLow(): bool
    {
        $maxDepth = max(1, (int) config('serik.scheduler.max_low_queue_depth', 3));

        return self::lowQueueDepth() < $maxDepth;
    }

    /**
     * Pause image backfill dispatch when the images lane is already deep.
     */
    public static function shouldDispatchImageBackfill(): bool
    {
        $maxDepth = max(10, (int) config('serik.images.max_pending', 120));

        return self::imagesQueueDepth() < $maxDepth;
    }

    /**
     * Imports must never contend with user-facing workers; depth gate only.
     */
    public static function shouldDispatchImports(): bool
    {
        $maxDepth = max(1, (int) config('serik.scheduler.max_imports_queue_depth', 20));

        return self::importsQueueDepth() < $maxDepth;
    }

    private static function queueDepth(string $queue): int
    {
        return (int) DB::table('jobs')->where('queue', $queue)->count();
    }
}
