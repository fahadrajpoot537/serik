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

    private static function queueDepth(string $queue): int
    {
        return (int) DB::table('jobs')->where('queue', $queue)->count();
    }
}
