<?php

namespace Database\Seeders;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Location\Models\City;
use Botble\Location\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class OntarioSeoNavigationSeeder extends Seeder
{
    public function run(): void
    {
        $ontario = State::query()
            ->where('name', 'Ontario')
            ->orWhere('abbreviation', 'ON')
            ->first();

        if (! $ontario) {
            $this->command?->error('Ontario state not found in states table.');

            return;
        }

        $path = database_path('data/ontario_cities.json');
        if (! File::exists($path)) {
            $this->command?->error('Missing database/data/ontario_cities.json');

            return;
        }

        $rows = json_decode(File::get($path), true);
        if (! is_array($rows)) {
            $this->command?->error('Invalid ontario_cities.json');

            return;
        }

        $majorSlugs = array_flip(config('seo_navigation.major_city_slugs', []));
        $now = now();
        $count = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $slug = Str::slug($name);
            $isMajor = isset($majorSlugs[$slug]) || (bool) ($row['is_major'] ?? false);

            DB::table('cities')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'state_id' => $ontario->id,
                    'country_id' => $ontario->country_id,
                    'latitude' => $row['lat'] ?? $row['latitude'] ?? null,
                    'longitude' => $row['lng'] ?? $row['longitude'] ?? null,
                    'is_major' => $isMajor,
                    'is_active' => true,
                    'status' => BaseStatusEnum::PUBLISHED,
                    'is_default' => false,
                    'order' => 0,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $count++;
        }

        $this->command?->info("Seeded/updated {$count} Ontario cities.");
    }
}
