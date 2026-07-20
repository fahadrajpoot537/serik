<?php

namespace Botble\RealEstate\Exporters;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\DataSynchronize\Exporter\ExportColumn;
use Botble\DataSynchronize\Exporter\ExportCounter;
use Botble\DataSynchronize\Exporter\Exporter;
use Botble\Media\Facades\RvMedia;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Enums\PropertyPeriodEnum;
use Botble\RealEstate\Enums\PropertyTypeEnum;
use Botble\RealEstate\Models\Property;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PropertyExporter extends Exporter
{
    protected ?int $limit = null;

    protected ?string $type = null;

    protected ?string $status = null;

    protected ?string $moderationStatus = null;

    protected ?string $startDate = null;

    protected ?string $endDate = null;

    protected ?int $projectId = null;

    protected ?int $categoryId = null;

    public function setLimit(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function setModerationStatus(?string $moderationStatus): static
    {
        $this->moderationStatus = $moderationStatus;

        return $this;
    }

    public function setDateRange(?string $startDate, ?string $endDate): static
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        return $this;
    }

    public function setProjectId(?int $projectId): static
    {
        $this->projectId = $projectId;

        return $this;
    }

    public function setCategoryId(?int $categoryId): static
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function getLabel(): string
    {
        return trans('plugins/real-estate::property.properties');
    }

    public function columns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('type')
                ->dropdown(PropertyTypeEnum::labels()),
            ExportColumn::make('description'),
            ExportColumn::make('content'),
            ExportColumn::make('location'),
            ExportColumn::make('images'),
            ExportColumn::make('project')
                ->label('Project'),
            ExportColumn::make('number_bedroom')
                ->label('Number bedroom'),
            ExportColumn::make('number_bathroom')
                ->label('Number bathroom'),
            ExportColumn::make('number_floor')
                ->label('Number floor'),
            ExportColumn::make('square'),
            ExportColumn::make('price'),
            ExportColumn::make('currency')
                ->label('Currency'),
            ExportColumn::make('is_featured')
                ->label('Is Featured?')
                ->boolean(),
            ExportColumn::make('country')
                ->label('Country'),
            ExportColumn::make('state')
                ->label('State'),
            ExportColumn::make('city')
                ->label('City'),
            ExportColumn::make('period')
                ->dropdown(PropertyPeriodEnum::labels()),
            ExportColumn::make('author_id')
                ->label('Author ID'),
            ExportColumn::make('author_type')
                ->label('Author Type'),
            ExportColumn::make('auto_renew')
                ->label('Auto renew')
                ->boolean(),
            ExportColumn::make('never_expired')
                ->label('Never Expired')
                ->boolean(),
            ExportColumn::make('expire_date')
                ->label('Expire Date'),
            ExportColumn::make('latitude'),
            ExportColumn::make('longitude'),
            ExportColumn::make('views'),
            ExportColumn::make('status')
                ->dropdown(BaseStatusEnum::values()),
            ExportColumn::make('moderation_status')
                ->label('Moderation status')
                ->dropdown(ModerationStatusEnum::labels()),
            ExportColumn::make('private_notes')
                ->label('Private Notes'),
            ExportColumn::make('unique_id')
                ->label('Unique ID'),
            ExportColumn::make('video_url')
                ->label('Video URL'),
            ExportColumn::make('video_thumbnail')
                ->label('Video Thumbnail'),
            ExportColumn::make('categories'),
            ExportColumn::make('features'),
            ExportColumn::make('facilities'),
            ExportColumn::make('custom_fields')
                ->label('Custom Fields'),
        ];
    }

    protected function applyFilters(Builder $query): void
    {
        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->moderationStatus) {
            $query->where('moderation_status', $this->moderationStatus);
        }

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', Carbon::parse($this->startDate));
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', Carbon::parse($this->endDate));
        }

        if ($this->projectId) {
            $query->where('project_id', $this->projectId);
        }

        if ($this->categoryId) {
            $query->whereHas('categories', function ($q): void {
                $q->where('re_categories.id', $this->categoryId);
            });
        }

        if ($this->limit) {
            $query->latest()->limit($this->limit);
        } else {
            $query->oldest();
        }
    }

    public function counters(): array
    {
        $query = Property::query();

        $this->applyFilters($query);

        return [
            ExportCounter::make()
                ->label(trans('plugins/real-estate::property.export.total'))
                ->value($query->count()),
        ];
    }

    public function hasDataToExport(): bool
    {
        return Property::query()->exists();
    }

    public function collection(): Collection
    {
        $query = Property::query()
            ->with([
                'project',
                'categories',
                'features',
                'facilities',
                'customFields',
                'metadata',
                'slugable',
            ]);

        $this->applyFilters($query);

        return $query->get()
            ->transform(fn (Property $property) => [
                ...$property->toArray(),
                'expire_date' => $property->expire_date?->format('Y-m-d'),
                'country' => $property->country?->name,
                'state' => $property->state?->name,
                'city' => $property->city?->name,
                'project' => $property->project?->name,
                'currency' => $property->currency?->title ?: cms_currency()->getDefaultCurrency()->title,
                'images' => is_array($property->images) ? implode(',', array_map(fn ($image) => RvMedia::getImageUrl($image), $property->images)) : '',
                'categories' => $property->categories->pluck('name')->implode(', '),
                'features' => $property->features->pluck('name')->implode(', '),
                'facilities' => $property->facilities->map(function ($facility) {
                    return $facility->name . ':' . $facility->pivot->distance;
                })->implode(', '),
                'custom_fields' => $property->customFields->map(function ($field) {
                    return $field->name . ':' . $field->value;
                })->implode(', '),
                'video_url' => $property->getMetaData('video_url', true),
                'video_thumbnail' => $property->getMetaData('video_thumbnail', true),
            ]);
    }

    protected function getView(): string
    {
        return 'plugins/real-estate::properties.export';
    }
}
