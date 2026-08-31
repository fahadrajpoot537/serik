<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountWishlist extends Model
{
    protected $table = 're_account_wishlists';

    protected $fillable = [
        'account_id',
        'item_type',
        'item_id',
    ];

    protected $casts = [
        'account_id' => 'int',
        'item_id' => 'int',
    ];
}
