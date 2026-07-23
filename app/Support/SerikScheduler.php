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
        return (int) DB::table('jobs')->where('queue', SerikQueue::high())->count();
    }

    public static function lowQueueDepth(): int
    {
        return (int) DB::table('jobs')->where('queue', SerikQueue::low())->count();
    }

    /**
     * Skip dispatching another long LOW maintenance job when the lane is already busy.
     */
    public static function shouldDispatchHeavyLow(): bool
    {
        $maxDepth = max(1, (int) config('serik.scheduler.max_low_queue_depth', 3));

        return self::lowQueueDepth() < $maxDepth;
    }
}
