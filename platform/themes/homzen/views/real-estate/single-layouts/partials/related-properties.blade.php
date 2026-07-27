@php
    use Botble\RealEstate\Models\Property;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\View;

    $model = $model ?? $property ?? null;
    $isProject = $model instanceof \Botble\RealEstate\Models\Project;

    $activeStatuses = ['New', 'Active', 'Ext', 'Extension', 'Price Change', 'Active Under Contract'];
    $soldStatuses = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];

    $resolveCityName = static function ($model): string {
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
    };

    $relatedProperties = Cache::remember(
        'serik_related_props_v3_' . $model->getKey() . '_' . ($isProject ? 'project' : 'property'),
        300,
        function () use ($model, $isProject, $soldStatuses, $resolveCityName) {
            $limit = max(3, (int) theme_option('number_of_related_properties', 8));

            if ($isProject) {
                return Property::query()
                    ->where('project_id', $model->getKey())
                    ->where('moderation_status', \Botble\RealEstate\Enums\ModerationStatusEnum::APPROVED)
                    ->orderByDesc('id')
                    ->take($limit)
                    ->with([
                        'slugable:id,key,prefix,reference_id',
                        'currency:id,is_default,exchange_rate,symbol,title,is_prefix_symbol,decimals',
                    ])
                    ->get();
            }

            $cityName = $resolveCityName($model);
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
                    $applied = app(\Botble\RealEstate\Services\PropertySearchService::class)
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

            $attempts = [];

            // 1) Same area + same beds + same baths + same type
            $attempts[] = static function ($query) use ($beds, $baths, $subtype) {
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
            };

            // 2) Same area + same beds + same baths
            $attempts[] = static function ($query) use ($beds, $baths) {
                if ($beds > 0) {
                    $query->where('number_bedroom', $beds);
                }
                if ($baths > 0) {
                    $query->where('number_bathroom', $baths);
                }

                return $query;
            };

            // 3) Same area + same beds
            $attempts[] = static function ($query) use ($beds) {
                if ($beds > 0) {
                    $query->where('number_bedroom', $beds);
                }

                return $query;
            };

            // 4) Same area only (last resort)
            $attempts[] = static function ($query) {
                return $query;
            };

            $results = collect();
            foreach ($attempts as $applyFilters) {
                $query = $applyFilters($buildBase());
                $results = $query
                    ->orderByDesc('id')
                    ->take($limit)
                    ->with($with)
                    ->get();

                if ($results->count() >= min(3, $limit)) {
                    break;
                }
            }

            return $results;
        }
    );

    if ($isProject) {
        $sectionTitle = __('Properties in project ":name"', ['name' => $model->name]);
    } else {
        $cityName = $resolveCityName($model);
        $beds = (int) ($model->number_bedroom ?? 0);
        $baths = (int) ($model->number_bathroom ?? 0);
        $isSoldListing = in_array((string) ($model->MlsStatus ?? ''), $soldStatuses, true)
            || (float) ($model->ClosePrice ?? 0) > 0;

        if ($beds > 0 && $baths > 0 && $cityName !== '') {
            $sectionTitle = $isSoldListing
                ? __(':beds bed, :baths bath sold in :city', ['beds' => $beds, 'baths' => $baths, 'city' => $cityName])
                : __(':beds bed, :baths bath in :city', ['beds' => $beds, 'baths' => $baths, 'city' => $cityName]);
        } elseif ($cityName !== '') {
            $sectionTitle = $isSoldListing
                ? __('Sold in :city', ['city' => $cityName])
                : __('Similar homes in :city', ['city' => $cityName]);
        } else {
            $sectionTitle = $isSoldListing ? __('Similar Sold Listings') : __('Similar Properties');
        }
    }
@endphp

@if ($relatedProperties->isNotEmpty())
    <section class="flat-section pt-0 flat-latest-property" id="similarProperties">
        <div class="container">
            <div class="box-title">
                <div class="text-subtitle text-primary">{{ __('Similar Properties') }}</div>
                <h2 class="section-title mt-4">
                    {{ $sectionTitle ?? __('Similar Properties') }}
                </h2>
            </div>
            <div class="swiper tf-latest-property" data-preview-lg="3" data-preview-md="2" data-preview-sm="2" data-space="30" data-loop="true">
                <div class="swiper-wrapper">
                    @foreach($relatedProperties as $property)
                        <div class="swiper-slide">
                            @include(Theme::getThemeNamespace('views.real-estate.properties.item-grid'), ['property' => $property, 'class' => 'style-2'])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
