<?php

namespace Botble\RealEstate\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\Media\Facades\RvMedia;
use Botble\RealEstate\Models\Account;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\Actions\ViewAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\BulkChanges\CreatedAtBulkChange;
use Botble\Table\BulkChanges\EmailBulkChange;
use Botble\Table\BulkChanges\TextBulkChange;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\EmailColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Botble\Table\Columns\YesNoColumn;
use Botble\Table\HeaderActions\CreateHeaderAction;
use Illuminate\Database\Eloquent\Builder;

class AccountTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Account::class)
            ->addActions([
                ViewAction::make()
                    ->route('account.show')
                    ->permission('account.edit'),
                EditAction::make()->route('account.edit'),
                DeleteAction::make()->route('account.destroy'),
            ])
            ->addHeaderAction(
                CreateHeaderAction::make()
                    ->route('account.create')
                    ->permission('account.create')
            )
            ->queryUsing(function (Builder $query) {
                return $query
                    ->select([
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                        'phone',
                        'created_at',
                        'credits',
                        'avatar_id',
                        'confirmed_at',
                        'blocked_at',
                        'blocked_reason',
                        'is_verified',
                        'verified_at',
                    ])
                    ->with(['avatar'])
                    ->withCount(['properties'])
                    ->when((bool) setting('real_estate_enable_account_verification', false), function (Builder $query): void {
                        $query->whereNotNull('approved_at');
                    });
            })
            ->addBulkActions([
                DeleteBulkAction::make()->permission('account.destroy'),
            ])
            ->addBulkChanges([
                TextBulkChange::make()
                    ->name('first_name')
                    ->title(trans('plugins/real-estate::account.first_name'))
                    ->validate('required|max:120'),
                TextBulkChange::make()
                    ->name('last_name')
                    ->title(trans('plugins/real-estate::account.last_name'))
                    ->validate('required|max:120'),
                EmailBulkChange::make(),
                CreatedAtBulkChange::make(),
            ])
            ->addColumns([
                IdColumn::make(),
                FormattedColumn::make('avatar_id')
                    ->title(trans('core/base::tables.image'))
                    ->width(70)
                    ->getValueUsing(function (FormattedColumn $column) {
                        return Html::image(
                            RvMedia::getImageUrl($column->getItem()->avatar->url, 'thumb', false, RvMedia::getDefaultImage()),
                            BaseHelper::clean($column->getItem()->name),
                            ['width' => 50]
                        );
                    }),
                NameColumn::make()
                    ->route('account.edit')
                    ->orderable(false)
                    ->searchable(false),
                EmailColumn::make(),
                FormattedColumn::make('phone')
                    ->title(trans('plugins/real-estate::account.phone'))
                    ->alignLeft()
                    ->getValueUsing(function (FormattedColumn $column) {
                        return BaseHelper::clean($column->getItem()->phone ?: '&mdash;');
                    }),
                FormattedColumn::make('is_verified')
                    ->title(trans('plugins/real-estate::account.verified'))
                    ->width(100)
                    ->alignCenter()
                    ->getValueUsing(function (FormattedColumn $column) {
                        if ($column->getItem()->confirmed_at) {
                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-circle-check me-1" width="12" height="12" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9" /><path d="M9 12l2 2l4 -4" /></svg>';

                            return Html::tag('span', $icon . trans('plugins/real-estate::account.verified'), ['class' => 'badge bg-green-lt text-green badge-pill']);
                        }

                        return Html::tag('span', trans('plugins/real-estate::account.not_verified'), ['class' => 'badge bg-gray-lt text-muted']);
                    }),
                Column::make('credits')
                    ->title(trans('plugins/real-estate::account.credits'))
                    ->alignLeft(),
                FormattedColumn::make('blocked_at')
                    ->title(trans('plugins/real-estate::account.status'))
                    ->width(100)
                    ->searchable(false)
                    ->orderable(false)
                    ->getValueUsing(function (FormattedColumn $column) {
                        if ($column->getItem()->blocked_at) {
                            $attributes = ['class' => 'badge bg-danger text-danger-fg'];

                            if ($column->getItem()->blocked_reason) {
                                $attributes['title'] = $column->getItem()->blocked_reason;
                                $attributes['data-bs-toggle'] = 'tooltip';
                            }

                            return Html::tag('span', trans('plugins/real-estate::account.blocked'), $attributes);
                        }

                        return Html::tag('span', trans('plugins/real-estate::account.active'), ['class' => 'badge bg-success text-success-fg']);
                    }),
                FormattedColumn::make('updated_at')
                    ->title(trans('plugins/real-estate::account.number_of_properties'))
                    ->width(100)
                    ->orderable(false)
                    ->searchable(false)
                    ->getValueUsing(function (FormattedColumn $column) {
                        return $column->getItem()->properties_count;
                    }),
                CreatedAtColumn::make(),
            ])
            ->onAjax(function (AccountTable $table) {
                return $table->toJson(
                    $table
                        ->table
                        ->eloquent($table->query())
                    ->filter(function (Builder $query) {
                        $keyword = $this->request->input('search.value');

                        if (! $keyword) {
                            return $query;
                        }

                        return $query->where(function (Builder $query) use ($keyword): void {
                            $query
                                ->where('id', $keyword)
                                ->orWhere('first_name', 'LIKE', '%' . $keyword . '%')
                                ->orWhere('last_name', 'LIKE', '%' . $keyword . '%')
                                ->orWhere('email', 'LIKE', '%' . $keyword . '%')
                                ->orWhereDate('created_at', $keyword);
                        });
                    })
                );
            });

        if (setting('verify_account_email', false)) {
            $this->addColumn(
                YesNoColumn::make('confirmed_at')
                    ->title(trans('plugins/real-estate::account.email_verified')),
            );
        }
    }
}
