<?php

namespace Botble\RealEstate\Tables\Fronts;

use Botble\RealEstate\Tables\ReviewTable as BaseReviewTable;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;

class ReviewTable extends BaseReviewTable
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setView('plugins/real-estate::account.table.base')
            ->queryUsing(function ($query) {
                return $query
                    ->select([
                        'id',
                        'reviewable_type',
                        'reviewable_id',
                        'star',
                        'content',
                        'account_id',
                        'status',
                        'created_at',
                    ])
                    ->with(['author', 'reviewable'])
                    ->where('account_id', auth('account')->id());
            })
            ->removeColumns()
            ->addColumns([
                IdColumn::make(),
                FormattedColumn::make('reviewable')
                    ->title(trans('plugins/real-estate::review.reviewable'))
                    ->alignLeft()
                    ->orderable(false)
                    ->searchable(false)
                    ->getValueUsing(function (FormattedColumn $column) {
                        $item = $column->getItem();

                        if (! $item->reviewable_id || ! $item->reviewable?->getKey()) {
                            return '&mdash;';
                        }

                        return view('plugins/real-estate::account.reviews.reviewable', ['item' => $item])->render();
                    }),
                FormattedColumn::make('star')
                    ->title(trans('plugins/real-estate::review.star'))
                    ->getValueUsing(function (FormattedColumn $column) {
                        $item = $column->getItem();

                        return view('plugins/real-estate::partials.review-star', ['star' => $item->star])->render();
                    }),
                FormattedColumn::make('content')
                    ->title(trans('plugins/real-estate::review.content'))
                    ->getValueUsing(function (FormattedColumn $column) {
                        $item = $column->getItem();

                        return $item->content;
                    }),
                CreatedAtColumn::make(),
            ])
            ->removeActions(['edit', 'delete']);
    }
}
