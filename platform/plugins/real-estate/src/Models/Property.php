<?php

namespace Botble\RealEstate\Models;

use App\Support\SerikMediaUrl;
use Botble\Base\Casts\SafeContent;
use Botble\Base\Facades\Html;
use Botble\Base\Models\BaseModel;
use Botble\Location\Models\City;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Botble\Media\Facades\RvMedia;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Enums\PropertyPeriodEnum;
use Botble\RealEstate\Enums\PropertyStatusEnum;
use Botble\RealEstate\Enums\PropertyTypeEnum;
use Botble\RealEstate\Models\Traits\UniqueId;
use Botble\RealEstate\QueryBuilders\PropertyBuilder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * @method static \Botble\RealEstate\QueryBuilders\PropertyBuilder<static> query()
 */
class Property extends BaseModel
{
    use UniqueId;
    use Searchable;

    protected $table = 're_properties';

    protected $fillable = [
        'name',
        'type',
        'description',
        'content',
        'location',
        'images',
        'project_id',
        'number_bedroom',
        'number_bathroom',
        'number_floor',
        'square',
        'price',
        'status',
        'is_featured',
        'featured_priority',
        'currency_id',
        'city_id',
        'state_id',
        'country_id',
        'period',
        'author_id',
        'author_type',
        'expire_date',
        'auto_renew',
        'latitude',
        'longitude',
        'google_formatted_address',
        'google_location_type',
        'geocoded_at',
        'geocoding_provider',
        'geocoding_status',
        'geocode_queued_at',
        'geocode_started_at',
        'geocode_completed_at',
        'geocode_failed_at',
        'geocode_attempts',
        'zip_code',
        'unique_id',
        'created_at',
        'private_notes',
        'floor_plans',
        'reject_reason',
        'external_id',
        'TransactionType',
        'MlsStatus',
        'image_val',
        'broker',
        'BedroomsBelowGrade',
        'PropertySubType',
        'ClosePrice',
        'Basement',
        'ParkingSpaces',
        'CoveredSpaces',
        'listing_contract_date',
        'listing_modified_at',
        'close_date',
        'purchase_contract_date',
    ];

