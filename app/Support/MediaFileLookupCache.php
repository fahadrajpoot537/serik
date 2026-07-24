<?php

namespace App\Support;

use Botble\Media\Models\MediaFile;
use Illuminate\Support\Facades\Cache;

/**
 * Persistent cache for media_files lookups used by ImageAlt (replaces repeated LIKE queries).
 */
final class MediaFileLookupCache
{
    private const PREFIX = 'media_file_lookup_v1:';

    private const TTL_SECONDS = 3600;

    public static function find(string $relative): ?MediaFile
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');

        if ($relative === '') {
            return null;
        }

        $cacheKey = self::PREFIX . md5($relative);

        $cached = Cache::get($cacheKey);

        if ($cached === 'null') {
            return null;
        }

        if ($cached instanceof MediaFile) {
            return $cached;
        }

        $basename = basename($relative);

        $file = MediaFile::query()
            ->where(function ($query) use ($relative, $basename): void {
                $query->where('url', $relative)
                    ->orWhere('url', 'like', '%/' . $basename);
            })
            ->orderByRaw('CASE WHEN url = ? THEN 0 ELSE 1 END', [$relative])
            ->first();

        Cache::put($cacheKey, $file ?? 'null', self::TTL_SECONDS);

        return $file;
    }

    public static function forget(string $relative): void
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');

        if ($relative !== '') {
            Cache::forget(self::PREFIX . md5($relative));
        }
    }
}
