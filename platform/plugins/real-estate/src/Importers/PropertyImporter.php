<?php

namespace Botble\RealEstate\Importers;

use Botble\ACL\Models\User;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Facades\BaseHelper;
use Botble\DataSynchronize\Contracts\Importer\WithMapping;
use Botble\DataSynchronize\Importer\ImportColumn;
use Botble\DataSynchronize\Importer\Importer;
use Botble\Location\Models\City;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Enums\PropertyPeriodEnum;
use Botble\RealEstate\Enums\PropertyStatusEnum;
use Botble\RealEstate\Enums\PropertyTypeEnum;
use Botble\RealEstate\Facades\RealEstateHelper;
use Botble\RealEstate\Models\Account;
use Botble\RealEstate\Models\Category;
use Botble\RealEstate\Models\Currency;
use Botble\RealEstate\Models\Facility;
use Botble\RealEstate\Models\Feature;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Botble\Slug\Facades\SlugHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PropertyImporter extends Importer implements WithMapping
{
    public function getLabel(): string
    {
        return trans('plugins/real-estate::property.properties');
    }

    public function columns(): array
    {
        $columns = [
            ImportColumn::make('name')
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('type')
                ->rules(['nullable', 'string', Rule::in(PropertyTypeEnum::values())]),
            ImportColumn::make('description')
                ->rules(['nullable', 'string']),
            ImportColumn::make('content')
                ->rules(['nullable', 'string']),
            ImportColumn::make('location')
                ->rules(['nullable', 'string']),
            ImportColumn::make('images')
                ->rules(['nullable', 'array']),
            ImportColumn::make('project_id')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('number_bedroom')
                ->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('number_bathroom')
                ->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('number_floor')
                ->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('square')
                ->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('price')
                ->rules(['nullable', 'numeric', 'min:0']),
            ImportColumn::make('currency')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('is_featured')
                ->boolean()
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('city')
                ->rules(['nullable', 'string']),
            ImportColumn::make('country')
                ->rules(['nullable', 'string']),
            ImportColumn::make('state')
                ->rules(['nullable', 'string']),
            ImportColumn::make('period')
                ->rules(['nullable', 'string', Rule::in(PropertyPeriodEnum::values())]),
            ImportColumn::make('author_id')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('author_type')
                ->rules(['nullable', 'string']),
            ImportColumn::make('auto_renew')
                ->boolean()
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('never_expired')
                ->boolean()
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('expire_date')
                ->rules(['nullable', 'date']),
            ImportColumn::make('latitude')
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('longitude')
                ->rules(['nullable', 'numeric']),
        ];

        if (RealEstateHelper::isEnabledZipCode()) {
            $columns[] = ImportColumn::make('zip_code')
                ->rules(['nullable', ...BaseHelper::getZipcodeValidationRule(true)]);
        }

        return [
            ...$columns,
            ImportColumn::make('views')
                ->rules(['nullable', 'integer', 'min:0']),
            ImportColumn::make('status')
                ->rules(['nullable', 'string', Rule::in(PropertyStatusEnum::values())]),
            ImportColumn::make('moderation_status')
                ->rules(['nullable', 'string', Rule::in(ModerationStatusEnum::values())]),
            ImportColumn::make('private_notes')
                ->rules(['nullable', 'string']),
            ImportColumn::make('unique_id')
                ->rules(['nullable', 'string']),
            ImportColumn::make('video_url')
                ->rules(['nullable', 'url']),
            ImportColumn::make('video_thumbnail')
                ->rules(['nullable', 'string']),
            ImportColumn::make('categories')
                ->rules(['nullable', 'array']),
            ImportColumn::make('features')
                ->rules(['nullable', 'array']),
            ImportColumn::make('facilities')
                ->rules(['nullable', 'array']),
            ImportColumn::make('custom_fields')
                ->rules(['nullable', 'array']),
        ];
    }

    public function getModel(): string
    {
        return Property::class;
    }

    public function chunkSize(): int
    {
        return 10;
    }

    public function getValidateUrl(): string
    {
        return route('tools.data-synchronize.import.properties.validate');
    }

    public function getImportUrl(): string
    {
        return route('tools.data-synchronize.import.properties.store');
    }

    public function getDownloadExampleUrl(): ?string
    {
        return route('tools.data-synchronize.import.properties.download-example');
    }

    public function getExportUrl(): ?string
    {
        return Auth::user()->hasPermission('property.export')
            ? route('tools.data-synchronize.export.properties.store')
            : null;
    }

    public function examples(): array
    {
        $properties = Property::query()
            ->take(3)
            ->with(['project', 'categories', 'features', 'facilities', 'customFields', 'slugable'])
            ->get()
            ->map(function (Property $property) { // @phpstan-ignore-line
                $data = [
                    'name' => $property->name,
                    'type' => $property->type,
                    'description' => Str::limit($property->description, 100),
                    'content' => Str::limit($property->content, 200),
                    'location' => $property->location,
                    'images' => is_array($property->images) ? implode(',', $property->images) : '',
                    'project' => $property->project?->name,
                    'number_bedroom' => $property->number_bedroom,
                    'number_bathroom' => $property->number_bathroom,
                    'number_floor' => $property->number_floor,
                    'square' => $property->square,
                    'price' => $property->price,
                    'currency' => $property->currency?->title ?: cms_currency()->getDefaultCurrency()->title,
                    'is_featured' => $property->is_featured ? 'Yes' : 'No',
                    'country' => $property->country_name,
                    'state' => $property->state_name,
                    'city' => $property->city_name,
                    'period' => $property->period,
                    'author_id' => $property->author_id,
                    'author_type' => $property->author_type,
                    'auto_renew' => $property->auto_renew ? 'Yes' : 'No',
                    'never_expired' => $property->never_expired ? 'Yes' : 'No',
                    'expire_date' => $property->expire_date?->format('Y-m-d'),
                    'latitude' => $property->latitude,
                    'longitude' => $property->longitude,
                ];

                if (RealEstateHelper::isEnabledZipCode()) {
                    $data['zip_code'] = $property->zip_code;
                }

                return [
                    ...$data,
                    'views' => $property->views,
                    'status' => $property->status,
                    'moderation_status' => $property->moderation_status,
                    'private_notes' => $property->private_notes,
                    'unique_id' => $property->unique_id,
                    'video_url' => $property->getMetaData('video_url', true),
                    'video_thumbnail' => $property->getMetaData('video_thumbnail', true),
                    'categories' => $property->categories->pluck('name')->implode(', '),
                    'features' => $property->features->pluck('name')->implode(', '),
                    'facilities' => $property->facilities->map(function ($facility) {
                        return $facility->name . ':' . $facility->pivot->distance;
                    })->implode(', '),
                    'custom_fields' => $property->customFields->map(function ($field) {
                        return $field->name . ':' . $field->value;
                    })->implode(', '),
                ];
            });

        if ($properties->isNotEmpty()) {
            return $properties->all();
        }

        $examples = [
            [
                'name' => 'Luxury Villa with Ocean View',
                'type' => 'sale',
                'description' => 'Beautiful luxury villa with stunning ocean views and modern amenities.',
                'content' => 'This magnificent villa offers breathtaking ocean views from every room. Features include a gourmet kitchen, spacious living areas, and a private pool.',
                'location' => '123 Ocean Drive, Malibu, CA',
                'images' => 'https://via.placeholder.com/800x600,https://via.placeholder.com/800x600',
                'project' => 'Ocean View Estates',
                'number_bedroom' => 4,
                'number_bathroom' => 3,
                'number_floor' => 2,
                'square' => 3500,
                'price' => 2500000,
                'currency' => 'USD',
                'is_featured' => 'Yes',
                'country' => 'United States',
                'state' => 'California',
                'city' => 'Malibu',
                'period' => 'month',
                'author_id' => 1,
                'author_type' => User::class,
                'auto_renew' => 'No',
                'never_expired' => 'No',
                'expire_date' => '2025-12-31',
                'latitude' => 34.0259,
                'longitude' => -118.7798,
                'views' => 150,
                'status' => BaseStatusEnum::PUBLISHED,
                'moderation_status' => ModerationStatusEnum::APPROVED,
                'private_notes' => 'Premium listing',
                'unique_id' => 'PROP-001',
                'video_url' => 'https://www.youtube.com/watch?v=example',
                'video_thumbnail' => 'https://via.placeholder.com/400x300',
                'categories' => 'Luxury, Residential',
                'features' => 'Swimming Pool, Garden, Garage',
                'facilities' => 'Hospital:2km, School:1km, Shopping Mall:3km',
                'custom_fields' => 'Parking Spaces:3, Year Built:2020',
            ],
            [
                'name' => 'Modern Downtown Apartment',
                'type' => 'rent',
                'description' => 'Stylish apartment in the heart of downtown with city views.',
                'content' => 'Contemporary apartment featuring floor-to-ceiling windows, hardwood floors, and premium finishes throughout.',
                'location' => '456 City Center Blvd, Downtown',
                'images' => 'https://via.placeholder.com/800x600',
                'project' => 'City Center Towers',
                'number_bedroom' => 2,
                'number_bathroom' => 2,
                'number_floor' => 1,
                'square' => 1200,
                'price' => 3500,
                'currency' => 'USD',
                'is_featured' => 'No',
                'country' => 'United States',
                'state' => 'New York',
                'city' => 'New York',
                'period' => 'month',
                'author_id' => 1,
                'author_type' => User::class,
                'auto_renew' => 'Yes',
                'never_expired' => 'Yes',
                'expire_date' => null,
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'views' => 89,
                'status' => BaseStatusEnum::PUBLISHED,
                'moderation_status' => ModerationStatusEnum::APPROVED,
                'private_notes' => '',
                'unique_id' => 'PROP-002',
                'video_url' => '',
                'video_thumbnail' => '',
                'categories' => 'Apartment, Urban',
                'features' => 'Balcony, Gym Access',
                'facilities' => 'Metro Station:0.5km, Restaurant:0.2km',
                'custom_fields' => 'Pet Friendly:Yes, Furnished:No',
            ],
        ];

        if (RealEstateHelper::isEnabledZipCode()) {
            $examples[0]['zip_code'] = '90265';
            $examples[1]['zip_code'] = '10001';
        }

        return $examples;
    }

    public function map(mixed $row): array
    {
        $categories = [];
        $features = [];
        $facilities = [];
        $customFields = [];

        if ($categoriesStr = Arr::get($row, 'categories')) {
            $categories = $this->parseIdsFromString($categoriesStr, Category::class);
        }

        if ($featuresStr = Arr::get($row, 'features')) {
            $features = $this->parseIdsFromString($featuresStr, Feature::class);
        }

        if ($facilitiesStr = Arr::get($row, 'facilities')) {
            $facilitiesArray = explode(',', $facilitiesStr);
            foreach ($facilitiesArray as $facility) {
                $facilityExplode = explode(':', trim($facility));
                if (count($facilityExplode) === 2) {
                    $facilityName = trim($facilityExplode[0]);
                    $distance = trim($facilityExplode[1]);
                    $facilityModel = Facility::query()->firstOrCreate(['name' => $facilityName]);
                    if ($facilityModel) {
                        $facilities[$facilityModel->id] = $distance;
                    }
                }
            }
        }

        if ($customFieldsStr = Arr::get($row, 'custom_fields')) {
            $fieldsArray = explode(',', $customFieldsStr);
            foreach ($fieldsArray as $field) {
                $fieldExplode = explode(':', trim($field));
                if (count($fieldExplode) === 2) {
                    $customFields[] = [
                        'name' => trim($fieldExplode[0]),
                        'value' => trim($fieldExplode[1]),
                    ];
                }
            }
        }

        $projectId = null;
        if ($projectName = Arr::get($row, 'project')) {
            $project = Project::query()->where('name', $projectName)->first();
            if ($project) {
                $projectId = $project->id;
            }
        }

        $countryId = null;
        $stateId = null;
        $cityId = null;

        if (is_plugin_active('location')) {
            if ($countryName = Arr::get($row, 'country')) {
                $country = Country::query()->where('name', $countryName)->first();
                if ($country) {
                    $countryId = $country->id;
                }
            }

            if ($stateName = Arr::get($row, 'state')) {
                $state = State::query()->where('name', $stateName)->first();
                if ($state) {
                    $stateId = $state->id;
                }
            }

            if ($cityName = Arr::get($row, 'city')) {
                $city = City::query()->where('name', $cityName)->first();
                if ($city) {
                    $cityId = $city->id;
                }
            }
        }

        $currencyId = null;

        if ($currencyName = Arr::get($row, 'currency')) {
            $currency = Currency::query()->where('title', strtoupper($currencyName))->first();
            if ($currency) {
                $currencyId = $currency->id;
            }
        }

        $images = [];
        if ($imagesStr = Arr::get($row, 'images')) {
            $images = array_filter(explode(',', $imagesStr));
        }

        $authorType = Arr::get($row, 'author_type');
        if ($authorType) {
            if (strtolower($authorType) === 'user') {
                $authorType = User::class;
            } elseif (strtolower($authorType) === 'account') {
                $authorType = Account::class;
            } elseif (! class_exists($authorType)) {
                $authorType = User::class;
            }
        }

        unset($row['country']);
        unset($row['state']);
        unset($row['city']);
        unset($row['currency']);

        return [
            ...$row,
            'categories' => $categories,
            'features' => $features,
            'facilities' => $facilities,
            'custom_fields' => $customFields,
            'project_id' => $projectId,
            'country_id' => $countryId,
            'state_id' => $stateId,
            'city_id' => $cityId,
            'currency_id' => $currencyId,
            'images' => $images,
            'is_featured' => $this->convertToBoolean(Arr::get($row, 'is_featured')),
            'auto_renew' => $this->convertToBoolean(Arr::get($row, 'auto_renew')),
            'never_expired' => $this->convertToBoolean(Arr::get($row, 'never_expired')),
            'author_type' => $authorType,
        ];
    }

    public function handle(array $data): int
    {
        $count = 0;

        foreach ($data as $row) {
            $uniqueId = Arr::get($row, 'unique_id');
            $property = null;

            if ($uniqueId) {
                $property = Property::query()->where('unique_id', $uniqueId)->first();
            }

            if (! $property) {
                $property = Property::query()->where('name', Arr::get($row, 'name'))->first();
            }

            $propertyData = Arr::except($row, ['categories', 'features', 'facilities', 'custom_fields', 'video_url', 'video_thumbnail']);

            if (! empty($propertyData['expire_date'])) {
                try {
                    $propertyData['expire_date'] = Carbon::parse($propertyData['expire_date'])->format('Y-m-d');
                } catch (Exception) {
                    unset($propertyData['expire_date']);
                }
            } elseif (isset($propertyData['expire_date'])) {
                $propertyData['expire_date'] = null;
            }

            $propertyData['unique_id'] = $uniqueId ?: null;
            $propertyData['period'] = $propertyData['period'] ?? null;

            if (! $property) {
                $property = new Property();
                $count++;
            }

            if (! Arr::get($propertyData, 'author_id')) {
                $propertyData['author_id'] = Auth::id();
                $propertyData['author_type'] = User::class;
            } else {
                if (! Arr::get($propertyData, 'author_type') || ! class_exists(Arr::get($propertyData, 'author_type'))) {
                    $propertyData['author_type'] = User::class;
                }
            }

            $property->forceFill($propertyData);

            /**
             * @var Property $property
             */
            $property->save();

            if ($property->wasRecentlyCreated) {
                SlugHelper::createSlug($property);
            }

            if ($categories = Arr::get($row, 'categories')) {
                $property->categories()->sync($categories);
            }

            if ($features = Arr::get($row, 'features')) {
                $property->features()->sync($features);
            }

            if ($facilities = Arr::get($row, 'facilities')) {
                $property->facilities()->detach();
                foreach ($facilities as $facilityId => $distance) {
                    $property->facilities()->attach($facilityId, ['distance' => $distance]);
                }
            }

            if ($customFields = Arr::get($row, 'custom_fields')) {
                $property->customFields()->delete();
                foreach ($customFields as $field) {
                    $property->customFields()->create([
                        'name' => $field['name'],
                        'value' => $field['value'],
                    ]);
                }
            }

            $property->metadata()->whereIn('meta_key', ['video_url', 'video_thumbnail'])->delete();

            if ($videoUrl = Arr::get($row, 'video_url')) {
                $property->metadata()->create([
                    'meta_key' => 'video_url',
                    'meta_value' => $videoUrl,
                ]);
            }

            if ($videoThumbnail = Arr::get($row, 'video_thumbnail')) {
                $property->metadata()->create([
                    'meta_key' => 'video_thumbnail',
                    'meta_value' => $videoThumbnail,
                ]);
            }

            $request = new Request();
            $request->merge([
                ...$row,
                'slug' => Str::slug($property->name),
                'is_slug_editable' => true,
            ]);

            event(new CreatedContentEvent(PROPERTY_MODULE_SCREEN_NAME, $request, $property));
        }

        return $count;
    }

    protected function parseIdsFromString(string $items, string $modelClass): array
    {
        return str($items)
            ->explode(',')
            ->map(function ($item) use ($modelClass) {
                $model = $modelClass::query()->firstOrCreate(['name' => trim($item)]);

                if (SlugHelper::isSupportedModel($modelClass) && $model->wasRecentlyCreated) {
                    SlugHelper::createSlug($model);
                }

                return $model->getKey();
            })
            ->all();
    }

    protected function convertToBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower($value), ['yes', '1', 'true', 'on']);
    }

}
