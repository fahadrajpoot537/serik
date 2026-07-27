<?php

namespace App\Models;

use Botble\Location\Models\City;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Neighborhood extends Model
{
    protected $fillable = [
        'city_id',
        'name',
        'slug',
        'latitude',
        'longitude',
        'property_count',
    ];

    protected $casts = [
        'city_id' => 'int',
        'latitude' => 'float',
        'longitude' => 'float',
        'property_count' => 'int',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
