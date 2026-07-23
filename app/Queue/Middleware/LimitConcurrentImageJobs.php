<?php

namespace App\Queue\Middleware;

use Illuminate\Support\Facades\Cache;

/**
 * Global semaphore for image workers — prevents hundreds of AMP/HTTP fetches at once.
 */
final class LimitConcurrentImageJobs
{
    public function handle(object $job, callable $next): mixed
    {
        $max = max(1, (int) config('serik.images.max_concurrent', 2));
        $counterKey = 'serik_images_active_jobs';
        $lockKey = 'serik_images_slot_lock';

        $lock = Cache::lock($lockKey, 30);

        if (! $lock->block(5)) {
            $job->release((int) config('serik.images.slot_wait_seconds', 15));

            return null;
        }

        try {
            $active = (int) Cache::get($counterKey, 0);

            if ($active >= $max) {
                $job->release((int) config('serik.images.slot_wait_seconds', 15));

                return null;
            }

            Cache::put($counterKey, $active + 1, 900);
        } finally {
            $lock->release();
        }

        try {
            return $next($job);
        } finally {
            $lock = Cache::lock($lockKey, 30);
            $lock->block(5, function () use ($counterKey): void {
                $active = (int) Cache::get($counterKey, 0);
                Cache::put($counterKey, max(0, $active - 1), 900);
            });
        }
    }
}
