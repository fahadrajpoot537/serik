<?php

namespace Botble\RealEstate\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\RealEstate\Models\PropertyVisit;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\Action;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Illuminate\Support\Facades\DB;

class PropertyVisitTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(PropertyVisit::class)
            ->addActions([
                Action::make('approve-delete')
                    ->label(trans('plugins/real-estate::property-visit.approve_delete'))
                    ->color('warning')
                    ->icon('ti ti-trash')
                    ->route('property-visit.approve-delete')
                    ->action('POST')
                    ->permission('property-visit.edit')
                    ->renderUsing(function (Action $action, $content) {
                        $item = $action->getItem();

                        if (! $item->delete_requested_at || $item->trashed()) {
                            return '';
                        }

                        return $content;
                    }),
                DeleteAction::make()->route('property-visit.destroy'),
            ])
            ->addBulkActions([
                DeleteBulkAction::make()->permission('property-visit.destroy'),
            ])
            ->addColumns([
                IdColumn::make(),
                FormattedColumn::make('account_id')
                    ->title(trans('plugins/real-estate::property-visit.account'))
                    ->alignLeft()
                    ->getValueUsing(function (FormattedColumn $column) {
                        $item = $column->getItem();

                        if (! $item->account_id || ! $item->account?->id) {
                            return '&mdash;';
                        }

                        return Html::link(
                            route('account.edit', $item->account->id),
                            BaseHelper::clean($item->account->name)
                        )->toHtml();
                    }),
                FormattedColumn::make('property_name')
                    ->title(trans('plugins/real-estate::property-visit.property'))
                    ->alignLeft()
                    ->getValueUsing(function (FormattedColumn $column) {
                        $item = $column->getItem();
                        $label = BaseHelper::clean($item->property_name ?: $item->listing_key);

                        if ($item->property_id) {
                            return Html::link(route('property.edit', $item->property_id), $label)->toHtml();
                        }

                        return $label;
                    }),
                FormattedColumn::make('listing_key')
                    ->title(trans('plugins/real-estate::property-visit.listing_key')),
                FormattedColumn::make('property_location')
                    ->title(trans('plugins/real-estate::property-visit.location'))
                    ->getValueUsing(fn (FormattedColumn $column) => BaseHelper::clean($column->getItem()->property_location ?: '—')),
                FormattedColumn::make('property_price')
                    ->title(trans('plugins/real-estate::property-visit.price'))
                    ->getValueUsing(function (FormattedColumn $column) {
                        $item = $column->getItem();
                        $price = $item->close_price > 0 ? $item->close_price : $item->property_price;

                        return $price > 0 ? number_format($price) : '—';
                    }),
                FormattedColumn::make('mls_status')
                    ->title(trans('plugins/real-estate::property-visit.mls_status')),
                FormattedColumn::make('view_count')
                    ->title(trans('plugins/real-estate::property-visit.views')),
                FormattedColumn::make('last_viewed_at')
                    ->title(trans('plugins/real-estate::property-visit.last_viewed'))
                    ->getValueUsing(fn (FormattedColumn $column) => $column->getItem()->last_viewed_at?->format('Y-m-d H:i') ?: '—'),
                FormattedColumn::make('delete_requested_at')
                    ->title(trans('plugins/real-estate::property-visit.delete_requested'))
                    ->getValueUsing(function (FormattedColumn $column) {
                        $item = $column->getItem();

                        return $item->delete_requested_at
                            ? '<span class="badge bg-warning text-dark">' . $item->delete_requested_at->format('Y-m-d H:i') . '</span>'
                            : '—';
                    }),
                CreatedAtColumn::make(),
            ])
            ->queryUsing(function ($query) {
                return $query
                    ->with(['account', 'property'])
                    ->withTrashed()
                    ->select([
                        'id',
                        'account_id',
                        'property_id',
                        'listing_key',
                        'property_name',
                        'property_location',
                        'property_price',
                        'close_price',
                        'mls_status',
                        'view_count',
                        'last_viewed_at',
                        'delete_requested_at',
                        'created_at',
                        'deleted_at',
                    ]);
            })
            ->onAjax(function (self $table) {
                return $table->toJson(
                    $table
                        ->table
                        ->eloquent($table->query())
                        ->filter(function ($query) {
                            $keyword = request()->input('search.value');

                            if (! $keyword) {
                                return $query;
                            }

                            return $query
                                ->where(function ($searchQuery) use ($keyword) {
                                    $searchQuery
                                        ->where('listing_key', 'LIKE', '%' . $keyword . '%')
                                        ->orWhere('property_name', 'LIKE', '%' . $keyword . '%')
                                        ->orWhere('property_location', 'LIKE', '%' . $keyword . '%')
                                        ->orWhereHas('account', function ($subQuery) use ($keyword) {
                                            $subQuery
                                                ->where('first_name', 'LIKE', '%' . $keyword . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $keyword . '%')
                                                ->orWhere(DB::raw('CONCAT(first_name, " ", last_name)'), 'LIKE', '%' . $keyword . '%');
                                        });
                                });
                        })
                );
            });
    }
}