    protected $casts = [
        'listing_contract_date' => 'datetime',
        'listing_modified_at' => 'datetime',
        'close_date' => 'datetime',
        'purchase_contract_date' => 'datetime',
        'geocode_queued_at' => 'datetime',
        'geocode_started_at' => 'datetime',
        'geocode_completed_at' => 'datetime',
        'geocode_failed_at' => 'datetime',
        'geocode_attempts' => 'int',
        'geocoded_at' => 'datetime',
        'status' => PropertyStatusEnum::class,
        'moderation_status' => ModerationStatusEnum::class,
        'period' => PropertyPeriodEnum::class,
        'name' => SafeContent::class,
        'description' => SafeContent::class,
        'location' => SafeContent::class,
        'private_notes' => SafeContent::class,
        'expire_date' => 'datetime',
        'images' => 'json',
        'price' => 'float',
        'square' => 'string',
        'number_bedroom' => 'int',
        'number_bathroom' => 'int',
        'number_floor' => 'int',
        'featured_priority' => 'int',
        'floor_plans' => 'array',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Property $property): void {
            $property->categories()->detach();
            $property->customFields()->delete();
            $property->reviews()->delete();
            $property->features()->detach();
            $property->facilities()->detach();
            $property->metadata()->delete();
        });

        // Append-only price/status/sale history. Uses wasRecentlyCreated + the
        // dirty tracking captured before the write, and is fully guarded so it
        // can never interrupt the TREB import/upsert path.
        static::saved(function (Property $property): void {
            \Botble\RealEstate\Supports\PropertyHistoryRecorder::record(
                $property,
                $property->wasRecentlyCreated
            );
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id')->withDefault();
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 're_property_features', 'property_id', 'feature_id');
    }

    public function facilities(): BelongsToMany
    {
        return $this->morphToMany(Facility::class, 'reference', 're_facilities_distances')->withPivot('distance');
    }

    protected function image(): Attribute
    {
        return Attribute::get(fn() => Arr::first($this->images) ?? null);
    }

    protected function squareText(): Attribute
    {
        return Attribute::get(function () {
            $square = $this->square;

            if (is_string($square) && preg_match('/^(\d+)\s*-\s*(\d+)$/', trim($square), $matches)) {
                return number_format((int) $matches[1]) . '-' . number_format((int) $matches[2]) . ' sq. ft.';
            }

            if (is_numeric($square) && class_exists(\Theme\homzen\Supports\TrebPropertyHelper::class)) {
                $normalized = \Theme\homzen\Supports\TrebPropertyHelper::normalizeSquareStorage($square);
                if ($normalized && preg_match('/^(\d+)\s*-\s*(\d+)$/', $normalized, $matches)) {
                    return number_format((int) $matches[1]) . '-' . number_format((int) $matches[2]) . ' sq. ft.';
                }
            }

            if (is_string($square) && preg_match('/^(\d{4})(\d{4})$/', trim($square), $matches)) {
                $low = (int) $matches[1];
                $high = (int) $matches[2];

                if ($high > $low) {
                    return number_format($low) . '-' . number_format($high) . ' sq. ft.';
                }
            }

            $unit = setting('real_estate_square_unit', 'm²');

            if (is_string($square) && str_contains($square, '-')) {
                return $square . ' ' . trans($unit);
            }

            if (is_numeric($square)) {
                $squareFormatted = fmod($square, 1) == 0
                    ? number_format($square)
                    : number_format($square, 2);

                return sprintf('%s %s', $squareFormatted, trans($unit));
            }

            return null;
        });
    }

    protected function address(): Attribute
    {
        return Attribute::get(fn() => $this->location);
    }

    protected function displayName(): Attribute
    {
        return Attribute::get(function () {
            if (class_exists(\Theme\homzen\Supports\TrebPropertyHelper::class)) {
                return \Theme\homzen\Supports\TrebPropertyHelper::formatDisplayAddress([
                    'UnparsedAddress' => (string) $this->name,
                    'name' => (string) $this->name,
                ]) ?: $this->name;
            }

            return $this->name;
        });
    }

    protected function category(): Attribute
    {
        return Attribute::get(fn() => $this->categories->first() ?: new Category());
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function author(): MorphTo
    {
        return $this->morphTo()->withDefault();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 're_property_categories');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id')->withDefault();
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id')->withDefault();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id')->withDefault();
    }

    protected function cityName(): Attribute
    {
        return Attribute::get(function () {
            if (!is_plugin_active('location')) {
                return $this->location;
            }

            return ($this->city->name ? $this->city->name . ', ' : null) . $this->state->name;
        });
    }

    protected function type(): Attribute
    {
        return Attribute::get(function () {
            $raw = $this->attributes['type'] ?? null;

            if ($raw instanceof PropertyTypeEnum) {
                return $raw;
            }

            if (is_string($raw) && $raw !== '' && PropertyTypeEnum::hasValue($raw)) {
                return PropertyTypeEnum::fromValue($raw);
            }

            return $this->TransactionType === 'For Lease'
                ? PropertyTypeEnum::RENT()
                : PropertyTypeEnum::SALE();
        });
    }

    protected function typeHtml(): Attribute
    {
        return Attribute::get(fn () => $this->type->label());
    }

    protected function statusHtml(): Attribute
    {
        return Attribute::get(function () {
            if ($this->isSoldHistory()) {
                $label = match (true) {
                    $this->MlsStatus === 'Leased' => __('Leased'),
                    str_contains((string) $this->MlsStatus, 'Sold') => __('Sold'),
                    default => __('Sold'),
                };

                return Html::tag('span', $label, ['class' => 'flag-tag primary status-sold'])->toHtml();
            }

            if ($this->MlsStatus === 'New' && $this->TransactionType) {
                $activeLabel = $this->TransactionType === 'For Lease' ? __('For Lease') : __('For Sale');

                return Html::tag('span', $activeLabel, ['class' => 'flag-tag success status-active'])->toHtml();
            }

            try {
                $status = $this->status;
                if ($status instanceof PropertyStatusEnum && $status->getValue() !== null && $status->getValue() !== '') {
                    return $status->toHtml();
                }
            } catch (\Throwable) {
                // Empty/invalid portal status on some MLS rows — never 500 the page.
            }

            $raw = trim((string) $this->getRawOriginal('status'));
            if ($raw !== '' && PropertyStatusEnum::isValid($raw)) {
                return (new PropertyStatusEnum())->make($raw)->toHtml();
            }

            return Html::tag('span', $this->MlsStatus ?: __('Listing'), ['class' => 'flag-tag secondary'])->toHtml();
        });
    }

    protected function coverImage(): Attribute
    {
        return Attribute::get(function () {
            $listingKey = trim((string) ($this->external_id ?? ''));
            $imageVal = $this->image_val ?? null;

            if ($listingKey !== '') {
                return SerikMediaUrl::mapListingCover($listingKey, $imageVal);
            }

            if (! empty($imageVal) && str_starts_with($imageVal, 'http')) {
                return SerikMediaUrl::normalizeExternalUrl($imageVal, RvMedia::getDefaultImage());
            }

            if ($this->image) {
                return RvMedia::getImageUrl($this->image, 'medium-rectangle', false, RvMedia::getDefaultImage());
            }

            if (class_exists(\Theme\homzen\Supports\TrebPropertyHelper::class) && $this->external_id) {
                $cover = \Theme\homzen\Supports\TrebPropertyHelper::getCoverImage(
                    $this->external_id,
                    $this->image_val
                );

                if ($cover) {
                    return $cover;
                }
            }

            return RvMedia::getDefaultImage();
        });
    }

    protected function categoryName(): Attribute
    {
        return Attribute::get(fn() => $this->category->name);
    }

    protected function imageThumb(): Attribute
    {
        return Attribute::get(fn() => $this->image ? RvMedia::getImageUrl($this->image, 'thumb', false, RvMedia::getDefaultImage()) : null);
    }

    protected function imageSmall(): Attribute
    {
        return Attribute::get(fn() => $this->image ? RvMedia::getImageUrl($this->image, 'small', false, RvMedia::getDefaultImage()) : null);
    }

    protected function priceHtml(): Attribute
    {
        return Attribute::get(function () {
            if (setting('real_estate_hide_price', false)) {
                return '';
            }

            if (!$this->price) {
                return trans('plugins/real-estate::real-estate.contact_for_price');
            }

            $price = $this->price_format;

            if ($this->type == PropertyTypeEnum::RENT) {
                $price .= ' / ' . Str::lower($this->period->shortLabel());
            }

            return $price;
        });
    }

    protected function priceFormat(): Attribute
    {
        return Attribute::get(function () {
            if (setting('real_estate_hide_price', false)) {
                return '';
            }

            if (!$this->price) {
                return trans('plugins/real-estate::real-estate.contact_for_price');
            }

            if ($this->price_formatted) {
                return $this->price_formatted;
            }

            $currency = $this->currency;

            if (!$currency || !$currency->getKey()) {
                $currency = get_application_currency();
            }

            return $this->price_formatted = format_price($this->price, $currency);
        });
    }

    protected function mapIcon(): Attribute
    {
        return Attribute::get(fn() => $this->type_html . ': ' . $this->price_format);
    }

    public function customFields(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'reference', 'reference_type', 'reference_id')->with('customField.options');
    }

    protected function customFieldsArray(): Attribute
    {
        return Attribute::get(fn() => CustomFieldValue::getCustomFieldValuesArray($this));
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function newEloquentBuilder($query): PropertyBuilder
    {
        return new PropertyBuilder($query);
    }

    protected function shortAddress(): Attribute
    {
        return Attribute::get(function () {
            if (!is_plugin_active('location')) {
                return $this->location;
            }

            return implode(', ', array_filter([$this->city->name, $this->state->name]));
        });
    }

    protected function formattedFloorPlans(): Attribute
    {
        return Attribute::get(function () {
            $floorPlan = $this->floor_plans;

            if (!is_array($floorPlan)) {
                $floorPlan = json_decode($floorPlan, true);
            }

            return collect($floorPlan)
                ->filter(fn($floorPlan) => is_array($floorPlan))
                ->map(function ($floorPlan) {
                    $floorPlan = collect($floorPlan)->pluck('value', 'key')->toArray();
                    $bedrooms = (int) Arr::get($floorPlan, 'bedrooms', 0);
                    $bathrooms = (int) Arr::get($floorPlan, 'bathrooms', 0);

                    return [
                        'name' => Arr::get($floorPlan, 'name'),
                        'description' => Arr::get($floorPlan, 'description'),
                        'image' => Arr::get($floorPlan, 'image'),
                        'bedrooms' => $bedrooms === 1 ? trans('plugins/real-estate::property.1_bedroom') : trans('plugins/real-estate::property.bedrooms', ['count' => $bedrooms]),
                        'bathrooms' => $bathrooms === 1 ? trans('plugins/real-estate::property.1_bathroom') : trans('plugins/real-estate::property.bathrooms', ['count' => $bathrooms]),
                    ];
                });
        });
    }

    protected function isPendingModeration(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->exists) {
                return false;
            }

            return !in_array($this->moderation_status, [ModerationStatusEnum::APPROVED, ModerationStatusEnum::REJECTED]);
        });
    }

    protected function displayedExpireDate(): Attribute
    {
        return Attribute::get(function () {
            if ($this->never_expired) {
                return Html::tag('span', trans('plugins/real-estate::property.never_expired_label'), ['class' => 'text-info'])->toHtml();
            }

            if (!$this->expire_date) {
                return '&mdash;';
            }

            if ($this->expire_date->isPast()) {
                return Html::tag('span', $this->expire_date->toDateString(), ['class' => 'text-danger'])->toHtml();
            }

            if (Carbon::now()->diffInDays($this->expire_date) < 3) {
                return Html::tag('span', $this->expire_date->toDateString(), ['class' => 'text-warning'])->toHtml();
            }

            return $this->expire_date->toDateString();
        });
    }

    public function isSoldHistory(): bool
    {
        // Match map/API sold detection — ClosePrice alone must not lock active For Sale
        // listings when AMP leaves stale close data (e.g. C13586122).
        return in_array((string) $this->MlsStatus, [
            'Sold',
            'Sold Conditional',
            'Sold Conditional Escape',
            'Leased',
            'Leased Conditional',
        ], true);
    }

    /*
    |--------------------------------------------------------------------------
    | Laravel Scout / Meilisearch
    |--------------------------------------------------------------------------
    */

    public function searchableAs(): string
    {
        return 'properties';
    }

    public function getScoutKey(): mixed
    {
        return $this->getKey();
    }

    public function getScoutKeyName(): string
    {
        return 'id';
    }

    public function shouldBeSearchable(): bool
    {
        // moderation_status is cast to an enum object; compare by value (not
        // identity) against the 'approved' string constant.
        $moderation = $this->getAttribute('moderation_status');
        $moderationValue = is_object($moderation) && method_exists($moderation, 'getValue')
            ? $moderation->getValue()
            : (string) $moderation;

        // Only publicly visible residential TREB listings belong in the search index.
        if ($moderationValue !== ModerationStatusEnum::APPROVED || empty($this->external_id)) {
            return false;
        }

        if (class_exists(\Theme\homzen\Supports\TrebPropertyHelper::class)) {
            return ! \Theme\homzen\Supports\TrebPropertyHelper::isCommercialSubType($this->PropertySubType);
        }

        return true;
    }

    public function toSearchableArray(): array
    {
        $parts = $this->parseAddressParts();
        $square = $this->numericSquare();
        $lat = (float) $this->latitude;
        $lng = (float) $this->longitude;

        // Use raw column values (getRawOriginal) to bypass the SafeContent /
        // HTMLPurifier cast — purifying 100k+ rows during indexing is both
        // pointlessly slow and unnecessary for a search index.
        $rawName = (string) $this->getRawOriginal('name');
        $rawLocation = (string) $this->getRawOriginal('location');

        $data = [
            'id' => (int) $this->id,
            'external_id' => (string) $this->external_id,
            'name' => $rawName,
            'location' => $rawLocation,
            'street' => $parts['street'],
            'street_number' => $parts['street_number'],
            'street_name' => $parts['street_name'],
            'unit' => $parts['unit'],
            'city' => $parts['city'],
            'community' => $parts['community'],
            'postal_code' => $parts['postal_code'] ?: (string) $this->zip_code,
            'zip_code' => (string) $this->zip_code,
            'broker' => (string) $this->broker,
            'keywords' => trim($rawName . ' ' . $rawLocation),

            'property_sub_type' => (string) $this->PropertySubType,
            'transaction_type' => (string) $this->TransactionType,
            'mls_status' => (string) $this->MlsStatus,
            'status' => is_object($this->getAttribute('status'))
                ? (string) $this->getRawOriginal('status')
                : (string) $this->getAttribute('status'),
            'is_sold' => $this->isSoldHistory(),

            'number_bedroom' => (int) $this->number_bedroom,
            'number_bathroom' => (int) $this->number_bathroom,
            'covered_spaces' => (int) $this->CoveredSpaces,
            'parking_spaces' => (int) $this->ParkingSpaces,

            'price' => (float) $this->price,
            'close_price' => (float) $this->ClosePrice,
            'square' => $square,

            'listing_year' => $this->listingYear(),
            'listing_contract_ts' => optional($this->listing_contract_date)->getTimestamp(),
            'close_ts' => optional($this->close_date)->getTimestamp(),
            'created_ts' => optional($this->created_at)->getTimestamp(),
            'updated_ts' => optional($this->updated_at)->getTimestamp(),
        ];

        // Only index real coordinates — 0/0 or nulls would pollute geo search.
        if ($lat !== 0.0 && $lng !== 0.0 && abs($lat) <= 90 && abs($lng) <= 180) {
            $data['_geo'] = ['lat' => $lat, 'lng' => $lng];
        }

        return $data;
    }

    /**
     * Resilient index write: never let a search-engine outage break a listing
     * save (admin edit or TREB import). Delegates to Scout's real path.
     */
    public function searchable(): void
    {
        try {
            // Prefer sync path — queued Scout jobs were stacking (1700+) with no
            // worker, so new listings never appeared on the map.
            if (config('scout.queue')) {
                $this->newCollection([$this])->searchable();
            } else {
                $this->newCollection([$this])->searchableSync();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function unsearchable(): void
    {
        try {
            if (config('scout.queue')) {
                $this->newCollection([$this])->unsearchable();
            } else {
                $this->newCollection([$this])->unsearchableSync();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function numericSquare(): int
    {
        $square = $this->getRawOriginal('square');

        if (is_numeric($square)) {
            return (int) $square;
        }

        if (is_string($square) && preg_match('/(\d+)/', $square, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    private function listingYear(): ?int
    {
        $date = $this->listing_contract_date ?: $this->created_at;

        if (! $date) {
            return null;
        }

        $year = (int) $date->format('Y');

        // Guard against corrupt future dates (e.g. bad AMP parse -> year 9899).
        return ($year >= 1990 && $year <= ((int) date('Y') + 1)) ? $year : null;
    }

    /**
     * Lightweight parse of an AMP UnparsedAddress:
     * "123 Main St #4, Toronto, ON M5V 1A1".
     */
    private function parseAddressParts(): array
    {
        $result = [
            'street' => '',
            'street_number' => '',
            'street_name' => '',
            'unit' => '',
            'city' => '',
            'community' => '',
            'postal_code' => '',
        ];
        $raw = trim((string) ($this->getRawOriginal('location') ?: $this->getRawOriginal('name')));

        if ($raw === '') {
            return $result;
        }

        if (preg_match('/([A-Za-z]\d[A-Za-z])\s?(\d[A-Za-z]\d)/', $raw, $pm)) {
            $result['postal_code'] = strtoupper($pm[1] . ' ' . $pm[2]);
        }

        $segments = array_values(array_filter(array_map('trim', explode(',', $raw))));

        if (isset($segments[0])) {
            $result['street'] = $segments[0];

            if (preg_match('/(?:#|unit|apt|suite)\s*([A-Za-z0-9\-]+)/i', $segments[0], $um)) {
                $result['unit'] = strtoupper($um[1]);
            }

            // "1713 Minstrel Manor" or "12A - 1713 Minstrel Manor"
            if (preg_match('/(?:^|-\s*)(\d+[A-Za-z]?)\s+(.+)$/u', $segments[0], $sm)) {
                $result['street_number'] = strtoupper($sm[1]);
                $streetName = trim(preg_replace('/(?:#|unit|apt|suite)\s*[A-Za-z0-9\-]+/i', '', $sm[2]) ?? '');
                $result['street_name'] = $streetName;
            }
        }

        if (isset($segments[1])) {
            $result['city'] = $segments[1];
        }

        // Some AMP addresses carry a community as a 3rd segment before province.
        if (isset($segments[2]) && ! preg_match('/\b(ON|ONTARIO)\b/i', $segments[2])) {
            $result['community'] = $segments[2];
        }

        return $result;
    }
}
