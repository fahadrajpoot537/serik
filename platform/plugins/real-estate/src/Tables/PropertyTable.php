<?php

namespace Botble\RealEstate\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\RealEstate\Enums\ModerationStatusEnum;
use Botble\RealEstate\Enums\PropertyStatusEnum;
use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\BulkChanges\CreatedAtBulkChange;
use Botble\Table\BulkChanges\IsFeaturedBulkChange;
use Botble\Table\BulkChanges\NameBulkChange;
use Botble\Table\BulkChanges\SelectBulkChange;
use Botble\Table\BulkChanges\StatusBulkChange;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\EnumColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\ImageColumn;
use Botble\Table\Columns\NameColumn;
use Botble\Table\Columns\StatusColumn;
use Botble\Table\HeaderActions\CreateHeaderAction;
use Botble\Table\HeaderActions\HeaderAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation as EloquentRelation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Validation\Rule;

class PropertyTable extends TableAbstract
{
    public function setup(): void
    {
        $this->defaultSortColumnName = 'created_at';

        $this
            ->model(Property::class)
            ->addHeaderAction(CreateHeaderAction::make()->route('property.create'))
            ->when($this->hasPermission('tools.data-synchronize.import.properties.index'), function ($table) {
                return $table->addHeaderAction(
                    HeaderAction::make('import')
                        ->route('tools.data-synchronize.import.properties.index')
                        ->icon('ti ti-upload')
                        ->label(trans('plugins/real-estate::property.import_properties'))
                        ->color('btn-info')
                );
            })
            ->when($this->hasPermission('tools.data-synchronize.export.properties.index'), function ($table) {
                return $table->addHeaderAction(
                    HeaderAction::make('export')
                        ->route('tools.data-synchronize.export.properties.index')
                        ->icon('ti ti-download')
                        ->label(trans('plugins/real-estate::property.export_properties'))
                        ->color('btn-success')
                );
            })
            ->addActions([
                EditAction::make()->route('property.edit'),
                DeleteAction::make()->route('property.destroy'),
            ])
            ->addColumns([
                IdColumn::make(),
                ImageColumn::make()
                    ->searchable(false)
                    ->orderable(false),
                NameColumn::make()->route('property.edit'),
                FormattedColumn::make('views')
                    ->title(trans('plugins/real-estate::property.views'))
                    ->getValueUsing(function (FormattedColumn $column) {
                        return number_format($column->getItem()->views);
                    }),
                FormattedColumn::make('unique_id')
                    ->title(trans('plugins/real-estate::property.unique_id'))
                    ->getValueUsing(function (FormattedColumn $column) {
                        return BaseHelper::clean($column->getItem()->unique_id ?: '&mdash;');
                    }),
                CreatedAtColumn::make(),
                FormattedColumn::make('expire_date')
                    ->getValueUsing(function (FormattedColumn $column) {
                        return $column->getItem()->displayed_expire_date;
                    }),
                StatusColumn::make(),
                EnumColumn::make('moderation_status')
                    ->title(trans('plugins/real-estate::property.moderation_status'))
                    ->width(150),
            ])
            ->addBulkActions([
                DeleteBulkAction::make()->permission('property.destroy'),
            ])
            ->addBulkChanges([
                NameBulkChange::make(),
                StatusBulkChange::make()
                    ->choices(PropertyStatusEnum::labels())
                    ->validate(['required', Rule::in(PropertyStatusEnum::values())]),
                StatusBulkChange::make()
                    ->name('moderation_status')
                    ->title(trans('plugins/real-estate::property.moderation_status'))
                    ->choices(ModerationStatusEnum::labels())
                    ->validate(['required', Rule::in(ModerationStatusEnum::values())]),
                SelectBulkChange::make()
                    ->name('project_id')
                    ->title(trans('plugins/real-estate::property.form.project'))
                    ->searchable()
                    ->choices(fn () => Project::query()->pluck('name', 'id')->all()),
                CreatedAtBulkChange::make(),
                IsFeaturedBulkChange::make(),
            ])
            ->queryUsing(function (Builder $query) {
                return $query
                    ->select([
                        'id',
                        'name',
                        'images',
                        'views',
                        'status',
                        'moderation_status',
                        'never_expired',
                        'expire_date',
                        'created_at',
                        'unique_id',
                        'location',
                        'zip_code',
                    ]);
            })
            ->onAjax(function (self $table) {
                return $table->toJson(
                    $table
                        ->table
                        ->eloquent($table->query())
                        ->filter(function ($query) {
                            if ($keyword = $this->request->input('search.value')) {
                                $raw = trim((string) $keyword);
                                $search = app(\Botble\RealEstate\Services\PropertySearchService::class);

                                if ($raw !== '' && $search->constrainQueryToKeyword($query, $raw, 200)) {
                                    return $query;
                                }

                                // Meili down: exact/prefix on indexed keys only — no %name% scans.
                                return $query->where(function ($q) use ($raw) {
                                    $q->where('external_id', 'like', $raw . '%')
                                        ->orWhere('unique_id', 'like', $raw . '%')
                                        ->orWhere('zip_code', 'like', $raw . '%');
                                });
                            }

                            return $query;
                        })
                );
            })
            ->onFilterQuery(
                function (
                    EloquentBuilder|QueryBuilder|EloquentRelation $query,
                    string $key,
                    string $operator,
                    ?string $value
                ) {
                    if ($key == 'status') {
                        switch ($value) {
                            case 'expired':
                                // @phpstan-ignore-next-line
                                return $query->expired();
                            case 'active':
                                // @phpstan-ignore-next-line
                                return $query->active();
                        }
                    }

                    return false;
                }
            )
            ->onSavingBulkChangeItem(function (Property $item, string $inputKey, ?string $inputValue) {
                if ($inputKey === 'moderation_status') {
                    $item->moderation_status = $inputValue;
                    $item->save();

                    return $item;
                }

                return null;
            });
    }
}
