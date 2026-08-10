<?php

namespace App\Services\RealEstate;

use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;

class RelatedPropertiesService
{
    public function build(Model $model): array
    {
        try {
            return $this->buildInternal($model);
        } catch (\Throwable $e) {
            report($e);

            return [
                'relatedProperties' => collect(),
                'sectionTitle' => $this->buildSectionTitle($model),
            ];
        }
    }

    protected function buildInternal(Model $model): array
    {
        $isProject = $model instanceof Project;
        $soldStatuses = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];
        // v6: match by city name — MLS rows almost never have city_id populated.
        $cacheKey = 'serik_related_props_v6_' . $model->getKey() . '_' . ($isProject ? 'project' : 'property');
        $ttl = max(60, (int) config('serik.cache.related_ttl', 900));

        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if (is_array($cached) && array_key_exists('ids', $cached)) {
            return [
                'relatedProperties' => $this->hydrateByIds($cached['ids'] ?? []),
                'sectionTitle' => (string) ($cached['sectionTitle'] ?? $this->buildSectionTitle($model)),
            ];
        }

        // Single-flight: prevent stampede on cold related-property misses.
        $lock = \Illuminate\Support\Facades\Cache::lock('serik:cache:sf:' . md5($cacheKey), 15);
        try {
            $payload = $lock->block(5, function () use ($cacheKey, $ttl, $model, $isProject, $soldStatuses) {
                $again = \Illuminate\Support\Facades\Cache::get($cacheKey);
                if (is_array($again) && array_key_exists('ids', $again)) {
                    return [
                        'relatedProperties' => $this->hydrateByIds($again['ids'] ?? []),
                        'sectionTitle' => (string) ($again['sectionTitle'] ?? $this->buildSectionTitle($model)),
                    ];
                }

                $computed = $this->computeFast($model, $isProject, $soldStatuses);
                // Cache empty too — rare cities (e.g. Unorganized Townships) otherwise
                // re-run multi-minute LIKE scans on every cold request.
                \Illuminate\Support\Facades\Cache::put($cacheKey, [
                    'ids' => $computed['relatedProperties']->pluck('id')->all(),
                    'sectionTitle' => $computed['sectionTitle'],
                ], $ttl);

                return $computed;
            });
        } catch (\Throwable) {
            $payload = $this->computeFast($model, $isProject, $soldStatuses);
            \Illuminate\Support\Facades\Cache::put($cacheKey, [
                'ids' => $payload['relatedProperties']->pluck('id')->all(),
                'sectionTitle' => $payload['sectionTitle'],
            ], $ttl);
        }

