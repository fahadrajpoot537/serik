<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Theme\homzen\Supports\TrebPropertyHelper;

class OntarioPlaceSearch
{
    /**
     * Forward-geocode Ontario place names via OpenStreetMap Nominatim (MapLibre ecosystem).
     *
     * @return array<int, array{name: string, city: string, lat: float, lng: float, count: int, source: string, label: string}>
     */
    public function search(string $keyword, int $limit = 5): array
    {
        $keyword = trim($keyword);
        if (mb_strlen($keyword) < 3 || $limit < 1) {
            return [];
        }

        $limit = min($limit, 6);
        $cacheKey = 'serik_place_search_v1:' . md5(mb_strtolower($keyword) . '|' . $limit);

        // Single-flight lock prevents Nominatim stampedes under concurrent autocomplete.
        // Cached payload is identical to Cache::remember.
        return SerikCache::remember($cacheKey, max(60, (int) config('serik.cache.place_search_ttl', 86400)), function () use ($keyword, $limit) {
            return $this->nominatimSearch($keyword, $limit);
        });
    }

    /**
     * @param  array<int, array{name: string, city: string, lat: float|null, lng: float|null, count: int}>  $mlsRows
     */
    public function shouldSupplement(string $keyword, array $mlsRows): bool
    {
        $needle = mb_strtolower(trim($keyword));
        if ($needle === '') {
            return false;
        }

        if ($mlsRows === []) {
            return true;
        }

        foreach ($mlsRows as $row) {
            $name = mb_strtolower((string) ($row['name'] ?? ''));
            if ($name === $needle) {
                return false;
            }
            if (str_starts_with($name, $needle) || str_starts_with($needle, $name)) {
                return false;
            }

            if ($this->suffixFamily($needle) !== '' && $this->suffixFamily($needle) === $this->suffixFamily($name)) {
                $prefixLen = 0;
                $max = min(mb_strlen($needle), mb_strlen($name));
                while ($prefixLen < $max && mb_substr($needle, $prefixLen, 1) === mb_substr($name, $prefixLen, 1)) {
                    $prefixLen++;
                }
                if ($prefixLen >= 7) {
                    return false;
                }
            }
        }

        return true;
    }

    private function suffixFamily(string $value): string
    {
        if (preg_match('/(ville|borough|burg|wood|dale|view|park|hill|side|town|grove|valley)$/i', $value, $m)) {
            return mb_strtolower($m[1]);
        }

        return '';
    }

    /**
     * @return array<int, array{name: string, city: string, lat: float, lng: float, count: int, source: string, label: string}>
     */
    private function nominatimSearch(string $keyword, int $limit): array
    {
        $query = $keyword;
        $lower = mb_strtolower($keyword);
        if (! str_contains($lower, 'ontario') && ! preg_match('/\bon\b/i', $keyword)) {
            $query .= ', Ontario, Canada';
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Serik.ca Property Map/1.0 (https://serik.ca)',
                    'Accept' => 'application/json',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => $limit + 2,
                    'countrycodes' => 'ca',
                    'addressdetails' => 1,
                ]);
        } catch (\Throwable) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        $out = [];
        foreach ($response->json() ?: [] as $hit) {
            $lat = (float) ($hit['lat'] ?? 0);
            $lng = (float) ($hit['lon'] ?? 0);
            if ($lat === 0.0 || $lng === 0.0) {
                continue;
            }

            if ($lat < 41.5 || $lat > 57.0 || $lng > -74.0 || $lng < -95.5) {
                continue;
            }

            $addr = is_array($hit['address'] ?? null) ? $hit['address'] : [];
            $city = (string) ($addr['city'] ?? $addr['town'] ?? $addr['municipality'] ?? $addr['county'] ?? '');
            $name = trim((string) ($hit['name'] ?? ''));
            if ($name === '') {
                $name = trim(explode(',', (string) ($hit['display_name'] ?? ''))[0] ?? '');
            }
            if ($name === '') {
                continue;
            }

            $label = trim((string) ($hit['display_name'] ?? $name));
            $key = mb_strtolower($name . '|' . $city);
            if (isset($out[$key])) {
                continue;
            }

            $out[$key] = [
                'name' => $name,
                'city' => TrebPropertyHelper::formatCityLabel($city),
                'lat' => $lat,
                'lng' => $lng,
                'count' => 0,
                'source' => 'place',
                'label' => $label,
            ];

            if (count($out) >= $limit) {
                break;
            }
        }

        return array_values($out);
    }
}
