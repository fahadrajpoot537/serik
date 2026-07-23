<?php

namespace App\Jobs;

use App\Support\SerikQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Recovery-only dispatcher: queues image jobs for listings that still need processing.
 */
class DispatchTrebImagesWebpJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public int $uniqueFor = 1800;

    private const STATE_KEY = 'serik_treb_images_webp_state';

    public function __construct(
        public int $chunk = 50,
    ) {
        $this->onQueue(SerikQueue::low());
    }

    public function uniqueId(): string
    {
        return 'dispatch-treb-images-webp';
    }

    public function handle(): void
    {
        // Image backfill disabled — legacy scheduled rows exit immediately.
    }

    public function failed(?Throwable $e): void
    {
    }
}
