<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductExternal extends Model
{
    protected $fillable = [
        'product_id',
        'provider_key',
        'external_uniqid',
        'external_hash',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
