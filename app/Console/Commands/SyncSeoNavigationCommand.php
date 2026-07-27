<?php

namespace App\Console\Commands;

use App\Models\NearbyCity;
use App\Models\Neighborhood;
use App\Services\Seo\CityNavigationService;
use Botble\Location\Models\City;
use Botble\RealEstate\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Theme\homzen\Supports\TrebPropertyHelper;

class SyncSeoNavigationCommand extends Command
{
    protected $signature = 'serik:sync-seo-navigation
        {--nearby : Rebuild nearby city relationships}
        {--counts : Sync property counts for cities and neighborhoods}
        {--neighborhoods : Import neighborhoods from MLS community index}
        {--cities : Import missing cities from MLS City field}';

    protected $description = 'Sync SEO navigation data: property counts, neighborhoods, nearby cities';

    public function handle(CityNavigationService $navigation): int
    {
        if ($this->option('cities') || (! $this->option('nearby') && ! $this->option('neighborhoods') && ! $this->option('counts'))) {
            $this->syncCitiesFromMls();
            $navigation->bustCache();
        }

        if ($this->option('counts') || (! $this->option('nearby') && ! $this->option('neighborhoods') && ! $this->option('cities'))) {
            $this->syncPropertyCounts();
            $navigation->bustCache();
        }

        if ($this->option('neighborhoods') || (! $this->option('nearby') && ! $this->option('counts'))) {
            $this->syncNeighborhoods();
            $navigation->bustCache();
        }

        if ($this->option('nearby') || (! $this->option('neighborhoods') && ! $this->option('counts'))) {
            $this->syncNearbyCities();
            $navigation->bustCache();
        }

        $this->info('SEO navigation sync complete.');

        return self::SUCCESS;
    }

