<?php

namespace Botble\RealEstate\Exporters;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\DataSynchronize\Exporter\ExportColumn;
use Botble\DataSynchronize\Exporter\ExportCounter;
use Botble\DataSynchronize\Exporter\Exporter;
use Botble\Media\Facades\RvMedia;
use Botble\RealEstate\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProjectExporter extends Exporter
{
    protected ?int $limit = null;

    protected ?string $status = null;

    protected ?string $startDate = null;

    protected ?string $endDate = null;

    protected ?int $investorId = null;

    protected ?int $categoryId = null;

    protected ?bool $isFeatured = null;

    public function setLimit(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function setDateRange(?string $startDate, ?string $endDate): static
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        return $this;
    }

    public function setInvestorId(?int $investorId): static
    {
        $this->investorId = $investorId;

        return $this;
    }

    public function setCategoryId(?int $categoryId): static
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function setIsFeatured(?bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }
    public function getLabel(): string
    {
        return trans('plugins/real-estate::project.projects');
    }

    public function columns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('description'),
            ExportColumn::make('content'),
            ExportColumn::make('images'),
            ExportColumn::make('location'),
            ExportColumn::make('investor')
                ->label('Investor'),
            ExportColumn::make('number_block')
                ->label('Number Block'),
            ExportColumn::make('number_floor')
                ->label('Number Floor'),
            ExportColumn::make('number_flat')
                ->label('Number Flat'),
            ExportColumn::make('is_featured')
                ->label('Is Featured?')
                ->boolean(),
            ExportColumn::make('date_finish')
                ->label('Date Finish'),
            ExportColumn::make('date_sell')
                ->label('Date Sell'),
            ExportColumn::make('price_from')
                ->label('Price from'),
            ExportColumn::make('price_to')
                ->label('Price to'),
            ExportColumn::make('currency')
                ->label('Currency'),
            ExportColumn::make('country')
                ->label('Country'),
            ExportColumn::make('state')
                ->label('State'),
            ExportColumn::make('city')
                ->label('City'),
            ExportColumn::make('author_id')
                ->label('Author ID'),
            ExportColumn::make('author_type')
                ->label('Author Type'),
            ExportColumn::make('longitude'),
            ExportColumn::make('latitude'),
            ExportColumn::make('status')
                ->dropdown(BaseStatusEnum::values()),
            ExportColumn::make('categories'),
            ExportColumn::make('features'),
            ExportColumn::make('facilities'),
            ExportColumn::make('custom_fields')
                ->label('Custom Fields'),
            ExportColumn::make('unique_id')
                ->label('Unique ID'),
            ExportColumn::make('video_url')
                ->label('Video URL'),
            ExportColumn::make('video_thumbnail')
                ->label('Video Thumbnail'),
        ];
    }

    protected function applyFilters(Builder $query): void
    {
        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->isFeatured !== null) {
            $query->where('is_featured', $this->isFeatured);
        }

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', Carbon::parse($this->startDate));
        }

        if ($this->endDate) {
            $query->whereDate('created_at', '<=', Carbon::parse($this->endDate));
        }

        if ($this->investorId) {
            $query->where('investor_id', $this->investorId);
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
        $query = Project::query();

        $this->applyFilters($query);

        return [
            ExportCounter::make()
                ->label(trans('plugins/real-estate::project.export.total'))
                ->value($query->count()),
        ];
    }

    public function hasDataToExport(): bool
    {
        return Project::query()->exists();
    }

    public function collection(): Collection
    {
        $query = Project::query()
            ->with([
                'investor',
                'categories',
                'features',
                'facilities',
                'customFields',
                'metadata',
                'slugable',
            ]);

        $this->applyFilters($query);

        return $query->get()
            ->transform(fn (Project $project) => [
                ...$project->toArray(),
                'country' => $project->country?->name,
                'state' => $project->state?->name,
                'city' => $project->city?->name,
                'date_finish' => $project->date_finish?->toDateString(),
                'date_sell' => $project->date_sell?->toDateString(),
                'investor' => $project->investor?->name ?: $project->investor_id,
                'currency' => $project->currency?->title ?: cms_currency()->getDefaultCurrency()->title,
                'images' => is_array($project->images) ? implode(',', array_map(fn ($image) => RvMedia::getImageUrl($image), $project->images)) : '',
                'categories' => $project->categories->pluck('name')->implode(', '),
                'features' => $project->features->pluck('name')->implode(', '),
                'facilities' => $project->facilities->map(function ($facility) {
                    return $facility->name . ':' . $facility->pivot->distance;
                })->implode(', '),
                'custom_fields' => $project->customFields->map(function ($field) {
                    return $field->name . ':' . $field->value;
                })->implode(', '),
                'video_url' => $project->getMetaData('video_url', true),
                'video_thumbnail' => $project->getMetaData('video_thumbnail', true),
            ]);
    }

    protected function getView(): string
    {
        return 'plugins/real-estate::projects.export';
    }
}
