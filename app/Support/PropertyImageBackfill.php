<?php

namespace App\Support;

use Botble\RealEstate\Models\Property;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Theme\homzen\Supports\TrebPropertyHelper;

/**
 * Optional background fill of re_properties.image_val from AMP.
 * Not required for display — TrebImageProxy fetches on first browser request.
 */
final class PropertyImageBackfill
{
    public const CURSOR_KEY = 'property_image_last_id';

    public const LOCK_KEY = 'import-property-images-lock';

    /**
     * @return array{
     *   status: string,
     *   processed: int,
     *   updated: int,
     *   skipped: int,
     *   last_id: int,
     *   message: string
     * }
     */
    public static function runBatch(int $batchLimit = 100, bool $sleepBetweenRows = true): array
    {
        $batchLimit = max(1, min(500, $batchLimit));
        $lastId = (int) cache()->get(self::CURSOR_KEY, 0);
        $processed = 0;
        $updated = 0;
        $skipped = 0;

        $properties = Property::query()
            ->select(['id', 'external_id', 'image_val'])
            ->whereNotNull('external_id')
            ->where('external_id', '!=', '')
            ->where(function ($q) {
                $q->whereNull('image_val')
                    ->orWhere('image_val', '')
                    ->orWhere('image_val', 'like', 'properties/treb/%');
            })
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit($batchLimit)
            ->get();

        if ($properties->isEmpty()) {
            cache()->forget(self::CURSOR_KEY);

            return [
                'status' => 'complete',
                'processed' => 0,
                'updated' => 0,
                'skipped' => 0,
                'last_id' => $lastId,
                'message' => 'Image backfill finished — no more rows need CDN URLs.',
            ];
        }

        foreach ($properties as $property) {
            $processed++;
            $listingKey = trim((string) $property->external_id);

            if ($listingKey === '') {
                $skipped++;
                cache()->put(self::CURSOR_KEY, (int) $property->id);

                continue;
            }

            $urls = TrebPropertyHelper::getPropertyImagesForPersistence(
                $listingKey,
                $property->image_val,
                fresh: false
            );
            $firstImage = is_string($urls[0] ?? null) ? trim((string) $urls[0]) : '';

            if ($firstImage === '' || ! TrebMediaFilter::isPhotoMediaUrl($firstImage)) {
                $skipped++;
                cache()->put(self::CURSOR_KEY, (int) $property->id);

                continue;
            }

            $property->image_val = $firstImage;
            $property->saveQuietly();
            $updated++;
            cache()->put(self::CURSOR_KEY, (int) $property->id);

            if ($sleepBetweenRows) {
                usleep(50_000);
            }
        }

        return [
            'status' => 'ok',
            'processed' => $processed,
            'updated' => $updated,
            'skipped' => $skipped,
            'last_id' => (int) $properties->last()->id,
            'message' => 'Batch complete.',
        ];
    }

    public static function acquireLock(int $seconds = 300): ?Lock
    {
        $lock = Cache::lock(self::LOCK_KEY, $seconds);

        return $lock->get() ? $lock : null;
    }
}
