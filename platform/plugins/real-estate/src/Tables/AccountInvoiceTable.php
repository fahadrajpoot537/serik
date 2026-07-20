<?php

namespace Botble\RealEstate\Tables;

use Botble\RealEstate\Models\Invoice;
use Botble\Table\Actions\ViewAction;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\LinkableColumn;
use Botble\Table\Columns\StatusColumn;
use Illuminate\Database\Eloquent\Builder;

class AccountInvoiceTable extends InvoiceTable
{
    public function setup(): void
    {
        $this
            ->model(Invoice::class)
            ->addActions([
                ViewAction::make('analytics')
                    ->route('public.account.invoices.show'),
            ])
            ->queryUsing(function (Builder $query) {
                return $query
                    ->select([
                        'id',
                        'code',
                        'amount',
                        'created_at',
                        'status',
                    ])
                    ->where('account_id', auth('account')->id());
            })
            ->addColumns([
                IdColumn::make(),
                LinkableColumn::make('code')
                    ->title(trans('plugins/real-estate::invoice.code'))
                    ->route('public.account.invoices.show')
                    ->alignLeft(),
                FormattedColumn::make('amount')
                    ->title(trans('plugins/real-estate::invoice.amount'))
                    ->alignLeft()
                    ->getValueUsing(function (FormattedColumn $column) {
                        return format_price($column->getItem()->amount);
                    }),
                CreatedAtColumn::make(),
                StatusColumn::make(),
            ]);
    }
}
