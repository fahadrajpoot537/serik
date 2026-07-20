<?php

namespace Botble\RealEstate\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investor extends BaseModel
{
    protected $table = 're_investors';

    protected $fillable = [
        'name',
        'status',
        'description',
        'avatar',
    ];

    protected $casts = [
        'status' => BaseStatusEnum::class,
        'name' => SafeContent::class,
        'description' => SafeContent::class,
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'investor_id');
    }
}
