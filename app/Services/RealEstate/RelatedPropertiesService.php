<?php

namespace App\Services\RealEstate;

use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Botble\RealEstate\Services\PropertySearchService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class RelatedPropertiesService
{
    public function build(Model $model): array
    {
        $isProject = $model instanceof Project;
        $soldStatuses = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];
        $cacheKey = 'serik_related_props_v3_' . $model->getKey() . '_' . ($isProject ? 'project' : 'property');

        $relatedProperties = Cache::remember($cacheKey, 300, function () use ($model, $isProject, $soldStatuses): Collection {
            $limit = max(3, (int) theme_option('number_of_related_properties', 8));

            if ($isProject) {
                return Property::query()
                    ->where('project_id', $model->getKey())
                    ->where('moderation_status', ModerationStatusEnum::APPROVED)
                    ->orderByDesc('id')
                    ->take($limit)
                    ->with([
                        'slugable:id,key,prefix,reference_id',
                        'currency:id,is_default,exchange_rate,symbol,title,is_prefix_symbol,decimals',
                    ])
                    ->get();
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

            $buildBase = static function () use ($model, $isSoldListing, $soldStatuses, $cityName) {
                $query = Property::query()
                    ->active()
                    ->residential()
                    ->where('id', '!=', $model->id);

                if ($isSoldListing) {
                    $query->where(function ($q) use ($soldStatuses) {
                        $q->whereIn('MlsStatus', $soldStatuses)
                            ->orWhere('ClosePrice', '>', 0);
                    });
                } else {
                    $query->mlsActive();
                }

                if (! empty($model->city_id)) {
                    $query->where('city_id', $model->city_id);
                } elseif ($cityName !== '') {
                    $applied = app(PropertySearchService::class)
                        ->constrainQueryToCity($query, $cityName, 2500, false);

                    if (! $applied) {
                        $query->where(function ($q) use ($cityName) {
                            $q->where('location', 'like', '%' . $cityName . '%')
                                ->orWhere('name', 'like', '%' . $cityName . '%');
                        });
                    }
                }

                return $query;
            };

            $attempts = [
                static function ($query) use ($beds, $baths, $subtype) {
                    if ($beds > 0) {
                        $query->where('number_bedroom', $beds);
                    }
                    if ($baths > 0) {
                        $query->where('number_bathroom', $baths);
                    }
                    if ($subtype !== '') {
                        $query->where('PropertySubType', $subtype);
                    }

                    return $query;
                },
                static function ($query) use ($beds, $baths) {
                    if ($beds > 0) {
                        $query->where('number_bedroom', $beds);
                    }
                    if ($baths > 0) {
                        $query->where('number_bathroom', $baths);
                    }

                    return $query;
                },
                static function ($query) use ($beds) {
                    if ($beds > 0) {
                        $query->where('number_bedroom', $beds);
                    }

                    return $query;
                },
                static fn ($query) => $query,
            ];

            $results = collect();
            foreach ($attempts as $applyFilters) {
                $results = $applyFilters($buildBase())
                    ->orderByDesc('id')
                    ->take($limit)
                    ->with($with)
                    ->get();

                if ($results->count() >= min(3, $limit)) {
                    break;
                }
            }

            return $results;
        });

        return [
            'relatedProperties' => $relatedProperties,
            'sectionTitle' => $this->buildSectionTitle($model),
        ];
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