    private function syncPropertyCounts(): void
    {
        $active = config('seo_navigation.active_mls_statuses', []);
        $excluded = TrebPropertyHelper::excludedCommercialSubTypes();
        $propertyClass = Property::class;

        $rows = DB::table('re_properties as p')
            ->join('meta_boxes as mb', function ($join) use ($propertyClass): void {
                $join->on('mb.reference_id', '=', 'p.id')
                    ->where('mb.reference_type', $propertyClass)
                    ->where('mb.meta_key', 'amp_snapshot');
            })
            ->where('p.moderation_status', 'approved')
            ->whereIn('p.MlsStatus', $active)
            ->where(function ($q) use ($excluded): void {
                $q->whereNull('p.PropertySubType')
                    ->orWhere('p.PropertySubType', '')
                    ->orWhereNotIn('p.PropertySubType', $excluded);
            })
            ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.City")) as city_name, COUNT(*) as cnt')
            ->groupBy('city_name')
            ->get();

        $countsByName = [];
        foreach ($rows as $row) {
            $name = TrebPropertyHelper::formatCityLabel(trim((string) ($row->city_name ?? '')));
            if ($name === '') {
                continue;
            }
            $countsByName[$name] = ($countsByName[$name] ?? 0) + (int) $row->cnt;
        }

        $cities = City::query()
            ->where('is_active', true)
            ->get(['id', 'name']);

        $bar = $this->output->createProgressBar($cities->count());
        $updated = 0;

        foreach ($cities as $city) {
            $count = $countsByName[$city->name] ?? 0;

            if ($count === 0) {
                foreach ($countsByName as $name => $cnt) {
                    if (strcasecmp($name, $city->name) === 0) {
                        $count = $cnt;
                        break;
                    }
                }
            }

            DB::table('cities')->where('id', $city->id)->update([
                'property_count' => $count,
                'updated_at' => now(),
            ]);
            $updated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Property counts updated for {$updated} cities.");
    }

    private function syncCitiesFromMls(): void
    {
        $ontario = \Botble\Location\Models\State::query()
            ->where('name', 'Ontario')
            ->orWhere('abbreviation', 'ON')
            ->first();

        if (! $ontario) {
            $this->warn('Ontario state not found — skip MLS city import.');

            return;
        }

        $propertyClass = Property::class;
        $majorSlugs = array_flip(config('seo_navigation.major_city_slugs', []));
        $now = now();

        $rows = DB::table('re_properties as p')
            ->join('meta_boxes as mb', function ($join) use ($propertyClass): void {
                $join->on('mb.reference_id', '=', 'p.id')
                    ->where('mb.reference_type', $propertyClass)
                    ->where('mb.meta_key', 'amp_snapshot');
            })
            ->where('p.moderation_status', 'approved')
            ->whereNotNull(DB::raw('JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.City"))'))
            ->selectRaw('
                JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.City")) as city_name,
                AVG(CASE WHEN p.latitude != 0 AND p.longitude != 0 THEN p.latitude END) as avg_lat,
                AVG(CASE WHEN p.latitude != 0 AND p.longitude != 0 THEN p.longitude END) as avg_lng,
                COUNT(*) as cnt
            ')
            ->groupBy('city_name')
            ->orderByDesc('cnt')
            ->get();

        $inserted = 0;

        foreach ($rows as $row) {
            $name = TrebPropertyHelper::formatCityLabel(trim((string) ($row->city_name ?? '')));
            if ($name === '' || strlen($name) < 2) {
                continue;
            }

            $slug = Str::slug($name);
            if ($slug === '') {
                continue;
            }

            $exists = DB::table('cities')->where('slug', $slug)->exists();
            $isMajor = isset($majorSlugs[$slug]);

            DB::table('cities')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'state_id' => $ontario->id,
                    'country_id' => $ontario->country_id,
                    'latitude' => is_numeric($row->avg_lat ?? null) ? round((float) $row->avg_lat, 7) : null,
                    'longitude' => is_numeric($row->avg_lng ?? null) ? round((float) $row->avg_lng, 7) : null,
                    'is_major' => $isMajor,
                    'is_active' => true,
                    'status' => \Botble\Base\Enums\BaseStatusEnum::PUBLISHED,
                    'is_default' => false,
                    'order' => 0,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            if (! $exists) {
                $inserted++;
            }
        }

        $this->info('MLS cities imported/updated: ' . count($rows) . " ({$inserted} new).");
    }

    private function syncNeighborhoods(): void
    {
        if (! class_exists(\Botble\RealEstate\Services\PropertySearchService::class)) {
            return;
        }

        $search = app(\Botble\RealEstate\Services\PropertySearchService::class);
        // Prefer full index (keeps raw City district codes) when available via reflection-safe path.
        $index = $search->getPublicCommunityIndex();
        if ($index === []) {
            $this->warn('Community index empty — skip neighborhoods.');

            return;
        }

        $citiesByName = City::query()
            ->where('is_active', true)
            ->get(['id', 'name', 'slug'])
            ->keyBy(fn (City $c) => mb_strtolower((string) $c->name));

        $citiesBySlug = City::query()
            ->where('is_active', true)
            ->pluck('id', 'slug');

        $districtToSlug = [];
        foreach (config('seo_navigation.treb_city_districts', []) as $slug => $codes) {
            foreach ((array) $codes as $code) {
                $districtToSlug[mb_strtolower(trim((string) $code))] = (string) $slug;
            }
        }

        $inserted = 0;

        // District-aware sync from amp_snapshot so North York / Scarborough get their own rows.
        $rows = DB::table('meta_boxes as mb')
            ->join('re_properties as p', 'p.id', '=', 'mb.reference_id')
            ->where('mb.meta_key', 'amp_snapshot')
            ->where('mb.reference_type', Property::class)
            ->select([
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.CityRegion")) as raw_region'),
                DB::raw('JSON_UNQUOTE(JSON_EXTRACT(mb.meta_value, "$.City")) as raw_city'),
                DB::raw('AVG(CASE WHEN p.latitude != 0 AND p.longitude != 0 THEN p.latitude END) as avg_lat'),
                DB::raw('AVG(CASE WHEN p.latitude != 0 AND p.longitude != 0 THEN p.longitude END) as avg_lng'),
                DB::raw('COUNT(*) as cnt'),
            ])
            ->groupBy('raw_region', 'raw_city')
            ->orderByDesc('cnt')
            ->get();

        foreach ($rows as $row) {
            $community = TrebPropertyHelper::formatRegionLabel((string) ($row->raw_region ?? ''));
            $rawCity = trim((string) ($row->raw_city ?? ''));
            if ($community === '' || $rawCity === '' || preg_match('/^[A-Z]\d+$/i', $community)) {
                continue;
            }

            $cityId = null;
            $districtSlug = $districtToSlug[mb_strtolower($rawCity)] ?? null;
            if ($districtSlug) {
                $cityId = $citiesBySlug[$districtSlug] ?? null;
            }
            if (! $cityId) {
                $formattedCity = TrebPropertyHelper::formatCityLabel($rawCity);
                $cityId = $citiesByName[mb_strtolower($formattedCity)]->id ?? null;
            }
            if (! $cityId) {
                continue;
            }

            $slug = Str::slug($community);
            Neighborhood::query()->updateOrCreate(
                ['city_id' => $cityId, 'slug' => $slug],
                [
                    'name' => $community,
                    'latitude' => is_numeric($row->avg_lat ?? null) ? (float) $row->avg_lat : null,
                    'longitude' => is_numeric($row->avg_lng ?? null) ? (float) $row->avg_lng : null,
                    'property_count' => (int) ($row->cnt ?? 0),
                ]
            );
            $inserted++;
        }

        // Keep public-index fallback for cities already represented there.
        foreach ($index as $entry) {
            $community = trim((string) ($entry['n'] ?? $entry['name'] ?? ''));
            $cityName = trim((string) ($entry['c'] ?? $entry['city'] ?? ''));
            if ($community === '' || $cityName === '') {
                continue;
            }

            $cityId = $citiesByName[mb_strtolower($cityName)]->id ?? null;
            if (! $cityId) {
                continue;
            }

            $slug = Str::slug($community);
            $exists = Neighborhood::query()
                ->where('city_id', $cityId)
                ->where('slug', $slug)
                ->exists();
            if ($exists) {
                continue;
            }

            Neighborhood::query()->create([
                'city_id' => $cityId,
                'slug' => $slug,
                'name' => $community,
                'latitude' => $entry['la'] ?? $entry['lat'] ?? null,
                'longitude' => $entry['lo'] ?? $entry['lng'] ?? null,
                'property_count' => (int) ($entry['t'] ?? $entry['count'] ?? 0),
            ]);
            $inserted++;
        }

        $this->info("Neighborhoods synced: {$inserted}");
    }

    private function syncNearbyCities(): void
    {
        NearbyCity::query()->delete();

        $cities = City::query()
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->get(['id', 'latitude', 'longitude']);

        $limit = (int) config('seo_navigation.nearby_city_limit', 8);
        $rows = [];

        foreach ($cities as $city) {
            $distances = [];

            foreach ($cities as $other) {
                if ($city->id === $other->id) {
                    continue;
                }

                $km = $this->haversineKm(
                    (float) $city->latitude,
                    (float) $city->longitude,
                    (float) $other->latitude,
                    (float) $other->longitude
                );

                if ($km <= 80) {
                    $distances[] = ['id' => $other->id, 'km' => $km];
                }
            }

            usort($distances, fn ($a, $b) => $a['km'] <=> $b['km']);
            $distances = array_slice($distances, 0, $limit);

            foreach ($distances as $d) {
                $rows[] = [
                    'city_id' => $city->id,
                    'nearby_city_id' => $d['id'],
                    'distance_km' => round($d['km'], 2),
                    'created_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('nearby_cities')->insert($chunk);
        }

        $this->info('Nearby cities rebuilt: ' . count($rows) . ' rows');
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
