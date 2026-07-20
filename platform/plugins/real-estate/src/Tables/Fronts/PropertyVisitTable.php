<?php

namespace Botble\RealEstate\Tables\Fronts;

use Botble\Base\Facades\BaseHelper;
use Botble\RealEstate\Models\PropertyVisit;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\Action;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;

class PropertyVisitTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(PropertyVisit::class)
            ->setView('plugins/real-estate::account.table.base')
            ->addActions([
                Action::make('request-delete')
                    ->label(trans('plugins/real-estate::property-visit.request_delete'))
                    ->color('danger')
                    ->icon('ti ti-trash')
                    ->route('public.account.visits.request-delete')
                    ->action('POST'),
            ])
            ->addColumns([
                IdColumn::make(),
                FormattedColumn::make('property_name')
                    ->title(trans('plugins/real-estate::property-visit.property'))
                    ->alignLeft()
                    ->getValueUsing(fn (FormattedColumn $column) => BaseHelper::clean($column->getItem()->property_name ?: $column->getItem()->listing_key)),
                FormattedColumn::make('property_location')
                    ->title(trans('plugins/real-estate::property-visit.location'))
                    ->getValueUsing(fn (FormattedColumn $column) => BaseHelper::clean($column->getItem()->property_location ?: '—')),
                FormattedColumn::make('mls_status')
                    ->title(trans('plugins/real-estate::property-visit.mls_status')),
                FormattedColumn::make('view_count')
                    ->title(trans('plugins/real-estate::property-visit.views')),
                FormattedColumn::make('last_viewed_at')
                    ->title(trans('plugins/real-estate::property-visit.last_viewed'))
                    ->getValueUsing(fn (FormattedColumn $column) => $column->getItem()->last_viewed_at?->format('Y-m-d H:i') ?: '—'),
                CreatedAtColumn::make(),
            ])
            ->queryUsing(function ($query) {
                return $query
                    ->where('account_id', auth('account')->id())
                    ->whereNull('delete_requested_at')
                    ->select([
                        'id',
                        'property_id',
                        'listing_key',
                        'property_name',
                        'property_location',
                        'mls_status',
                        'view_count',
                        'last_viewed_at',
                        'created_at',
                    ]);
            });
    }
}
