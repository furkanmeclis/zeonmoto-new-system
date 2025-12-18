<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'path',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Görsel URL'ini döndürür (storage'dan direkt)
     *
     * @return string|null
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->path) {
            return Storage::disk('public')->url($this->path);
        }

        return null;
    }
}