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

    /**
     * Skip dispatching SyncLiveJob when the HIGH lane already has pending copies.
     */
    public static function shouldDispatchSyncLive(): bool
    {
        $maxPending = max(0, (int) config('serik.scheduler.max_sync_live_pending', 1));
        if ($maxPending === 0) {
            return true;
        }

        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('jobs')) {
                return true;
            }

            $pending = SerikQueueJobHygiene::countPending(\App\Jobs\SyncLiveJob::class, SerikQueue::high());
        } catch (\Throwable) {
            return true;
        }

        return $pending < $maxPending;
    }

    /**
     * Spread every-minute scheduler tasks across N minute slots (configurable).
     * stagger_slots=1 (default) runs every task every minute (no behavior change).
     */
    public static function shouldRunStaggerSlot(string $taskKey): bool
    {
        $slots = max(1, (int) config('serik.scheduler.stagger_slots', 1));
        if ($slots <= 1) {
            return true;
        }

        $phase = abs(crc32($taskKey)) % $slots;

        return ((int) now()->format('i') % $slots) === $phase;
    }

    /**
     * Release DB connections after lightweight schedule closures (cron overlap).
     */
    public static function releaseDatabaseConnections(): void
    {
        try {
            \Illuminate\Support\Facades\DB::disconnect();
        } catch (\Throwable) {
            //
        }
    }

    private static function queueDepth(string $queue): int
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('jobs')) {
                return 0;
            }

            return (int) DB::table('jobs')->where('queue', $queue)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
