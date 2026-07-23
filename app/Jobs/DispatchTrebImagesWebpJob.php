<?php

namespace App\Jobs;

use App\Support\ListingImagePipeline;
use App\Support\SerikQueue;
use App\Support\SerikScheduler;
use Botble\RealEstate\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

    public function handle(ListingImagePipeline $pipeline): void
    {
        if (! SerikScheduler::shouldDispatchImageBackfill()) {
            Log::debug('[DispatchTrebImagesWebpJob] skipped — images queue busy', [
                'images_depth' => SerikScheduler::imagesQueueDepth(),
            ]);

            return;
        }

        $chunk = max(10, min(200, $this->chunk));
        $saved = Cache::get(self::STATE_KEY, []);
        $lastId = is_array($saved) ? (int) ($saved['last_id'] ?? 0) : 0;
        $dispatched = 0;
        $skipped = 0;

        $rows = Property::query()
            ->select(['id', 'external_id', 'image_val', 'images'])
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit($chunk)
            ->get();

        if ($rows->isEmpty()) {
            Cache::forget(self::STATE_KEY);
            Log::info('[DispatchTrebImagesWebpJob] backfill complete');

            return;
        }

        foreach ($rows as $property) {
            if (! $pipeline->needsProcessing($property)) {
                $skipped++;

                continue;
            }

            $pipeline->queueForRecovery((int) $property->id);
            $dispatched++;
        }

        $newLastId = (int) $rows->last()->id;
        Cache::put(self::STATE_KEY, [
            'last_id' => $newLastId,
            'dispatched' => $dispatched,
            'skipped' => $skipped,
            'updated_at' => now()->toIso8601String(),
        ], 86400 * 30);

        Log::info('[DispatchTrebImagesWebpJob] recovery slice', [
            'last_id' => $newLastId,
            'dispatched' => $dispatched,
            'skipped' => $skipped,
            'images_depth' => SerikScheduler::imagesQueueDepth(),
        ]);
    }

    public function failed(?Throwable $e): void
    {
        Log::error('[DispatchTrebImagesWebpJob] failed', ['error' => $e?->getMessage()]);
    }
}
