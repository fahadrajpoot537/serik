<?php

namespace Botble\RealEstate\Models;

use Botble\ACL\Models\User;
use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyVisit extends BaseModel
{
    use SoftDeletes;

    protected $table = 're_property_visits';

    protected $fillable = [
        'account_id',
        'property_id',
        'listing_key',
        'property_name',
        'property_location',
        'property_price',
        'close_price',
        'mls_status',
        'transaction_type',
        'property_sub_type',
        'source',
        'view_count',
        'last_viewed_at',
        'delete_requested_at',
        'delete_approved_by',
        'delete_approved_at',
    ];

    protected $casts = [
        'property_price' => 'float',
        'close_price' => 'float',
        'view_count' => 'int',
        'last_viewed_at' => 'datetime',
        'delete_requested_at' => 'datetime',
        'delete_approved_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id')->withDefault();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id')->withDefault();
    }

    public function deleteApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delete_approved_by')->withDefault();
    }

    public static function recordForAccount(
        int $accountId,
        ?Property $property,
        string $listingKey,
        string $source = 'map',
        ?array $fallback = null
    ): void {
        $listingKey = strtoupper(trim($listingKey));

        if ($listingKey === '') {
            return;
        }

        $visit = self::query()
            ->where('account_id', $accountId)
            ->where('listing_key', $listingKey)
            ->first();

        if ($visit && $visit->trashed()) {
            return;
        }

        if (! $visit) {
            $visit = new self();
            $visit->account_id = $accountId;
            $visit->listing_key = $listingKey;
            $visit->view_count = 0;
        }

        if ($visit->delete_requested_at) {
            return;
        }

        $visit->property_id = $property?->getKey();
        $visit->property_name = $property?->name ?? ($fallback['name'] ?? null);
        $visit->property_location = $property?->location ?? ($fallback['location'] ?? null);
        $visit->property_price = $property?->price ?? ($fallback['price'] ?? null);
        $visit->close_price = $property?->ClosePrice ?? ($fallback['ClosePrice'] ?? $fallback['close_price'] ?? null);
        $visit->mls_status = $property?->MlsStatus ?? ($fallback['MlsStatus'] ?? $fallback['mls_status'] ?? null);
        $visit->transaction_type = $property?->TransactionType ?? ($fallback['TransactionType'] ?? null);
        $visit->property_sub_type = $property?->PropertySubType ?? ($fallback['PropertySubType'] ?? null);
        $visit->source = $source;
        $visit->view_count = (int) $visit->view_count + 1;
        $visit->last_viewed_at = now();
        $visit->save();
    }
}
