@php
    use Botble\RealEstate\Models\Property;

    $model = $model ?? $property ?? null;
    $isProject = $model instanceof \Botble\RealEstate\Models\Project;

    $activeStatuses = ['New', 'Price Change', 'Extension', 'Previous Status'];
    $soldStatuses = ['Sold', 'Sold Conditional', 'Sold Conditional Escape', 'Leased', 'Leased Conditional'];

    if ($isProject) {
        $relatedProperties = Property::query()
            ->where('project_id', $model->getKey())
            ->where('moderation_status', \Botble\RealEstate\Enums\ModerationStatusEnum::APPROVED)
            ->latest()
            ->take(theme_option('number_of_related_properties', 8))
            ->with([...Botble\RealEstate\Facades\RealEstateHelper::getPropertyRelationsQuery(), 'author'])
            ->get();
        $sectionTitle = __('Properties in project ":name"', ['name' => $model->name]);
    } else {
        $cityName = View::shared('cityName', '') ?: '';
        if (! $cityName && ! empty($model->name) && preg_match('/,\s*([^,]+),\s*ON\b/i', $model->name, $cityMatch)) {
            $cityName = trim($cityMatch[1]);
        }

        $isSoldListing = in_array($model->MlsStatus, $soldStatuses, true) || (float) ($model->ClosePrice ?? 0) > 0;
        $price = (float) $model->price;
        $minPrice = $price > 0 ? $price * 0.8 : 0;
        $maxPrice = $price > 0 ? $price * 1.2 : 100000000;

        $query = Property::query()
            ->where('id', '!=', $model->id)
            ->where('moderation_status', \Botble\RealEstate\Enums\ModerationStatusEnum::APPROVED);

        if ($model->PropertySubType) {
            $subtype = trim($model->PropertySubType);
            $subtypeVariants = array_unique(array_filter([
                $subtype,
                $subtype . ' ',
                str_replace('-', ' ', $subtype),
                str_replace('-', ' ', $subtype) . ' ',
            ]));
            $query->whereIn('PropertySubType', $subtypeVariants);
        }

        if ($cityName) {
            // Cap Meili city IDs — 2000-wide whereIn was 500–800ms+ and, under
            // geocode load, helped push detail pages past PHP's 60s limit.
            app(\Botble\RealEstate\Services\PropertySearchService::class)
                ->constrainQueryToCity($query, $cityName, 250);
        }

        if ($isSoldListing) {
            $query->where(function ($q) use ($soldStatuses) {
                $q->whereIn('MlsStatus', $soldStatuses)
                    ->orWhere('ClosePrice', '>', 0);
            });
            $sectionTitle = $cityName
                ? __('Sold in :city', ['city' => $cityName])
                : __('Similar Sold Listings');
        } else {
            $query->whereIn('MlsStatus', $activeStatuses)
                ->where(function ($q) {
                    $q->whereNull('ClosePrice')->orWhere('ClosePrice', '<=', 0);
                });
            $sectionTitle = $cityName
                ? __('For Sale in :city', ['city' => $cityName])
                : __('Related Properties');
        }

        if ($price > 0) {
            $query->whereBetween('price', [$minPrice, $maxPrice]);
        }

        $relatedProperties = $query
            ->latest()
            ->take(theme_option('number_of_related_properties', 8))
            ->with([...Botble\RealEstate\Facades\RealEstateHelper::getPropertyRelationsQuery(), 'author'])
            ->get();
    }
@endphp

@if ($relatedProperties->isNotEmpty())
    <section class="flat-section pt-0 flat-latest-property">
        <div class="container">
            <div class="box-title">
                <div class="text-subtitle text-primary">{{ __('Related Listings') }}</div>
                <h2 class="section-title mt-4">
                    {{ $sectionTitle ?? __('The Most Recent Estate') }}
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
