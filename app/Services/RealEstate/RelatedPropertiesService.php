<?php

namespace App\Services\RealEstate;

use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && array_key_exists('ids', $cached) && $cached['ids'] !== []) {
            return [
                'relatedProperties' => $this->hydrateByIds($cached['ids'] ?? []),
                'sectionTitle' => (string) ($cached['sectionTitle'] ?? $this->buildSectionTitle($model)),
            ];
        }

        $payload = $this->computeFast($model, $isProject, $soldStatuses);
        Cache::put($cacheKey, [
            'ids' => $payload['relatedProperties']->pluck('id')->all(),
            'sectionTitle' => $payload['sectionTitle'],
        ], 600);

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
        // Fetch city(+subtype) fast, then prefer matching beds/baths in PHP.
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

        // MLS imports leave city_id null — match ", City, ON" in the display name.
        if ($cityName !== '') {
            $needle = '%, ' . $cityName . ', ON%';
            $query->where(function ($q) use ($needle, $cityName) {
                $q->where('name', 'like', $needle)
                    ->orWhere('location', 'like', '%' . $cityName . '%');
            });
        }

        if ($subtype !== '') {
            $query->where('PropertySubType', $subtype);
        }

        return $query
            ->orderByDesc('id')
            ->take($limit)
            ->with($with)
            ->get();
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
