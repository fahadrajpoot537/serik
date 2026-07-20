<?php

namespace Botble\RealEstate\Models;

use Botble\ACL\Models\User;
use Botble\Base\Casts\SafeContent;
use Botble\Base\Facades\Html;
use Botble\Base\Models\BaseModel;
use Botble\Payment\Models\Payment;
use Botble\RealEstate\Enums\TransactionTypeEnum;
use Botble\RealEstate\Facades\RealEstateHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends BaseModel
{
    protected $table = 'transactions';

    protected $fillable = [
        'credits',
        'description',
        'user_id',
        'account_id',
        'payment_id',
        'type',
    ];

    protected $casts = [
        'type' => TransactionTypeEnum::class,
        'description' => SafeContent::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class)->withDefault();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class)->withDefault();
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->credits;
            }
        );
    }

    public function getDescription(): string
    {
        if (! RealEstateHelper::isEnabledCreditsSystem()) {
            return '';
        }

        $time = Html::tag('span', $this->created_at->diffForHumans(), ['class' => 'small italic']);

        $credits = number_format($this->credits);

        if ($this->user_id) {
            if ($this->type == TransactionTypeEnum::ADD) {
                return trans(
                    'plugins/real-estate::transaction.added_credits',
                    ['credits' => $credits, 'user' => $this->user->name]
                );
            }

            return trans(
                'plugins/real-estate::transaction.removed_credits',
                ['credits' => $credits, 'user' => $this->user->name]
            );
        }

        $description = trans('plugins/real-estate::transaction.purchased_credits', ['credits' => $credits]);
        if ($this->payment?->id) {
            $description .= ' ' . trans('plugins/real-estate::transaction.via') . ' ' . $this->payment->payment_channel->label() . ' ' . $time .
                ': ' . number_format($this->payment->amount, 2) . $this->payment->currency;
        }

        return $description;
    }
}
