<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;
use Botble\Location\Models\City;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Botble\Media\Facades\RvMedia;
use Botble\RealEstate\Enums\ProjectStatusEnum;
use Botble\RealEstate\Models\Traits\UniqueId;
use Botble\RealEstate\QueryBuilders\ProjectBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;

/**
 * @method static \Botble\RealEstate\QueryBuilders\ProjectBuilder<static> query()
 */
class Project extends BaseModel
{
    use UniqueId;

    protected $table = 're_projects';

    protected $fillable = [
        'name',
        'description',
        'content',
        'location',
        'images',
        'status',
        'is_featured',
        'featured_priority',
        'investor_id',
        'number_block',
        'number_floor',
        'number_flat',
        'date_finish',
        'date_sell',
        'price_from',
        'price_to',
        'currency_id',
        'city_id',
        'state_id',
        'country_id',
        'author_id',
        'author_type',
        'category_id',
        'latitude',
        'longitude',
        'zip_code',
        'unique_id',
        'private_notes',
        'floor_plans',
    ];

    protected $casts = [
        'status' => ProjectStatusEnum::class,
        'date_finish' => 'date',
        'date_sell' => 'date',
        'price_from' => 'float',
        'price_to' => 'float',
        'number_block' => 'int',
        'number_float' => 'int',
        'number_flat' => 'int',
        'views' => 'int',
        'is_featured' => 'boolean',
        'featured_priority' => 'int',
        'name' => SafeContent::class,
        'description' => SafeContent::class,
        'location' => SafeContent::class,
        'private_notes' => SafeContent::class,
        'images' => 'json',
        'floor_plans' => 'array',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Project $project): void {
            $project->categories()->detach();
            $project->customFields()->delete();
            $project->reviews()->delete();
            $project->features()->detach();
            $project->facilities()->detach();
            $project->properties()->update(['project_id' => 0]);
            $project->metadata()->delete();
        });
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @deprecated
     */
    public function property(): HasMany
    {
        return $this->properties();
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'project_id');
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class)->withDefault();
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 're_project_features', 'project_id', 'feature_id');
    }

    public function facilities(): BelongsToMany
    {
        return $this->morphToMany(Facility::class, 'reference', 're_facilities_distances')->withPivot('distance');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 're_project_categories');
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

    protected function image(): Attribute
    {
        return Attribute::make(
            get: function () {
                return Arr::first($this->images) ?? null;
            },
        );
    }

    protected function address(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->location;
            },
        );
    }

    protected function category(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->categories->first() ?: new Category();
            },
        );
    }

    protected function statusHtml(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->status->toHtml();
            },
        );
    }

    protected function categoryName(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->category->name;
            },
        );
    }

    protected function imageThumb(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->image ? RvMedia::getImageUrl($this->image, 'thumb', false, RvMedia::getDefaultImage()) : null;
            },
        );
    }

    protected function imageSmall(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->image ? RvMedia::getImageUrl($this->image, 'small', false, RvMedia::getDefaultImage()) : null;
            },
        );
    }

    protected function mapIcon(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->name;
            },
        );
    }

    protected function cityName(): Attribute
    {
        return Attribute::get(function () {
            if (! is_plugin_active('location')) {
                return $this->location;
            }

            return ($this->city->name ? $this->city->name . ', ' : null) . $this->state->name;
        });
    }

    public function customFields(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'reference', 'reference_type', 'reference_id')->with('customField.options');
    }

    protected function customFieldsArray(): Attribute
    {
        return Attribute::make(
            get: function () {
                return CustomFieldValue::getCustomFieldValuesArray($this);
            },
        );
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function newEloquentBuilder($query): ProjectBuilder
    {
        return new ProjectBuilder($query);
    }

    protected function formattedPrice(): Attribute
    {
        return Attribute::get(function () {
            if (setting('real_estate_hide_price', false)) {
                return '';
            }

            $text = '';

            if ($this->price_from) {
                $text .= format_price($this->price_from, $this->currency);
            }

            if ($this->price_to) {
                $text .= sprintf(' - %s', format_price($this->price_to, $this->currency));
            }

            return $text;
        });
    }

    protected function priceFromFormatted(): Attribute
    {
        return Attribute::get(function () {
            if (setting('real_estate_hide_price', false) || ! $this->price_from) {
                return null;
            }

            return format_price($this->price_from, $this->currency ?: get_application_currency());
        });
    }

    protected function priceToFormatted(): Attribute
    {
        return Attribute::get(function () {
            if (setting('real_estate_hide_price', false) || ! $this->price_to) {
                return null;
            }

            return format_price($this->price_to, $this->currency ?: get_application_currency());
        });
    }

    protected function priceHtml(): Attribute
    {
        return Attribute::get(function () {
            if (setting('real_estate_hide_price', false)) {
                return '';
            }

            if (! $this->price) {
                return trans('plugins/real-estate::real-estate.contact_for_price');
            }

            return $this->formatted_price;
        });
    }

    protected function priceFormat(): Attribute
    {
        return Attribute::get(function () {
            return $this->formatted_price;
        });
    }

    protected function shortAddress(): Attribute
    {
        return Attribute::get(function () {
            if (! is_plugin_active('location')) {
                return $this->location;
            }

            return implode(', ', array_filter([$this->city->name, $this->state->name]));
        });
    }

    protected function formattedFloorPlans(): Attribute
    {
        return Attribute::get(function () {
            $floorPlan = $this->floor_plans;

            if (! is_array($floorPlan)) {
                $floorPlan = json_decode($floorPlan, true);
            }

            return collect($floorPlan)
                ->filter(fn ($floorPlan) => is_array($floorPlan))
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
}
