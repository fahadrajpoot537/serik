<?php

namespace App\Models;

use Botble\Location\Models\City;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NearbyCity extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'city_id',
        'nearby_city_id',
        'distance_km',
    ];

    protected $casts = [
        'city_id' => 'int',
        'nearby_city_id' => 'int',
        'distance_km' => 'float',
        'created_at' => 'datetime',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function nearbyCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'nearby_city_id');
    }
}
