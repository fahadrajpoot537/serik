<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyHistory extends BaseModel
{
    protected $table = 're_property_history';

    protected $fillable = [
        'property_id',
        'external_id',
        'event',
        'price',
        'close_price',
        'mls_status',
        'transaction_type',
        'status',
        'listing_contract_date',
        'listing_modified_at',
        'close_date',
        'purchase_contract_date',
        'changed',
        'snapshot',
        'source',
        'recorded_at',
    ];

    protected $casts = [
        'price' => 'float',
        'close_price' => 'float',
        'changed' => 'array',
        'snapshot' => 'array',
        'listing_contract_date' => 'datetime',
        'listing_modified_at' => 'datetime',
        'close_date' => 'datetime',
        'purchase_contract_date' => 'datetime',
        'recorded_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}