        return $payload;
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    protected function hydrateByIds(array $ids): Collection
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return collect();
        }

        $with = [
            'slugable:id,key,prefix,reference_id',
            'currency:id,is_default,exchange_rate,symbol,title,is_prefix_symbol,decimals',
        ];

        $rows = Property::query()
            ->whereIn('id', $ids)
            ->with($with)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn ($id) => $rows->get($id))
            ->filter()
            ->values();
    }

    protected function computeFast(Model $model, bool $isProject, array $soldStatuses): array
    {
        $limit = max(3, min(8, (int) theme_option('number_of_related_properties', 8)));
        $sectionTitle = $this->buildSectionTitle($model);

        if ($isProject) {
            $related = Property::query()
                ->where('project_id', $model->getKey())
                ->where('moderation_status', ModerationStatusEnum::APPROVED)
                ->orderByDesc('id')
                ->take($limit)
                ->with([
                    'slugable:id,key,prefix,reference_id',
                    'currency:id,is_default,exchange_rate,symbol,title,is_prefix_symbol,decimals',
                ])
                ->get();

            return [
                'relatedProperties' => $related,
                'sectionTitle' => $sectionTitle,
            ];
        }

        $cityName = $this->resolveCityName($model);
        $beds = (int) ($model->number_bedroom ?? 0);
        $baths = (int) ($model->number_bathroom ?? 0);
        $isSoldListing = in_array((string) ($model->MlsStatus ?? ''), $soldStatuses, true)
            || (float) ($model->ClosePrice ?? 0) > 0;
        $subtype = trim((string) ($model->PropertySubType ?? ''));

        $with = [
            'slugable:id,key,prefix,reference_id',
            'currency:id,is_default,exchange_rate,symbol,title,is_prefix_symbol,decimals',
        ];

        // beds/baths in SQL + LIKE city is multi-second on MLS volume.
        // Prefer Meili city IDs (via runScopedQuery); then prefer matching beds/baths in PHP.
        $attempts = [
            [$cityName, $subtype],
            [$cityName, ''],
        ];

        $pool = collect();
        foreach ($attempts as [$city, $type]) {
            $pool = $this->runScopedQuery(
                (int) $model->id,
                (string) $city,
                $isSoldListing,
                $soldStatuses,
                (string) $type,
                $limit * 3,
                $with
            );

            if ($pool->count() >= min(3, $limit)) {
                break;
            }

            // Rare/tiny cities: one city-scoped attempt is enough — a second
            // full-table LIKE scan can block property detail for 60–100s+.
            if ($city !== '' && $pool->count() > 0) {
                break;
            }
        }

        $related = $pool
            ->sortByDesc(function ($property) use ($beds, $baths, $subtype) {
                $score = 0;
                if ($subtype !== '' && trim((string) ($property->PropertySubType ?? '')) === $subtype) {
                    $score += 4;
                }
                if ($beds > 0 && (int) ($property->number_bedroom ?? 0) === $beds) {
                    $score += 2;
                }
                if ($baths > 0 && (int) ($property->number_bathroom ?? 0) === $baths) {
                    $score += 1;
                }

                return $score * 1_000_000_000 + (int) $property->id;
            })
            ->take($limit)
            ->values();

        return [
            'relatedProperties' => $related,
            'sectionTitle' => $sectionTitle,
        ];
    }

    /**
     * @param  array<int, string>  $soldStatuses
     * @param  array<int, string>  $with
     */
    protected function runScopedQuery(
        int $excludeId,
        string $cityName,
        bool $isSoldListing,
        array $soldStatuses,
        string $subtype,
        int $limit,
        array $with
    ): Collection {
        $query = Property::query()
            ->select([
                'id',
                'name',
                'location',
                'number_bedroom',
                'number_bathroom',
                'PropertySubType',
                'MlsStatus',
                'ClosePrice',
                'price',
                'images',
                'image_val',
                'currency_id',
                'square',
                'status',
                'moderation_status',
                'external_id',
            ])
            ->where('id', '!=', $excludeId)
            ->where('moderation_status', ModerationStatusEnum::APPROVED)
            ->residential();

        if ($isSoldListing) {
            $query->where(function ($q) use ($soldStatuses) {
                $q->whereIn('MlsStatus', $soldStatuses)
                    ->orWhere('ClosePrice', '>', 0);
            });
        } else {
            $query->mlsActive();
        }

        // Prefer Meili city IDs; MySQL fallback uses "%, City, ON%" only (no
        // leading-wildcard OR) — that pattern was scanning 100k+ rows for rare cities.
        if ($cityName !== '') {
            $search = app(\Botble\RealEstate\Services\PropertySearchService::class);
            $opts = $isSoldListing
                ? ['statuses' => $soldStatuses]
                : [
                    'statuses' => [
                        'New',
                        'Active',
                        'Ext',
                        'Extension',
                        'Price Change',
                        'Active Under Contract',
                    ],
                ];
            $applied = $search->constrainQueryToCity($query, $cityName, 2000, true, $opts);
            if (! $applied) {
                return collect();
            }
        }

        if ($subtype !== '') {
            $query->where('PropertySubType', $subtype);
        }

        // Soft time cap so a bad plan cannot hold the property detail request.
        try {
            \Illuminate\Support\Facades\DB::statement('SET SESSION MAX_EXECUTION_TIME=3000');
        } catch (\Throwable) {
            // MariaDB / older MySQL — ignore.
        }

        try {
            return $query
                ->orderByDesc('id')
                ->take($limit)
                ->with($with)
                ->get();
        } catch (\Throwable $e) {
            report($e);

            return collect();
        } finally {
            try {
                \Illuminate\Support\Facades\DB::statement('SET SESSION MAX_EXECUTION_TIME=0');
            } catch (\Throwable) {
                // ignore
            }
        }
    }

    protected function resolveCityName(Model $model): string
    {
        $cityName = (string) (View::shared('cityName', '') ?: '');
        if ($cityName !== '') {
            return trim($cityName);
        }
        if (! empty($model->name) && preg_match('/,\s*([^,]+),\s*ON\b/i', (string) $model->name, $cityMatch)) {
            return trim($cityMatch[1]);
        }
        if (! empty($model->location) && preg_match('/,\s*([^,]+),\s*ON\b/i', (string) $model->location, $cityMatch)) {
            return trim($cityMatch[1]);
        }
        if (! empty($model->short_address)) {
            return trim((string) $model->short_address);
        }

        return '';
    }

    protected function buildSectionTitle(Model $model): string
    {
        if ($model instanceof Project) {
            return __('Properties in project ":name"', ['name' => $model->name]);
        }

        $soldStatuses = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];
        $cityName = $this->resolveCityName($model);
        $beds = (int) ($model->number_bedroom ?? 0);
        $baths = (int) ($model->number_bathroom ?? 0);
        $isSoldListing = in_array((string) ($model->MlsStatus ?? ''), $soldStatuses, true)
            || (float) ($model->ClosePrice ?? 0) > 0;

        if ($beds > 0 && $baths > 0 && $cityName !== '') {
            return $isSoldListing
                ? __(':beds bed, :baths bath sold in :city', ['beds' => $beds, 'baths' => $baths, 'city' => $cityName])
                : __(':beds bed, :baths bath in :city', ['beds' => $beds, 'baths' => $baths, 'city' => $cityName]);
        }
        if ($cityName !== '') {
            return $isSoldListing
                ? __('Sold in :city', ['city' => $cityName])
                : __('Similar homes in :city', ['city' => $cityName]);
        }

        return $isSoldListing ? __('Similar Sold Listings') : __('Similar Properties');
    }
}
